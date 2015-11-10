<?php
/*

	implement a consistent crypto layer on top of VeryMongo

*/
class VerySecure  extends VeryMongo{


	private $publicKey;
	private $tempPublicKey;
	private $tempPrivateKey;

	private $temp_public_key_file;
	private $temp_private_key_file;
	private $public_key_file;

	private $cipher = 'aes-256-cbc';

	private $this_time;

	private $had_to_refresh;

	public function __construct(){


		$temp_key_dir = app_path()."/storage/temp_keys/";
		$this->temp_public_key_file = $temp_key_dir."temp_public_key.key";
		$this->temp_private_key_file = $temp_key_dir."temp_private_key.key";
		
		$this->public_key_file = app_path()."/config/keys/public.key";
	
		$is_need_to_refresh_keys = false;
		if(!file_exists($this->temp_private_key_file)){
			$is_need_to_refresh_keys = true;
		}else{
			$key_last_written_time = filemtime($this->temp_private_key_file); 
			$now = time();
			$seconds_to_keep_temp_keys = Config::get('app.seconds_to_keep_temp_keys',86400); //default to one day
			$seconds_kept = $now - $key_last_written_time;
			if($seconds_kept > $seconds_to_keep_temp_keys){
				$is_need_to_refresh_keys = true;
			} 
/*
			echo "key_last_written_time = $key_last_written_time<br>";
			echo "now = $now<br>";
			echo "seconds_to_keep_temp_keys = $seconds_to_keep_temp_keys<br>";
			echo "seconds_kept = $seconds_kept<br>";
*/			


		}
	
		if($is_need_to_refresh_keys){
			$this->had_to_refresh = true;
			$this->refresh_keys();
		}else{
			$this->had_to_refresh = false;
		}

		$this->load_keys();
	
		parent::__construct();

	}
/**
 *	Load the keys into memory...
 */
	private function load_keys(){
	
        	$this->publicKey  = file_get_contents($this->public_key_file);
        	$this->tempPublicKey = file_get_contents($this->temp_public_key_file);
        	$this->tempPrivateKey = file_get_contents($this->temp_private_key_file);

	}



	public function sync($id = 0, $versioning = false){

		if(!isset($this->data_array['encrypted_thing'])){
			echo "VerySecure.php function sync cannot work without having the encryption run..";
			exit();
		}

		parent::sync($id,$versioning);

	}

/**
 *	Overwrite the temporary key files with new values...
 */
	private function refresh_keys(){

		$config = [
			'private_key_bits' => 2048,      // Size of Key.
    			'private_key_type' => OPENSSL_KEYTYPE_RSA,
			];

		$PKEY_obj = openssl_pkey_new($config);
		openssl_pkey_export_to_file($PKEY_obj,$this->temp_private_key_file);
		
		$pubkey = openssl_pkey_get_details($PKEY_obj);
		file_put_contents($this->temp_public_key_file,$pubkey['key']);

		openssl_free_key($PKEY_obj);
	}


/**
 *      We use timestamps as ids in several places (including tokens)...
 *      We want to use the same timestamp in any API call, this lets us do that
 */
        public function getTime(){
                if(!is_null($this->this_time)){
                        return($this->this_time);
                }

                //this is the first call this run...
                $this->this_time = time();
                return($this->this_time);

        }


	public function loadRecent(){

		$debug_encrypt = true;

		$now = $this->getTime();

		$seconds_to_keep_temp_keys = Config::get('seconds_to_keep_temp_keys',86400); // a time before now...
		$test_starting_from = (int) $now - (int) $seconds_to_keep_temp_keys; // a time before now...

		$name = strtolower(get_class($this));
	
		$my_id = $name."_id";

		$now = $this->getTime();
		$starting_from = strtotime('-1 day',$now);

		//TODO figure out why the first method (used to make test_starting_from
		//results in the just the same as "now"?? Why did we need to use (int);
		//thats messed up... but I have to move on.

		/*
		echo "now is $now starting_from is $starting_from and $test_starting_from is also";
		exit();
		*/

		$search_query = [$my_id => ['$gt' => $starting_from]];

		$my_results = $this->find($search_query);
		
		foreach($my_results as $index => $this_result){

			$decrypted = VerySecure::decryptThis(	
								$this_result,
								$this->tempPrivateKey);



			$my_results[$index]['decrypted'] = $decrypted;

		}

		return($my_results);

	}

/**
 *	Given an encrypted password, and a private key which can decrypte the password, this can decrypt a thing
 */
	public static function decryptThis(	$result_row,
						$private_key){


		if(!isset($result_row['encrypted_pass_key'])){
			echo "VerySecure.php error: the decryptThis function expects a row of results.. but instead I got <br><pre>";
			var_export($result_row);
			exit();
		}

		$encrypted_pass_key = base64_decode($result_row['encrypted_pass_key']);
		$encrypted_thing = base64_decode($result_row['encrypted_thing']);		

		$pass_key = Crypto::decrypt_using_private_key($encrypted_pass_key,$private_key);

		$decrypted_json = Crypto::decrypt_using_passkey($encrypted_thing,$pass_key);

		$decrypted  = json_decode($decrypted_json,true);

		return($decrypted);
	

	}


	public function secureAndSync($id,$data){

		$encryptedData = VerySecure::encryptThis($data,$this->publicKey,$this->tempPublicKey);
		$this->data_array = $encryptedData;
		$this->sync($id); 

	}

	public static function encryptThis($thing,$publicKey,$tempPublicKey){

		$debug_encrypt = false;
	
		if(is_array($thing) || is_object($thing)){
                        $thing = json_encode($thing); //we just want strings...
                }

		$return_me = [];

		if($debug_encrypt){
			$return_me['base64_insurance'] = base64_encode($thing);
		}

	
		$pass_key = Crypto::make_random_passkey();
	
		if($debug_encrypt){
			$return_me['plaintext_passkey_going_in'] = base64_encode($pass_key);
		}

		$encrypted_thing = Crypto::encrypt_using_passkey($thing,$pass_key);

		$return_me['encrypted_thing'] = base64_encode($encrypted_thing);
		
		//now lets store the pass_key, after using assymetric encrypt on it.. 


		$temp_pass_key = Crypto::encrypt_using_public_key($pass_key,$tempPublicKey);
	
		$return_me['temp_encrypted_pass_key'] = base64_encode($temp_pass_key);

		$enc_pass_key = Crypto::encrypt_using_public_key($pass_key,$tempPublicKey);

		$return_me['encrypted_pass_key'] = base64_encode($enc_pass_key);
		
		$pass_key = 'forgotten'; //just in case...

                return($return_me);

	}

}
