<?php
/*
	A generic class that holds functions that cross data type boundaries
	But are still data related
*/


class MetaTools extends VeryMongo {


/**
 *	Returns an array containing every clinical wikipedia article that the system is aware of
 *	Including those that are downloaded from the project list
 *	those that are organically spidered and soon, those that are found by crawling categories
 */
	public function getBigClinicalArticleList(){

		$wikitag_collection = $this->mongo->wikitags;
		
		$tags = [
			'is_Medicine',
			'is_Anatomy',
			'is_titleclinical',
			'is_WPMED',
			];

		foreach($tags as $this_tag){		
			$wikitag_search = [$this_tag => true];
	
			$wikitag_cursor = $wikitag_collection->find($wikitag_search);

			$articles_to_return = [];
			foreach($wikitag_cursor as $thisWikitag){
				$articles_to_return[$thisWikitag['title']] = $thisWikitag['title'];
			}
		}

		$ProjectArticles = new ProjectArticles();

		$all_pa = $ProjectArticles->get_all();

		foreach($all_pa as $this_pa){
			$title = WikiData::wikiurlencode($this_pa['name']);
			$articles_to_return[$title] = $title;
		}	
		

		//Then the ProjectArticles list...

			return($articles_to_return);
	}
} 

?>
