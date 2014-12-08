<?php


class WikiData extends VeryMongo{


	function getWikiText($wikititle = null, $revision_id = 0){

		$page_array = $this->getPageArray($wikititle,$revision_id);

	}
/*
	traverses the json returned by the wikipedia api to get the 
	actual data array for a page...
	


*/
	function getPageArray($wikititle = null, $revision_id = 0){

		if(is_null($wikititle)){
			if(count($this->data_array) == 0){
				echo "WikiData: I either need a wikititle to load or I need to have a preloaded wiki text...\n";
				exit();
			}
		}else{
			$my_id = WikiData::get_wikidata_id($wikititle,$revision_id);
			$this->sync($my_id);			
		}

		//OK here we have data!!

		$pages = $this->data_array['query']['pages'];
		//we dont know the name of the actual id to this array, but there is just the one so...
		$page_array = array_pop($pages);

		//we dont want revisions considered at this level..
		$revision_array = $page_array['revisions'][0];
		unset($page_array['revisions']);
		$page_array = array_merge($page_array,$revision_array);

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
        static function is_wikiline_citation($wikiline){

                //this only works on templates...

                $is_citation = strpos(strtolower($wikiline),'cite ');
                if($is_citation !== false){ //because it will often be '0'
                        return(true);
                }else{
                        return(false);
                }
        }


/*
        does this wikitext contain a journal citation
*/
        function is_wikiline_journal_citation($wikiline){
                if(WikiData::is_wikiline_citation($wikiline)){
                        return(false); //cant be a web citation if its not a citation..
                }
                $is_journal = strpos(strtolower($wikiline),'journal');
                return($is_journal);
        }


/*
        does this wikitext contain a book citation
*/
        function is_wikiline_book_citation($wikiline){
                if(!WikiData::is_wikiline_citation($wikiline)){
                        return(false); //cant be a web citation if its not a citation..
                }

                $is_web = strpos(strtolower($wikiline),'book');
                return($is_web);

        }


/*
        does this wikitext contain a web citation
*/
        function is_wikiline_web_citation($wikiline){
                if(!WikiData::is_wikiline_citation($wikiline)){
                        return(false); //cant be a web citation if its not a citation..
                }

                $is_web = strpos(strtolower($wikiline),'web');
                return($is_web);

        }



/*
        give a journal citation... return the pmid if there is one
        return false if its not there
        It is also possible to convert from various other types of ids to pmids here: 
http://www.ncbi.nlm.nih.gov/pmc/tools/id-converter-api/
*/
        static function get_pmid_from_citation_template($wikitext){

                        if(!WikiData::is_wikiline_journal_citation($wikitext)){
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











}



?>
