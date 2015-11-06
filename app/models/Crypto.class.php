<?php
	define('AES_256_CBC','aes-256-cbc');

class Crypto{


	public static function make_random_passkey(){
		$encryption_key = openssl_random_pseudo_bytes(32);
		return($encryption_key);
	}
	

	public static function make_random_salt(){
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
        	//so we do this to make sure that it is 16bytes...
 		$iv = substr(hash('sha256', $iv), 0, 16);
		return($iv);
	}

	public static function encrypt_using_passkey($thing_to_encrypt,$pass_key){
		$salt = Crypto::make_random_salt();
		$encrypted = openssl_encrypt($thing_to_encrypt, AES_256_CBC, $pass_key, 0, $salt);
		$encrypted = $encrypted .":". $salt;
		return($encrypted);
	}

	public static function decrypt_using_passkey($encrypted_thing_with_salt,$pass_key){
		list($encrypted_thing,$salt) = explode(':',$encrypted_thing_with_salt);	
		$decrypted = openssl_decrypt($encrypted_thing, AES_256_CBC, $pass_key, 0, $salt);
		return($decrypted);
	}

	public static function decrypt_using_private_key($encrypted_thing,$private_key_contents){

		if (!$privateKey = openssl_pkey_get_private($private_key_contents)){
    			die('decrypt_using_public_key: Private Key failed');
		}
		$a_key = openssl_pkey_get_details($privateKey);
 
 
    		$decrypted = '';
    		if (!openssl_private_decrypt($encrypted_thing, $decrypted, $privateKey)){
        		die('Failed to decrypt data');
    		}

		openssl_free_key($privateKey);

		return($decrypted);

	}




	public static function encrypt_using_public_key($thing_to_encrypt,$public_key_contents){

		$publicKey = openssl_pkey_get_public($public_key_contents);
		$a_key = openssl_pkey_get_details($publicKey);

		// Encrypt the data in small chunks and then combine and send it.
		$encrypted = '';
		if (!openssl_public_encrypt($thing_to_encrypt, $encrypted, $publicKey))
		{
        		die('encrypt_using_public_key: Failed to encrypt data');
		}

		openssl_free_key($publicKey);

		// This is the final encrypted data to be sent to the recipient
		return($encrypted);

	}

}//end class

