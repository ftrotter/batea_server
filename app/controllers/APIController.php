<?php
	use Xi\Algorithm\Luhn;
/*
	This is how the clients works with the batea server

*/
class APIController extends BaseController {

	private $this_time = null;

//GET functions

/**
 * Tells the browser tool that a given wikipedia url is clinical which causes the history to start tracking
 */
//STUB
	public function isURLClinical($that_url){

		$the_url = base64_decode($that_url);
		$url_parts = parse_url($the_url);

		$path = $url_parts['path'];
		$title = str_replace('/wiki/','',$path);	
	
		$result = WikiTags::isTitleClinical($title);

		$is_clinical = $result['is_titleclinical'];

		if($is_clinical){
			$return_me = [
				'is_success' => true,
				'is_clinical' => $is_clinical,
				'url_echo' => $the_url,
				'title_echo' => $title,
			];
		}else{
			$return_me = [
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

		$return_me = array(
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
		if(isset($CT->data_array['is_consented'])){
			$is_consented = $CT->data_array['is_consented'];
		}else{
			$is_consented = false;
		}
		$return_me = [
			'is_success' => true,
			'is_consented' => $is_consented,
		];		

		return Response::json($return_me);
	}


//POST functions

/**
 *	Given a browser token... save a forager Comment
 */
	public function foragerComment($browser_token){
		return($this->_justSaveAndEncrypt($browser_token,'SecureForagerComment'));
	}
/**
 *  Read back foraferComment data, when it is in debug mode (i.e. not encrypted)
 */
	public function foragerCommentDebug($browser_token){
		return(Response::json($this->_justRetrieveAndDecrypt($browser_token,'SecureForagerComment')));
	}



/**
 * given a browser token, save a history of the users browser session
 */
	public function historyTree($browser_token){
		return($this->_justSaveAndEncrypt($browser_token,'SecureHistoryTree'));
	}

/**
 *  Read back historTree data, when it is in debug mode (i.e. not encrypted)
 */
	public function historyTreeDebug($browser_token){
		return(Response::json($this->_justRetrieveAndDecrypt($browser_token,'SecureHistoryTree')));
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
		$CT->sync();

		return($return_me);

	}

/**
 * save a comment on a specific wiki text... 
 */
	public function wikiComment($browser_token){
		return($this->_justSaveAndEncrypt($browser_token,'SecureWikiComment'));
	}
/**
 *  Read back wikiComment data, when it is in debug mode (i.e. not encrypted)
 */
	public function wikiCommentDebug($browser_token){
		return(Response::json($this->_justRetrieveAndDecrypt($browser_token,'SecureWikiComment')));
	}


/**
 *	We use timestamps as ids in several places (including tokens)...
 *	We want to use the same timestamp in any API call, this lets us do that
 */
	public function _getTime(){
		if(!is_null($this->this_time)){
			return($this->this_time);
		}
		
		//this is the first call this run...
		$this->this_time = time();
		return($this->this_time);
			
	}


/**
 * This gets a donator token on initial setup of the client.
 * could this be made more secure?? perhaps...
 * but we are at least logging it...
 */
	public function DonatorToken(){

		
		

		$luhn = new Luhn();
		$token = $luhn->generate($this->_getTime());
	
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

		$now = $this->_getTime();
		$log_this  = $_SERVER;
		$log_this['donation_token'] = $token;	
		$log_this['event'] = $event;	
		$log_this['when_logged'] = $now;
		$securelogs_data = $this->_encryptThis($log_this);
	
		$SecureLogs = new SecureLogs();
		$SecureLogs->data_array = $securelogs_data;
		$SecureLogs->sync($now);
					

	}

/**
 *	When _justSaveAndEncrypt is not encrypting (i.e. debug mode) this function can reverse it
 *	Eventually this could be made to work with encryption too... perhaps if we are clever we can
 *	Find a way to make it work with an unencrypt recent system...
 */
	public function _justRetrieveAndDecrypt($token,$mongoObject,$specific_id = null,$start_timestamp = null,$end_timestamp = null){

		$collection_name = strtolower($mongoObject);
		$MO = new $mongoObject();
		$collection = $MO->mongo->$collection_name;
		
		$now = $this->_getTime();
		$minus_one_day = strtotime('-1 day',$now);

		//becuase our ids are always timestamps, its pretty easy to just get todays data
		$search = ["$collection_name"."_id" => ['$gt' => $minus_one_day  ]];
		
		$cursor = $collection->find($search);
	
		$coded_by_id_array = iterator_to_array($cursor);
		sort($coded_by_id_array); //gets rid of the keys..
		$return_me = [];
		$today_key = ''; //TODO implement me...
		foreach($coded_by_id_array as $this_item){
		
			$data = $this->_decryptThis($this_item,$today_key);	
			$this_item['data'] = $data;
			$return_me[] = $this_item;
		}
	
		return($return_me);

	}

/**
 *	This is the basic template method that just saves the data
 *	to the right Mongo table
 * 	after encrypting it...
 */
	public function _justSaveAndEncrypt($token,$mongoObject){

		$this_time = $this->_getTime();

		$save_me  = Input::all(); //all the json..
		$save_me['donator_token'] = $token;

		$MO = new $mongoObject();
		$MO->secureAndSync($this_time,$save_me);

		$this->_logDonation($token,"Just saved $mongoObject");	

		return(Response::json(array('is_success' => true)));

	}

/**
 *	This undoes the encrypt function...
 */
	public function _decryptThis($thing, $today_key = null, $forever_key = null){

		//prefer the forever key if you have it..
		if(!is_null($forever_key)){
			$my_key = $forever_key;
			$passkey_field = 'encrypted_passkey';		
		}else{
			if(!is_null($today_key)){
				$my_key = $today_key;
				$passkey_field = 'today_encrypted_passkey';		
			}else{
				echo "APIController.php _decryptThis error: I need to either have a today key or a forever key not be null";
				exit();
			}
		}

		//I will use $my_key and $passkey_field to decrypt this data..
		//TODO implement real encryption here...

		$encrypted_thing = $thing['encrypted_thing'];
		$plain_text_json = base64_decode($encrypted_thing);
		$data = json_decode($plain_text_json,true);

		return($data);

	}

/**
 *	a function that accepts a thing to encrypt, and provides a assymetrically encrypted passkey
 * 	and a symetrically encrypted 'thing'.. you have to use the private key to decrypt the password
 *	then decrypt the main "thing". This circumvents the size limiations of assemtric encryption
 *	For now this is stubbed, but we want to have our data structures right..
 *	So that we can do this at any time...
 */
//STUB
	public function _encryptThis($thing, $today_key = null, $forever_key = null){

		if(is_array($thing) || is_object($thing)){
			$thing = json_encode($thing); //we just want strings...
		}

		$actually_encrypt = false;
		
		if($actually_encrypt){
			//implement ASAP...

		}else{
			$return_me = [
				'encrypted_passkey' => 'not_implemented_using_base64_for_now',
				'today_encrypted_passkey' => 'not_implemented_using_base64_for_now',
				'encrypted_thing' =>	base64_encode($thing), //so that we can see what is happening for the time being...
				];		

		}

		return($return_me);
	
	}

}
