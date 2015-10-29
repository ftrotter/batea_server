<?php
	use Xi\Algorithm\Luhn;
/*
	This is how the clients works with the batea server

*/
class APIController extends BaseController {


//GET functions

/**
 * Tells the browser tool that a given wikipedia url is clinical which causes the history to start tracking
 */
//STUB
	public function isURLClinical($that_url){

		$the_url = urldecode($that_url);
		$url_parts = parse_url($the_url);

		$path = $url_parts['path'];
		$title = str_replace('/wiki/','',$path);	
	
		$is_clinical = true;

		if($is_clinical){
			$return_me [
				'is_success' => true,
				'is_clinical' => $is_clinical,
				'url_echo' => $url,
				'title_echo' => $title,
			];
		}else{
			$return_me [
				'is_success' => true,
				'is_clinical' => $is_clinical,
				];	
		}

		return Response::json($return_me);
		

	}

/**
 * Gets the list of known clinical website (like pubmed etc) in the form of url regexes 
 * TODO make these regex actually work...
 */
//STUB
	public function clinicalURLStubs(){

		$return_me = stdClass::__set_state(array(
   'is_success' => true,
   'clincalURLDomains' => 
  array (
    0 => 'medscape.com',
    1 => 'webmd.com',
    2 => 'nih.gov',
    3 => 'merckmanuals.com',
    4 => 'mayoclinic.org',
    5 => 'clevelandclinic.org',
    6 => 'nejm.org',
    7 => 'icsi.org',
  ),
   'clinicalURLRegex' => 
  array (
    0 => '/^(?:http(?:s)?:\\/\\/)?(?:[^\\.]+\\.)?medscape\\.com$/',
    1 => '/^(?:http(?:s)?:\\/\\/)?(?:[^\\.]+\\.)?webmd\\.com$/',
    2 => '/^(?:http(?:s)?:\\/\\/)?(?:[^\\.]+\\.)?nih\\.gov$/',
    3 => '/^(?:http(?:s)?:\\/\\/)?(?:[^\\.]+\\.)?merckmanuals\\.com$/',
    4 => '/^(?:http(?:s)?:\\/\\/)?(?:[^\\.]+\\.)?mayoclinic\\.org$/',
    5 => '/^(?:http(?:s)?:\\/\\/)?(?:[^\\.]+\\.)?clevelandclinic\\.org$/',
    6 => '/^(?:http(?:s)?:\\/\\/)?(?:[^\\.]+\\.)?nejm\\.org$/',
    7 => '/^(?:http(?:s)?:\\/\\/)?(?:[^\\.]+\\.)?icsi\\.org$/',
  ),
));

		return Response::json($return_me);
	
	}


/**
 * Gets what little information we allow public about a given browser token (i.e. consented, or not consented)
 * This one should be rate limited for security....
 * and should always return with a 'no' for unknown token to prevent hacking
 */
	public function Donator($browser_token){

		$CT = new ConsentToken();
		$CT->sync($browser_token);
		$is_consented = $CT->data_array['is_consented'];

		$return_me = [
			'is_success' => true,
			'is_consented' => $is_consented,
		];		

	}


//POST functions

/**
 *	Given a browser token... save a forager Comment
 */
	public function foragerComment($browser_token){
		return($this->_justSaveAndEncrypt($browser_token,'SecureForagerComment'));
	}


/**
 * given a browser token, save a history of the users browser session
 */
	public function historyTree($browser_token){
		return($this->_justSaveAndEncrypt($browser_token,'SecureHistoryTree'));
	}


/**
 * save a users settings (used to track consent and demographics etc)
 */
	public function saveSettings($browser_token){
		$return_me = $this->_justSaveAndEncrypt($browser_token,'SecureSettings');

		//if is_consented is missing or false, we set consent to false...
		// is_consented must be resent along with any settings changess..
		$got_is_consent = Input::get('is_consented',false);
		$CT = new ConsentToken();
		$CT->data_array['consenttoken_id'] = $browser_token;
		$CT->data_array['is_consented'] = $got_is_consent;
		$CT->save();

		return($return_me);

	}

/**
 * save a comment on a specific wiki text... 
 */
	public function wikiComment($browser_token){
		return($this->_justSaveAndEncrypt($browser_token,'SecureWikiComment'));
	}



/**
 * This gets a donator token on initial setup of the client.
 * could this be made more secure?? perhaps...
 * but we are at least logging it...
 */
	public function DonatorToken(){

		$luhn = new Luhn();
		$token = $luhn->generate(time());
	
		$this->_logDonation($token,"Created new token");

		$return_me = [
			'is_success' => true,
			'DonatorToken' => $token
			];

		return Response::json($return_me);
	}

/**
*	We keep encrypted logs of the IP address of donations, just in case someone breaks into the server
*	TODO: make sure the apache logs are recycled quickly for this site... 24 is probably enough...
*/
	public function _logDonation($token,$event){

		$now = time();
		$log_this  = $_SERVER;
		$log_this['donation_token'] = $token;	
		$log_this['event'] = $event;	
		$log_this['when_logged'] = $now;
		$securelogs_data = $this->_encryptThis($log_this);
	
		$SecureLogs = new SecureLogs();
		$SecureLogs->data_array = $securelogs_data;
		$SecureLogs->save();
					

	}


/**
 *	This is the basic template method that just saves the data
 *	to the right Mongo table
 * 	after encrypting it...
 */
	public _justSaveAndEncrypt($token,$mongoObject){

		$save_me  = Input::all(); //all the json..
		$save_me['donator_token'] = $token;
		$encrypted_save_me = $this->encryptThis($save_me);
		$MO = new $mongoObject();
		$MO->data_array = $encrypted_save_me;
		$MO->save();

		$this->_logDonation($token,"Just saved $mongoObject");	

		return(Response::json(array('is_success' => true)));

	}
/**
 *	a function that accepts a thing to encrypt, and provides a assymetrically encrypted passkey
 * 	and a symetrically encrypted 'thing'.. you have to use the private key to decrypt the password
 *	then decrypt the main "thing". This circumvents the size limiations of assemtric encryption
 *	For now this is stubbed, but we want to have our data structures right..
 *	So that we can do this at any time...
 */
//STUB
	public function _encryptThis($thing){

		if(is_array($thing) || is_object($thing)){
			$thing = json_encode($thing); //we just want strings...
		}

		$actually_encrypt = false;
		
		if($actually_encrypt){
			//implement ASAP...

		}else{
			$return_me[
				'encrypted_passkey' => 'not_implemented_using_base64_for_now',
				'encrypted_thing' =>	base64_encode($thing), //so that we can see what is happening for the time being...
				];		

		}

		return($return_me);
	
	}

}
