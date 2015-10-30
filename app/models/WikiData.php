<?php


class WikiData extends VeryMongo{

	public $run_silent = false;


/**  
 * Given a particular title of a wikipage, download the JSON representation...  
 * if you pass in a page cache, then it will be used.. if you dont then it will force a new download  
 * the function runs silently by default  
 */  
public static function makeFromAPI($title,$id_to_get = null,&$cache_to_use = null,$run_slow = true,$run_silent = true){  
  
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
                $result_array = WikiData::rawDownload($api_url);
  
                if(strlen($result_array['result']) < 20){
                        //then there must be a rate limiting problem
                        if(!$run_silent){ echo "I got $result \n\n Now Slowing down and retrying call\n"; }
                        sleep(5);
                        return WikiData::makeFromAPI($title,$id_to_get,$cache_to_use,$run_slow,$run_silent);
                }
  
                if($result_array['is_success']){
                        $WikiData = new WikiData();
                        $WikiData->fromJSON($result_array['result']);
                        $wikidata_id = WikiData::get_wikidata_id($title,$cache_id);
 
			$wikitext = WikiData::get_wikitext_from_json($result_array['result']);
			$revision_id = WikiData::get_revision_from_json($result_array['result']);
			$WikiData->data_array['title'] = $title;
			$WikiData->data_array['revision_id'] = $revision_id;
			$WikiData->data_array['wikitext'] = $wikitext;
                        $WikiData->sync($wikidata_id);
 
                        //which is why this is pass by refernce..
                        if(!is_null($cache_to_use)){
                                $cache_to_use[$title][$cache_id] = $result;
                        }
		
			return($WikiData);
 
               }else{
			return(false);
		}
  
  
} 


