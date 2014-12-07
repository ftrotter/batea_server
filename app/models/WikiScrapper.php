<?php



class WikiScrapper{

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
	its
	https://en.wikipedia.org/wiki/Cushing%27s_syndrome	
	not
	https://en.wikipedia.org/wiki/Cushing's syndrome
	this one takes a link that might appear in a wikitext link and converts it into a wikititle that you can 
	stick onto a url...
	probably should just copy this function straight from the wikipedia sourcecode...	
	this is not just a better name for wfUrlencode
*/
	public function wikiurlencode($title){
		$wikititle = str_replace(' ','_',$title);
		$wikititle = ucwords($wikititle); 
			//  I cannot seem to find where this happens in the wikipedia code
			// but every time I use a lower case it is redirected to the capital version.
			// I actually this this happens as arguments are being
			// passed into of the factory methods in Title.php	
		$wikititle = $this->wfUrlencode($wikititle);
		return($wikititle);
	}

/**
 *This is copied verbatum (even the weird IIS stuff) from the includes/GlobalFunctions.php
 * of the mediawiki sourcecode. I am also copying in the comments so you can see the thinking..
 * This should handle all of the strange little cases correctly... 
 * 
 *Not that the underscore replacement does not happen in includes/GlobalFunctions.php
 * but in Title.php makeTitle function. Confusing as hell.
 *
 *
 * We want some things to be included as literal characters in our title URLs
 * for prettiness, which urlencode encodes by default.  According to RFC 1738,
 * all of the following should be safe:
 *
 * ;:@&=$-_.+!*'(),
 *
 * But + is not safe because it's used to indicate a space; &= are only safe in
 * paths and not in queries (and we don't distinguish here); ' seems kind of
 * scary; and urlencode() doesn't touch -_. to begin with.  Plus, although /
 * is reserved, we don't care.  So the list we unescape is:
 *
 * ;:@$!*(),/
 *
 * However, IIS7 redirects fail when the url contains a colon (Bug 22709),
 * so no fancy : for IIS7.
 *
 * %2F in the page titles seems to fatally break for some reason.
 *
 * @param string $s
 * @return string
 */
function wfUrlencode( $s ) {
        static $needle;

        if ( is_null( $s ) ) {
                $needle = null;
                return '';
        }

        if ( is_null( $needle ) ) {
                $needle = array( '%3B', '%40', '%24', '%21', '%2A', '%28', '%29', '%2C', '%2F' );
                if ( !isset( $_SERVER['SERVER_SOFTWARE'] ) ||
                        ( strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS/7' ) === false )
                ) {
                        $needle[] = '%3A';
                }
        }

        $s = urlencode( $s );
        $s = str_ireplace(
                $needle,
                array( ';', '@', '$', '!', '*', '(', ')', ',', '/', ':' ),
                $s
        );

        return $s;
}





/*
	Determines if a given title is a redirect
	if it is it returns the redirected url
	if not it returns false..
	unless the return_the_title is set true then it just returns the wikititle that was passed in..
*/
	public function get_redirect($title,$return_the_title = false){

		$title = $this->wikiurlencode($title);

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
                while($this->is_redirect($wiki_api_json)){ //sometimes wiki pages are just stubs that redirect
			//I cannot seem to get to the bottom of this pile...
                        $redirect_to = $this->parse_redirect($wiki_api_json); //this returns the title that the orginal title redirects to..
			if(strcmp($redirect_to,$last_redirect) == 0){
				//then we are in an infinite loop because is_redirect is stupid
				var_export($wiki_api_json);
				echo "REDIRECT LOOP!!!\n";
				continue;
			}
		//		var_export($wiki_api_json);
			$redirect_to = $this->wikiurlencode($redirect_to);
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

	

/*
	gets talk pages and then ensures that if they are medical projects
	that we have them in our data cache as such...
*/
	public function get_clean_talkpage($title,$id_to_get = null){
		$results =  $this->get_clean_wikitext("Talk:$title",$id_to_get);
		
		$this->wpmed_cache[$title] = false;
		if(strpos($results,"WPMED") !== false){
				//then this is a wikiproject medicine project!!!
			//echo "CLINICAL Found WPMED \n";
			echo 'c';
			$this->wpmed_cache[$title] = true;	
		}
		if(strpos(strtolower($results),"wikiproject medicine") !== false){
				//then this is a wikiproject medicine project!!!
			//echo "CLINICAL Found wikiproject medicine\n";
			$this->wpmed_cache[$title] = true;	
		}
	
		$wikitag_id = $this->get_wikidata_id($title,$id_to_get);
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

		if($this->is_redirect($wiki_api_json)){ //sometimes wiki pages are just stubs that redirect
                                        //the web user just sees the right page...
                                        //but the API actually returns the redirect...
                        $redirect_to = $this->parse_redirect($wiki_api_json); //this returns the title that the orginal title redirects to..
                        $wiki_api_json = $this->download_wiki_result($redirect_to); //this returns the wiki_json for the right title.
                }

		$uncompressed_wiki_text = $this->get_wikitext_from_json($wiki_api_json);
		if($uncompressed_wiki_text){ //returns false on fail after all..
			$compressed_wiki_text = $this->compress_wikitext_templates('{{','}}',$uncompressed_wiki_text);
        		$compressed_wiki_text = $this->compress_wikitext_templates('{|','|}',$compressed_wiki_text);
			return($compressed_wiki_text);
		}else{
			return(false);
		}
	}
/*
 * Given a particular title of a wikipage, download the JSON representation...
 */
public function download_wiki_result($title,$id_to_get = null){

		//lets not download a wikipage twice in a run at least...
		if(is_null($id_to_get)){
			$cache_id = 0;
		}else{
			$cache_id = $id_to_get;
		}


		if(isset($this->wiki_json_cache[$title][$cache_id])){
			//echo "returning cache for $title\n";
			echo 'w';
			return($this->wiki_json_cache[$title][$cache_id]);
		}

		echo "\t\tdownloading $title\n";
		sleep(1); //lets slow this down.

                $api_url = $this->get_wiki_api_url($title,$id_to_get);
		$result = $this->wikipedia_raw_download($api_url); 

		if(strlen($result) < 20){
			//then there must be a rate limiting problem
			echo "I got $result \n\n Now Slowing down and retrying call\n";
			sleep(5);
			return $this->download_wiki_result($title,$id_to_get);
		}


		$WikiData = new WikiData();
		$WikiData->fromJSON($result);
		$wikidata_id = $this->get_wikidata_id($title,$cache_id);
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
               	        exit('cURL Error: '.curl_error($ch));
                }

		return($result);
	}


	function get_wikidata_id($title,$cache_id){
		$wikidata_id = "$title"."|$cache_id";
		return($wikidata_id);
	}
	

/*
	Given the json that comes from the API, get the wikitext...
*/
        function get_wikitext_from_json($json){
                $wiki_data = json_decode($json,true);
 
        //echo "<pre>";
        //var_export($wiki_data);       
        //echo "</pre>";

                if(isset($wiki_data['query']['pages'])){

                        $page_array = $wiki_data['query']['pages'];
                        //we don't know the page id, so lets pop instead..
                        $page = array_pop($page_array);

                        if(isset($page['revisions'][0]['*'])){
                                $wiki_text = $page['revisions'][0]['*']; //does this work?
                                return $wiki_text;
                        }else{
                                return(false);
                        }
                }else{
                        return(false);
                }

        }
/*
	Given template start and end characters (i.e. {{ and }})
	and some wikitext
        Compress the templates to single lines, returning valid wikitext, but that has the templates compressed
	Makes later parsing much simpler...

*/
function compress_wikitext_templates($start,$end,$wiki_text){

        $wiki_lines = explode("\n",$wiki_text);
        $total_diff = 0;
        $new_wiki_text = '';
        foreach($wiki_lines as $line_number => $this_line){

                if($total_diff > 0){
                        $nl = "";
                }else{
                        $nl = "\n";
                }

                $opencurly_count = substr_count($this_line, $start);
                $closecurly_count = substr_count($this_line, $end);

                if($opencurly_count == 0 && $closecurly_count ==0){
                        $new_wiki_text .= "$nl$this_line";
                        continue;
                }

                if($opencurly_count == $closecurly_count){
                        //echo "line $line_number has $opencurly_count template<br>";
                        $new_wiki_text .= "$nl$this_line";
                }else{

                        $diff = $opencurly_count - $closecurly_count;
                        $total_diff = $total_diff + $diff;
                       // echo "This $line_number has a diff of $diff with a running total of $total_diff <br>";
                       // echo "$this_line<br>";
                        $new_wiki_text .= "$nl$this_line";
                }
        }

        return($new_wiki_text);
}

/*
	stub for something that will take alink and return the label if there is one, false if there isnt
	it would be coolish if this could take it in the form '[[heart attack|Myocardial infarction]]'
	or 'heart attack|Myocardial infarction' and still work
*/
	function get_link_label($link){

		echo "WikiScrapper get_link_label() This is a stub, dont use me";
		exit();
	}


	function get_medical_links_from_wikiline($wikiline){

		$all_links = $this->get_links_from_wikiline($wikiline);
		$return_me = array();
		foreach($all_links as $label => $wikititle){

			$this->get_clean_talkpage($wikititle);
			//which sets the wpmed cache...
			if($this->wpmed_cache[$wikititle]){
				$return_me[$wikititle] = $wikititle;
			}
		}

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
						}else{
							//the label is the same as the page title...
							$label = "$this_line_text";
						}
						$return_the_title_rather_than_false = true;	
						$results_array[$label] = $this->get_redirect($this_line_text,$return_the_title_rather_than_false);
					}
					return($results_array);
                                }else{
					return(array()); //nothing here return empty array
				}
        }


	function get_all_pmids_from_wikiline($wikiline){

		$potential_templates = $this->get_citation_templates_from_wikiline($wikiline);
		$return_array = array();
		foreach($potential_templates as $this_template){
			$possible_pmid = $this->get_pmid_from_citation_template($this_template);
			if($possible_pmid){
				//then this is pmid...
				$return_array[] = $possible_pmid;
			}
	
		}

		return($return_array);

	}

