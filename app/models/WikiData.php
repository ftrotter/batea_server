<?php


class WikiData extends VeryMongo{

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

}

?>