	function getWikiText($wikititle = null, $revision_id = 0){

		$page_array = $this->getPageArray($wikititle,$revision_id);

		return($page_array['wikitext']);
		
	}
/*
	traverses the json returned by the wikipedia api to get the 
	actual data array for a page...
	


*/
	function getPageArray($wikititle = null, $revision_id = 0){

		if(is_null($wikititle) || strlen($wikititle) == 0){
			if(count($this->data_array) <= 2){
				echo "WikiData: I either need a wikititle to load or I need to have a preloaded wiki text...\n";
				exit();
			}
		}else{
			$my_id = WikiData::get_wikidata_id($wikititle,$revision_id);
			$this->sync($my_id);			
		}

		//OK here we have data!!
		if(isset($this->data_array['query']['pages'])){
			$pages = $this->data_array['query']['pages'];
		}else{
			$problem_child = var_export($this->data_array,true);
			echo "What the hell?? wikititle = $wikititle\n";
			file_put_contents(app_path().'/storage/wikis/'."$wikititle.out",$problem_child);
			return(array('wikitext' => ''));
		}
		//we dont know the name of the actual id to this array, but there is just the one so...
		$page_array = array_pop($pages);

		if(!isset($page_array['revisions'])){
		//probably asked for a "File:... didnt we..
			$return_array = array('wikitext' => '');
			return($return_array);
		}


		//we dont want revisions considered at this level..
		$revision_array = $page_array['revisions'][0];
		$revision_array = array_pop($revision_array);
		unset($page_array['revisions']);
		$page_array['wikitext'] = $revision_array;
	//	unset($page_array['*']);
	//	$page_array = array_merge($page_array,$revision_array);

		return($page_array);

	}

/*
* Not static functions above...
*------------------------------------------------
* Static functions below...
*/

/*
        A function that understands how we combine titles and revision ids to index our collection of wiki pages
        Its simple, just append a '|' and then the revision id to the wikititle.
        But then use 0 for the revision id for the "latest" version...
        How do we keep them up to date? Hmm, good question...
        I wish the wikipedia API gave you the latest revision number along with the page id when it returned..
*/
        static function get_wikidata_id($title,$revision_id){
                $wikidata_id = "$title"."|$revision_id";
                return($wikidata_id);
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
        static public function wikiurlencode($title){
                $wikititle = str_replace(' ','_',$title);
                $wikititle = ucwords($wikititle);
                        //  I cannot seem to find where this happens in the wikipedia code
                        // but every time I use a lower case it is redirected to the capital version.
                        // I actually this this happens as arguments are being
                        // passed into of the factory methods in Title.php      
                $wikititle = WikiData::wfUrlencode($wikititle);
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

static function wfUrlencode( $s ) {
        static $needle;

        if ( is_null( $s ) ) {
                $needle = null;
                return '';
        }

        if ( is_null( $needle ) ) {
                $needle = array( '%3B', '%40', '%24', '%21', '%2A', '%28', '%29', '%2C', '%2F' );
 /*               if ( !isset( $_SERVER['SERVER_SOFTWARE'] ) ||
                        ( strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS/7' ) === false )
                ) {
                        $needle[] = '%3A';
                }
*/
		//Fred did this... because I really want a static function...
                        $needle[] = '%3A';
		
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
        Given the json that comes from the API, get the wikitext...
*/
        static function get_wikitext_from_json($json){
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

	static function get_revision_from_json($json){
                $wiki_data = json_decode($json,true);

        //echo "<pre>";
        //var_export($wiki_data);       
        //echo "</pre>";

                if(isset($wiki_data['query']['pages'])){

                        $page_array = $wiki_data['query']['pages'];
                        //we don't know the page id, so lets pop instead..
                        $page = array_pop($page_array);

                        if(isset($page['revisions'][0]['revid'])){
                                $revision_id = $page['revisions'][0]['revid']; //does this work?
                                return $revision_id;
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
static function compress_wikitext_templates($start,$end,$wiki_text){

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
        if this is any kind of template return true.
        if it is a normal line return false...
*/
        static function is_wikiline_template($wikiline){

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


/*
//returns false if not a heading line
//returns the name of the heading if it is a heading
*/
        static function is_wikiline_heading($line){
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
        determines if a wikiline is a citation...
*/
        static function is_wikitemplate_citation($wikiline){

                //this only works on templates...

		//just to rule out false == 0 shenannigans
                $is_citation = strpos('  '.strtolower($wikiline),'cite ');
                if($is_citation !== false){ //because it will often be '0'
                        return(true);
                }else{
                        return(false);
                }
        }


/*
        does this wikitext contain a journal citation
*/
        static function is_wikitemplate_journal_citation($wikiline){
                if(!WikiData::is_wikitemplate_citation($wikiline)){
                        return(false); //cant be a web citation if its not a citation..
                }
                $is_journal = strpos(strtolower($wikiline),'journal');

                return($is_journal);
        }


/*
        does this wikitext contain a book citation
*/
        static function is_wikitemplate_book_citation($wikiline){
                if(!WikiData::is_wikitemplate_citation($wikiline)){
                        return(false); //cant be a web citation if its not a citation..
                }

                $is_web = strpos(strtolower($wikiline),'book');
                return($is_web);

        }


/*
        does this wikitext contain a web citation
*/
        static function is_wikitemplate_web_citation($wikiline){
                if(!WikiData::is_wikitemplate_citation($wikiline)){
                        return(false); //cant be a web citation if its not a citation..
                }

                $is_web = strpos(strtolower($wikiline),'web');
                return($is_web);

        }


/*
        Attempts to get all of the simple links from a line of wikitext
        this is tricky because there are links inside templates...
        so we eliminate these first...
*/
        static function get_citation_templates_from_wikiline($wikiline){

                                $regex = "/\{\{(.*?)\}\}/"; //should catch everything inside double curly braces..
                                preg_match_all($regex,$wikiline,$matches);
                                if(count($matches[0]) != 0){ //there were some templates here
                                        return($matches[1]); //lets return the contents of the templates..
                                }else{
                                        return(array()); //nothing here return empty array
                                }

        }

/*
 * Takes a look at the json results from a wiki call, and determines if it is a redirect.
 */
static function is_redirect_from_json($wiki_json){
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
static function parse_redirect_from_json($wiki_json){

        preg_match_all('/\[\[(.+?)\]\]/u',$wiki_json,$matches); // find any string inside the [[ ]] which form wiki links...

        if(!isset($matches[1][0])){
                echo json_encode(array('result' => 'error','problem' => 'regex fail on redirect'));
                echo "REDIRECT PARSE ERRORS\n";
                exit();
        }

        $new_string = $matches[1][0];

        return($new_string); //we return only the first match... 

}

/*
*  Given the title and revision id of a wiki article, get its api url..
*
*/
static function get_wiki_api_url($title,$revision_id = null){

                if(is_null($revision_id)){
                        //we do nothing
                        $url_parameters = "&titles=$title";
                }else{
                        $url_parameters = "&revids=$revision_id";
                }
	
		//this will return revision ids too!!
		$api_url = "https://en.wikipedia.org/w/api.php?action=query&prop=revisions&rvprop=content|ids&format=json$url_parameters";

                return($api_url);
}

/*
 * Given a url, this function returns the title of the Wikipage that is then useful for further API calls
 */
        static function get_wiki_title_from_url($url){

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

/*
        stub for something that will take alink and return the label if there is one, false if there isnt
        it would be coolish if this could take it in the form '[[heart attack|Myocardial infarction]]'
        or 'heart attack|Myocardial infarction' and still work
*/
        static function get_link_label($link){

                echo "WikiScrapper get_link_label() This is a stub, dont use me";
                exit();
        }





        static function get_all_pmids_from_wikiline($wikiline){

                $potential_templates = WikiData::get_citation_templates_from_wikiline($wikiline);
                $return_array = array();
                foreach($potential_templates as $this_template){


                        $possible_pmid = WikiData::get_pmid_from_citation_template($this_template);
                        if($possible_pmid){
                                //then this is pmid...
                                $return_array[] = $possible_pmid;
                        }else{
				//echo "no pmid found\n";
			}

                }

                return($return_array);

        }




/*
        give a journal citation... return the pmid if there is one
        return false if its not there
        It is also possible to convert from various other types of ids to pmids here: 
http://www.ncbi.nlm.nih.gov/pmc/tools/id-converter-api/
*/
        static function get_pmid_from_citation_template($wikitext){

                        if(!WikiData::is_wikitemplate_journal_citation($wikitext)){
                                return(false); //cant very well get a pmid from wikitext that is not a journal citation
                        }

                        $citation_array = explode('|',$wikitext);
                        $found_one = false;
                        foreach($citation_array as $citation_block){
                                if(strpos(strtolower($citation_block),'pmid') !== false){
                                        //then this is the pmid = XXXX block...
                                        $citation_block = str_replace(' ','',$citation_block);//remove all whitespace
					if(strpos($citation_block,'=') !== false){
                                        	list($trash,$pmid) = explode('=',$citation_block);
					}else{
						echo "Citation block is does not have an equal with $citation_block\n";
						$pmid = false;
					}	
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
							//how can I make this silent? its static...
							echo "thought I found a pmid of '$pmid' in \n\n$wikitext\n\n but failed\n";
							
                                                }
                                        }

                                }
                        }

                        return(false); //means I did not find a pmid... don't even need $found_one..

        }

/**  
 *      The raw download function. Given an api_url, try to download it  
 *      return an array with is_success and result  
 */  
        public static function rawDownload($api_url){  
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






/**
 *	This is how we get HTML from a given wikitext.
 */
public static function getHtmlFromWikitext($wikitext){


        if(strlen($wikitext) == 0){
                return(''); //the translation of nothing is nothing...  
        }

        $parsoid_data = array(
                'wt' => $wikitext,
                'body' => 1,
        );

        $parsoid_url = "https://parsoid-lb.eqiad.wikimedia.org/enwiki/";
        $parsoid_html = WikiData::postToUrl($parsoid_url,$parsoid_data);
        return($parsoid_html);   
}

/*  
 Generic function to post to a url so that we can call parsoid...  
*/
public static function postToUrl($url, $data) {

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
