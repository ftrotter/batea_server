<?php



class WikiScrapper{


	public $run_silent = false;

	public $redirect_cache = array();
	public $wiki_json_cache = array();
	public $wpmed_cache = array();

	private $email_address = "fred.trotter+wikiscraper@gmail.com";

	public function __construct($email = null){

		if(!is_null($email)){
			$this->email = $email;
		}
		$WikiData = new WikiData();
		$all_wikidata = $WikiData->get_all();
		foreach($all_wikidata as $id => $this_wikidata){
			$this_wikidata_id = $this_wikidata['wikidata_id'];
			if(strpos($this_wikidata_id,'|') !== false){
				list($this_title,$this_cache_id) = explode('|',$this_wikidata_id);
				$this->wiki_json_cache[$this_title][$this_cache_id] = json_encode($this_wikidata);
			}else{
				//there is a 0 in the database...
				//wtf?
			}
		}


	}



/*
	Determines if a given title is a redirect
	if it is it returns the redirected url
	if not it returns false..
	unless the return_the_title is set true then it just returns the wikititle that was passed in..
*/
	public static function get_redirect($title,$return_the_title = false,$redirect_cache = null,$run_silent = true){

		$title = WikiData::wikiurlencode($title);

		if(isset($redirect_cache[$title])){
			if($redirect_cache[$title]){
				return($redirect_cache[$title]);
			}else{
				if($return_the_title){
					return($title);
				}else{
					return(false);
				}	
			}
		}


                $wiki_api_result = WikiScrapper::download_wiki_result($title);
                $wiki_api_json = $wiki_api_result['result'];
		$redirect_to = false;
		$last_redirect = $title;
                while(WikiData::is_redirect_from_json($wiki_api_json)){ //sometimes wiki pages are just stubs that redirect
			//I cannot seem to get to the bottom of the pile...
                        $redirect_to = WikiData::parse_redirect_from_json($wiki_api_json); //returns the title that the orginal title redirects to..
			if(strcmp($redirect_to,$last_redirect) == 0){
				//then we are in an infinite loop because is_redirect_from_json is stupid
				var_export($wiki_api_json);
				if(!$run_silent){  echo "REDIRECT LOOP!!!\n"; }
				continue;
			}
		//		var_export($wiki_api_json);
			$redirect_to = WikiData::wikiurlencode($redirect_to);
			//echo "started with '$title' last run was '$last_redirect' now mining '$redirect_to'\n";
                	$wiki_api_result = WikiScrapper::download_wiki_result($redirect_to);
                	$wiki_api_json = $wiki_api_result['result'];
			$last_redirect = $redirect_to;
		}

		if(!is_null($redirect_cache)){
			$redirect_cache[$title] = $redirect_to;	
		}

		if($redirect_to){
			return($redirect_to);
		}else{

                        if($return_the_title){
                                return($title);
                        }else{
                                return(false);
                        }
		

		}

	}



	public static $talk_tags_map = array(
		'is_WPMED' => '{{WPMED',
		'is_vital' => '{{Vital',
		'is_Anatomy' => '{{WikiProject Anatomy',
		'is_Medicine' => '{{WikiProject Medicine',
		);

	public static $medical_page_filters = array(
		'{{WPMED',
		'{{WikiProject Anatomy',
		'{{WikiProject Medicine',
		'{{WikiProject Computational Biology',
		'{{Wikiproject MCB',
		'{{EvolWikiProject}}',
		'{{WikiProject Pharmacology',
		);	

/*
	gets talk pages and then ensures that if they are medical projects
	that we have them in our data cache as such...
	WARNING I am retiring this.. it is trying to do too much
*/
	public static function get_clean_talkpage($title,$id_to_get = null,$wpmed_cache = null,$run_silent = true){
		$results =  WikiScrapper::get_clean_wikitext("Talk:$title",$id_to_get);
		return($results);
	
/*	
		if(!is_null($wpmed_cache)){
			$wpmed_cache[$title] = false;
			foreach(WikiScrapper::medical_page_filters as $this_filter){
				if(strpos($results,$this_filter) !== false){
						//then this is a wikiproject medicine project!!!
					//echo "CLINICAL Found WPMED \n";
					if(!$run_silent){ echo 'c'; }
					$wpmed_cache[$title] = true;	
				}
			}
		}
	
		$wikitag_id = WikiData::get_wikidata_id($title,$id_to_get);
		foreach(WikiScrapper::talk_tags_map as $tag => $search_string){
			if(strpos($results,$search_string) !== false){
				$wiki_tag_array[$tag] = true;		
			}else{
				$wiki_tag_array[$tag] = false;		
			}
	
		}

		$WikiTags = new WikiTags();
		$WikiTags->data_array = $wiki_tag_array;
		$WikiTags->data_array['title'] = $title;
		$WikiTags->sync($wikitag_id);
*/
	
	}