/*
        Attempts to get all of the simple links from a line of wikitext
        this is tricky because there are links inside templates...
        so we eliminate these first...
*/
	function get_citation_templates_from_wikiline($wikiline){

		                $regex = "/\{\{(.*?)\}\}/"; //should catch everything inside double curly braces..
                                preg_match_all($regex,$wikiline,$matches);
                                if(count($matches[0]) != 0){ //there were some templates here
                                        return($matches[1]); //lets return the contents of the templates..
                                }else{
					return(array()); //nothing here return empty array
				}

	}



/*
	give a journal citation... return the pmid if there is one
	return false if its not there
	It is also possible to convert from various other types of ids to pmids here: 
http://www.ncbi.nlm.nih.gov/pmc/tools/id-converter-api/
*/
	function get_pmid_from_citation_template($wikitext){
	
			if(!$this->is_wikiline_journal_citation($wikitext)){
				return(false); //cant very well get a pmid from wikitext that is not a journal citation
			}

                        $citation_array = explode('|',$wikitext);
			$found_one = false;
                        foreach($citation_array as $citation_block){
                                if(strpos(strtolower($citation_block),'pmid') !== false){
                                        //then this is the pmid = XXXX block...
                                        $citation_block = str_replace(' ','',$citation_block);//remove all whitespace
                                        list($trash,$pmid) = explode('=',$citation_block);
                			//echo "The PMID is '$pmid'. Whos is a badass?<br>";

                                        if(is_numeric($pmid)){
						$found_one = true;
						return($pmid);

                                        }else{
						if(strlen(trim($pmid)) == 0){
							//then its just not there		
							//it was only the tag..
						}else{
							//then there is "something" there...
                                               	 	//this is a shitty pmid... I have no idea what to make of it...
							echo "thought I found a pmid of '$pmid' in \n\n$wikitext\n\n but failed\n";
						}
                                        }

                                }
                        }		

			return(false); //means I did not find a pmid... don't even need $found_one..

	}
