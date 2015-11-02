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

	public function __construct(){


		$temp_key_dir = app_path()."/storage/temp_keys/";
		$this->temp_public_key_file = $temp_key_dir."temp_public_key.pem";
		$this->temp_private_key_file = $temp_key_dir."temp_private_key.pem";
		
		$this->public_key_file = app_path()."/config/keys/public.pem";
	
		$is_need_to_refresh_keys = false;
		if(!file_exists($this->temp_private_key_file)){
			$is_need_to_refresh_keys = true;
		}else{
			$key_last_written_time = filemtime($this->temp_private_key_file); 
			$now = time();
			$seconds_to_keep_temp_keys = Config::get('seconds_to_keep_temp_keys',86400); //default to one day
			$seconds_kept = $now - $key_last_written_time;
			if($seconds_kept > $seconds_to_keep_temp_keys){
				$is_need_to_refresh_keys = true;
			} 
		}
		
		if($is_need_to_refresh_keys){
			$this->refresh_keys();
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

		$PKEY_obj = openssl_pkey_new();
		openssl_pkey_export($PKEY_obj,$privkey,null);
		file_put_contents($this->temp_private_key_file,$privkey);
		
		$pubkey = openssl_pkey_get_details($PKEY_obj);
		file_put_contents($this->temp_public_key_file,$pubkey['key']);
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

		$now = $this->getTime();

		$starting_from = $now - Config::get('seconds_to_keep_temp_keys',86400); // a time before now...

		$name = strtolower(get_class($this));
	
		$my_id = $name."_id";

		$search_query = [$my_id => ['$gt' => $starting_from]];

		$my_results = $this->find($search_query);
		
		foreach($my_results as $index => $this_result){

			$decrypted = VerySecure::decryptThis(	$this_result['encrypted_thing'],
								$this_result['temp_encrypted_pass_key'],
								$this->tempPrivateKey,
								$this->cipher);

			$my_results[$index]['decrypted'] = $decrypted;
		}

		return($my_results);

	}

/**
 *	Given an encrypted password, and a private key which can decrypte the password, this can decrypt a thing
 */
	public static function decryptThis(	$encrypted_thing,
						$encrypted_passkey,
						$private_key,
						$cipher){

		$encrypted_passkey = base64_decode($encrypted_passkey);

		$success = @openssl_private_decrypt($encrypted_passkey,$cleartext_passkey,$private_key);

		if(!$success){
			echo "Failed to decryptThis\n";
			exit();
		}

		$encrypted_thing = base64_decode($encrypted_thing);

		list($encrypted, $iv) = explode(':'.$encrypted_thing); 		

		$decrypted = openssl_decrypt($encrypted_thing, $cipher, $cleartext_passkey, 0, $iv);


		return($decrypted);
	

	}


	public function secureAndSync($id,$data){

		$encryptedData = VerySecure::encryptThis($data,$this->cipher,$this->publicKey,$this->tempPublicKey);
		$this->data_array = $encryptedData;
		$this->sync($id); 

	}

	public static function encryptThis($thing,$cipher,$publicKey,$tempPublicKey){
	
		if(is_array($thing) || is_object($thing)){
                        $thing = json_encode($thing); //we just want strings...
                }

		$use_base64 = Config::get('app.base64_plaintext_debug_encryption',false);
		$return_me = [];

		if($use_base64){
			$return_me['base64_insurance'] = base64_encode($thing);
		}

		$factory = new RandomLib\Factory;
		$generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));

		$passwordLength = 32; // Or more
		$pass_key = $generator->generateString($passwordLength);
		
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));

		$encrypted = openssl_encrypt($thing, $cipher, $pass_key, 0, $iv);

		$store_me = $encrypted . ':' . $iv;

		$return_me['encrypted_thing'] = base64_encode($store_me);
		
		//now lets store the pass_key, after using assymetric encrypt on it.. 

		$success = @openssl_public_encrypt($data,$temp_pass_key,$tempPublicKey);	
		if(!$success){
			$error = openssl_error_string();
			echo "VerySecure encryptThis openssl_public_encrypt with tempPublicKey failed\n<br> $error \n";
			
			exit();
		}	

		$return_me['temp_encrypted_pass_key'] = base64_encode($temp_pass_key);

		$success = @openssl_public_encrypt($data,$pass_key,$publicKey);	
		if(!$success){
			$error = openssl_error_string();
			echo "VerySecure encryptThis openssl_public_encrypt with publicKey failed <br>\n $error \n";
			exit();
		}	

		$return_me['encrypted_pass_key'] = base64_encode($pass_key);
		
                return($return_me);

	}

}