	public static function get_clean_wikitext($title,$id_to_get = null){

               	$wiki_api_result = WikiScrapper::download_wiki_result($title,$id_to_get); //this returns the wiki_json for the right title.
		if($wiki_api_result['is_success']){
                       	$wiki_api_json = $wiki_api_result['result'];
		}else{
				return(false);
		} 
                

		if(WikiData::is_redirect_from_json($wiki_api_json)){ //sometimes wiki pages are just stubs that redirect
                                        //the web user just sees the right page...
                                        //but the API actually returns the redirect...
                        $redirect_to = WikiData::parse_redirect_from_json($wiki_api_json); //this returns the title that the orginal title redirects to..
                        $wiki_api_result = WikiScrapper::download_wiki_result($redirect_to); //this returns the wiki_json for the right title.
			if($wiki_api_result['is_success']){
                        	$wiki_api_json = $wiki_api_result['result'];
			}else{
				return(false);
			} 
                }

		$uncompressed_wiki_text = WikiData::get_wikitext_from_json($wiki_api_json);
		if($uncompressed_wiki_text){ //returns false on fail after all..
			$compressed_wiki_text = WikiData::compress_wikitext_templates('{{','}}',$uncompressed_wiki_text);
        		$compressed_wiki_text = WikiData::compress_wikitext_templates('{|','|}',$compressed_wiki_text);
			return($compressed_wiki_text);
		}else{
			return(false);
		}
	}
/*
 * Given a particular title of a wikipage, download the JSON representation...
 * if you pass in a page cache, then it will be used.. if you dont then it will force a new download
 * the function runs silently by default
 */
public static function download_wiki_result($title,$id_to_get = null,&$cache_to_use = null,$run_slow = true,$run_silent = true){

		if(strpos($title,'File:') !== false || strpos($title,'Image:') !== false){
			return("{result: 'NO FILE DOWNLOADS'}");//no idea what this will do, but I am not downloading files anymore...
		}


		//lets not download a wikipage twice in a run at least...
		if(is_null($id_to_get)){
			$cache_id = 0;
		}else{
			$cache_id = $id_to_get;
		}


		if(isset($cache_to_use[$title][$cache_id])){
			//echo "returning cache for $title\n";
			if(!$run_silent){ echo 'w'; }
			return($cache_to_use[$title][$cache_id]);
		}

		if(!$run_silent){ echo "\t\tdownloading $title\n"; }
		if($run_slow){
			sleep(1); //lets slow this down.
		}

                $api_url = WikiData::get_wiki_api_url($title,$id_to_get);
		$result_array = WikiScrapper::wikipedia_raw_download($api_url); 

		if(strlen($result_array['result']) < 20){
			//then there must be a rate limiting problem
			if(!$run_silent){ echo "I got $result \n\n Now Slowing down and retrying call\n"; }
			sleep(5);
			return WikiScrapper::download_wiki_result($title,$id_to_get,$cache_to_use,$run_slow,$run_silent);
		}


		if($result_array['is_success']){
			$WikiData = new WikiData();
			$WikiData->fromJSON($result_);
			$wikidata_id = WikiData::get_wikidata_id($title,$cache_id);
			$WikiData->sync($wikidata_id);
	
			//which is why this is pass by refernce..
			if(!is_null($cache_to_use)){
				$cache_to_use[$title][$cache_id] = $result;		
			}
		}		

                return($result_array);

}