/*
        does this wikitext contain a journal citation
*/
        function is_wikiline_journal_citation($wikiline){
                if(!$this->is_wikiline_citation($wikiline)){
                        return(false); //cant be a web citation if its not a citation..
                }
                $is_journal = strpos(strtolower($wikiline),'journal');
                return($is_journal);
        }


/*
	does this wikitext contain a book citation
*/
        function is_wikiline_book_citation($wikiline){
                if(!$this->is_wikiline_citation($wikiline)){
                        return(false); //cant be a web citation if its not a citation..
                }

                $is_web = strpos(strtolower($wikiline),'book');
                return($is_web);

        }


/*
	does this wikitext contain a web citation
*/
	function is_wikiline_web_citation($wikiline){
		if(!$this->is_wikiline_citation($wikiline)){
			return(false); //cant be a web citation if its not a citation..
		}

		$is_web = strpos(strtolower($wikiline),'web');
		return($is_web);

	}



/*
	determines if a wikiline is a citation...
*/
	function is_wikiline_citation($wikiline){

		//this only works on templates...

                $is_citation = strpos(strtolower($wikiline),'cite ');
                if($is_citation !== false){ //because it will often be '0'
			return(true);
		}else{
			return(false);
		}
	}

/*
//returns false if not a heading line
//returns the name of the heading if it is a heading
*/
        function is_wikiline_heading($line){
                $heading_regex_array = array(
                        6 => "/^======(.+?)======$/m",                                  // SubSubSubsubheading
                        5 => "/^=====(.+?)=====$/m",                                    // SubSubsubheading
                        4 => "/^====(.+?)====$/m",                                              // Subsubheading
                        3 => "/^===(.+?)===$/m",                                                // Subheading
                        2 => "/^==(.+?)==$/m",                                          // Heading
                        1 => "/^=(.+?)=$/m",                                            // Heading
                        );

                $is_heading = false;
                foreach($heading_regex_array as $level => $this_regex){

                        $is_heading = preg_match($this_regex,$line,$matches);

                        if($is_heading){
                                return(trim($matches[1]));
                        }


                }

                return($is_heading);

        }



