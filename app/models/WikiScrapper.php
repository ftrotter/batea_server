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
	public function get_redirect($title,$return_the_title = false){

		$title = WikiData::wikiurlencode($title);

		if(isset($this->redirect_cache[$title])){
			if($this->redirect_cache[$title]){
				return($this->redirect_cache[$title]);
			}else{
				if($return_the_title){
					return($title);
				}else{
					return(false);
				}	
			}
		}


                $wiki_api_json = $this->download_wiki_result($title);
		$redirect_to = false;
		$last_redirect = $title;
                while(WikiData::is_redirect_from_json($wiki_api_json)){ //sometimes wiki pages are just stubs that redirect
			//I cannot seem to get to the bottom of this pile...
                        $redirect_to = WikiData::parse_redirect_from_json($wiki_api_json); //this returns the title that the orginal title redirects to..
			if(strcmp($redirect_to,$last_redirect) == 0){
				//then we are in an infinite loop because is_redirect_from_json is stupid
				var_export($wiki_api_json);
				if(!$this->run_silent){  echo "REDIRECT LOOP!!!\n"; }
				continue;
			}
		//		var_export($wiki_api_json);
			$redirect_to = WikiData::wikiurlencode($redirect_to);
			//echo "started with '$title' last run was '$last_redirect' now mining '$redirect_to'\n";
			$wiki_api_json = $this->download_wiki_result($redirect_to);
			$last_redirect = $redirect_to;
		}

		$this->redirect_cache[$title] = $redirect_to;	

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



	private $talk_tags_map = array(
		'is_WPMED' => '{{WPMED',
		'is_vital' => '{{Vital',
		'is_Anatomy' => '{{WikiProject Anatomy',
		'is_Medicine' => '{{WikiProject Medicine',
		);

	private $medical_page_filters = array(
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
*/
	public function get_clean_talkpage($title,$id_to_get = null){
		$results =  $this->get_clean_wikitext("Talk:$title",$id_to_get);
		
		$this->wpmed_cache[$title] = false;
		foreach($this->medical_page_filters as $this_filter){
			if(strpos($results,$this_filter) !== false){
					//then this is a wikiproject medicine project!!!
				//echo "CLINICAL Found WPMED \n";
				if(!$this->run_silent){ echo 'c'; }
				$this->wpmed_cache[$title] = true;	
			}
		}
	
		$wikitag_id = WikiData::get_wikidata_id($title,$id_to_get);
		foreach($this->talk_tags_map as $tag => $search_string){
			if(strpos($results,$search_string) !== false){
				$wiki_tag_array[$tag] = true;		
			}else{
				$wiki_tag_array[$tag] = false;		
			}
	
		}

		$WikiTags = new WikiTags();
		$WikiTags->data_array = $wiki_tag_array;
		$WikiTags->sync($wikitag_id);

	
		return($results);
	}


	public function get_clean_wikitext($title,$id_to_get = null){

		$wiki_api_json = $this->download_wiki_result($title,$id_to_get);

		if(WikiData::is_redirect_from_json($wiki_api_json)){ //sometimes wiki pages are just stubs that redirect
                                        //the web user just sees the right page...
                                        //but the API actually returns the redirect...
                        $redirect_to = WikiData::parse_redirect_from_json($wiki_api_json); //this returns the title that the orginal title redirects to..
                        $wiki_api_json = $this->download_wiki_result($redirect_to); //this returns the wiki_json for the right title.
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
 */
public function download_wiki_result($title,$id_to_get = null){

		if(strpos($title,'File:') !== false || strpos($title,'Image:') !== false){
			return("{result: 'NO FILE DOWNLOADS'}");//no idea what this will do, but I am not downloading files anymore...
		}


		//lets not download a wikipage twice in a run at least...
		if(is_null($id_to_get)){
			$cache_id = 0;
		}else{
			$cache_id = $id_to_get;
		}


		if(isset($this->wiki_json_cache[$title][$cache_id])){
			//echo "returning cache for $title\n";
			if(!$this->run_silent){ echo 'w'; }
			return($this->wiki_json_cache[$title][$cache_id]);
		}

		if(!$this->run_silent){ echo "\t\tdownloading $title\n"; }
		sleep(1); //lets slow this down.

                $api_url = WikiData::get_wiki_api_url($title,$id_to_get);
		$result = $this->wikipedia_raw_download($api_url); 

		if(strlen($result) < 20){
			//then there must be a rate limiting problem
			if(!$this->run_silent){ echo "I got $result \n\n Now Slowing down and retrying call\n"; }
			sleep(5);
			return $this->download_wiki_result($title,$id_to_get);
		}


		$WikiData = new WikiData();
		$WikiData->fromJSON($result);
		$wikidata_id = WikiData::get_wikidata_id($title,$cache_id);
		$WikiData->sync($wikidata_id);

		$this->wiki_json_cache[$title][$cache_id] = $result;		

                return($result);

}

	function wikipedia_raw_download($api_url){
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_USERAGENT,
                        'ClincalSpade/1.0 (http://www.fredtrotter.com/; fred.trotter@gmail.com)');

                curl_setopt($ch, CURLOPT_URL, $api_url);
                $result = curl_exec($ch);
                if (!$result) {
			echo "wikipedia_raw_download failed with $api_url\n"; 
			var_export(curl_getinfo($ch));
			echo "\n\n";
               	        exit('cURL Error: '.curl_error($ch));
                }

		return($result);
	}


	


	function get_medical_links_from_wikiline($wikiline,$depth = ''){

		if(!$this->run_silent){ echo "\n>"; }
		$all_links = $this->get_links_from_wikiline($wikiline);
		$return_me = array();
		foreach($all_links as $label => $wikititle){

			$this->get_clean_talkpage($wikititle);
			//which sets the wpmed cache...
			if($this->wpmed_cache[$wikititle]){
				$return_me[$wikititle] = $wikititle;
			}
		}
		if(!$this->run_silent){ echo "<\n";}
		return($return_me);

	}



/*
	Attempts to get all of the simple links from a line of wikitext
	this is tricky because there are links inside templates...
	so we eliminate these first...
*/
        function get_links_from_wikiline($wikiline){

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
					foreach($matches[1] as $this_line_text){
						if(strpos($this_line_text,'|')){ //then this has a label!!!
							//the label is different than the page title..
							list($this_line_text,$label) = explode('|',$this_line_text);
							if(strlen(trim($label)) ==0){
								//it had the | but nothing to thre right... it happens...
								$label = $this_line_text;
	
							}
						}else{
							//the label is the same as the page title...
							$label = $this_line_text;
						}
						$return_the_title_rather_than_false = true;	
						$this_redirect =  $this->get_redirect($this_line_text,$return_the_title_rather_than_false);
						if($this_redirect){
							$results_array[$label] = $this_redirect;
						}
					}
					return($results_array);
                                }else{
					return(array()); //nothing here return empty array
				}
        }



static function get_html_from_wikitext($wikitext){


	if(strlen($wikitext) == 0){
		return(''); //the translation of nothing is nothing...
	}

        $parsoid_data = array(
                'wt' => $wikitext,
                'body' => 1,
        );	

	$parsoid_url = "http://parsoid-lb.eqiad.wikimedia.org/enwiki/";
	$parsoid_html = WikiScrapper::post_to_url($parsoid_url,$parsoid_data);
	return($parsoid_html);
}

/*
 Generic function to post to a url so that we can call parsoid...
*/
static function post_to_url($url, $data) {

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