	public static function wikipedia_raw_download($api_url){
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_USERAGENT,
                        'ClincalSpade/1.0 (http://www.fredtrotter.com/; fred.trotter@gmail.com)');

                curl_setopt($ch, CURLOPT_URL, $api_url);
                $result = curl_exec($ch);
		$return_me = [];
                if (!$result) {
			$return_me['is_success'] = false;
			$error = "wikipedia_raw_download failed with $api_url\n"; 
			$error .= var_export(curl_getinfo($ch),true);
			$error .= 'cURL Error: '.curl_error($ch);
			$return_me['error']  = $error;
                }else{
			$return_me['is_success'] = true;
			$return_me['result'] = $result;
		}

	
		return($return_me);
	}


	


	public static function get_medical_links_from_wikiline($wikiline,$depth = '',$wpmed_cache = null, $run_silent = true){

		if(!$run_silent){ echo "\n>"; }
		$all_links = WikiScrapper::get_links_from_wikiline($wikiline);
		$return_me = array();
		foreach($all_links as $label => $wikititle){

			WikiScrapper::get_clean_talkpage($wikititle);
			//which sets the wpmed cache...
			if(!is_null($wpmed_cache)){
				if($wpmed_cache[$wikititle]){
					$return_me[$wikititle] = $wikititle;
				}
			}
		}
		if(!$run_silent){ echo "<\n";}
		return($return_me);

	}



/*
	Attempts to get all of the simple links from a line of wikitext
	this is tricky because there are links inside templates...
	so we eliminate these first...
*/
        public static function get_links_from_wikiline($wikiline){

                                $regex = "/\{\{(.*?)\}\}/"; //should catch everything inside double curly braces..

                                preg_match_all($regex,$wikiline,$matches);
                                if(count($matches[0]) != 0){
					//then lets remove these matches 
					//and replace them (and any potential links inside them)
					//with something that will not match the links regex..
					$wikiline = preg_replace($regex," |||TEMPLATE||| ",$wikiline);
                                }
		
				$regex = "/\[\[(.*?)\]\]/"; //should catch everything inside double square braces..

                                preg_match_all($regex,$wikiline,$matches);
                                if(count($matches[0]) != 0){
					$return_array = array();
					foreach($matches[1] as $a_line_text){
						if(strpos($a_line_text,'|')){ //then it has a label!!!
							//the label is different than the page title..
							list($a_line_text,$label) = explode('|',$a_line_text);
							if(strlen(trim($label)) ==0){
								//it had the | but nothing to thre right... it happens...
								$label = $a_line_text;
	
							}
						}else{
							//the label is the same as the page title...
							$label = $a_line_text;
						}
						$return_the_title_rather_than_false = true;	
						$a_redirect =  WikiScrapper::get_redirect($a_line_text,$return_the_title_rather_than_false);
						if($a_redirect){
							$results_array[$label] = $a_redirect;
						}
					}
					return($results_array);
                                }else{
					return(array()); //nothing here return empty array
				}
        }



public static function get_html_from_wikitext($wikitext){


	if(strlen($wikitext) == 0){
		return(''); //the translation of nothing is nothing...
	}

        $parsoid_data = array(
                'wt' => $wikitext,
                'body' => 1,
        );	

	$parsoid_url = "https://parsoid-lb.eqiad.wikimedia.org/enwiki/";
	$parsoid_html = WikiScrapper::post_to_url($parsoid_url,$parsoid_data);
	return($parsoid_html);
}

/*
 Generic function to post to a url so that we can call parsoid...
*/
public static function post_to_url($url, $data) {

   $post = curl_init();

   curl_setopt($post, CURLOPT_URL, $url);
   curl_setopt($post, CURLOPT_POST, count($data));
   curl_setopt($post, CURLOPT_POSTFIELDS, $data);
   curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);

   $result = curl_exec($post);

   curl_close($post);

   return($result);

}






}






?>