/*
	if this is any kind of template return true.
	if it is a normal line return false...
*/
        function is_wikiline_template($wikiline){

                //if the line begins with {{ it is a template line, and infobox or something..
                //not a normal line...

                if(strpos($wikiline,'{{') === 0){
                        return(true);
                }

                if(strpos($wikiline,'{|') === 0){
                        return(true);
                }

                return(false);

        }


function get_html_from_wikitext($wikitext){


	if(strlen($wikitext) == 0){
		return(''); //the translation of nothing is nothing...
	}

        $parsoid_data = array(
                'wt' => $wikitext,
                'body' => 1,
        );	

	$parsoid_url = "http://parsoid-lb.eqiad.wikimedia.org/enwiki/";
	$parsoid_html = $this->post_to_url($parsoid_url,$parsoid_data);
	return($parsoid_html);
}

/*
 Generic function to post to a url so that we can call parsoid...
*/
function post_to_url($url, $data) {
   $fields = '';
   foreach($data as $key => $value) {
      $fields .= $key . '=' . $value . '&';
   }
   rtrim($fields, '&');

   $post = curl_init();

   curl_setopt($post, CURLOPT_URL, $url);
   curl_setopt($post, CURLOPT_POST, count($data));
   curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
   curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);

   $result = curl_exec($post);

   curl_close($post);

   return($result);

}



/*
 * Takes a look at the json results from a wiki call, and determines if it is a redirect.
 */
function is_redirect($wiki_json){
        $redirect_string = '"#REDIRECT ';
        if(strpos($wiki_json,$redirect_string) !== false){
                return(true); // we found the string, which means this is a redirect file...
        }else{
                return(false);
        }
}


/*
 * if a given json is a redirect, get the place it redirects to and return the title for that page
 */
function parse_redirect($wiki_json){

        preg_match_all('/\[\[(.+?)\]\]/u',$wiki_json,$matches); // find any string inside the [[ ]] which form wiki links...

        if(!isset($matches[1][0])){
                echo json_encode(array('result' => 'error','problem' => 'regex fail on redirect'));
		echo "REDIRECT PARSE ERRORS\n";
                exit();
        }

        $new_string = $matches[1][0];

        return($new_string); //we return only the first match... 

}


function get_wiki_api_url($title,$revision_id = null){

                if(is_null($revision_id)){
                        //we do nothing
                        $url_parameters = "&titles=$title";
                }else{
                        $url_parameters = "&revids=$revision_id";
                }

                $api_url = "http://en.wikipedia.org/w/api.php?format=json&action=query$url_parameters";
                $api_url .= "&prop=revisions&rvprop=content";

                return($api_url);
}




/*
 * Given a url, this function returns the title of the Wikipage that is then useful for further API calls
 */
        function get_wiki_title($url){

                $url_array = explode('/',$url);

//                $title = array_pop($url_array); //this does not work for things like HIV/AIDS

                $the_http = array_shift($url_array);
                $nothing = array_shift($url_array);
                $domain = array_shift($url_array);

                $the_word_wiki = array_shift($url_array);
                $title = implode('/',$url_array); //should account for HIV/AIDS


                if(strpos($domain,'wikipedia') !== false){
                        return($title);
                }else{
                        return(false);
                }

        }






}






?>
