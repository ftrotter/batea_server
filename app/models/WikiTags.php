<?php


class WikiTags extends VeryMongo{

	static public $talk_tags_maps = [
                'is_WPMED' => '{{WPMED',
                'is_vital' => '{{Vital',
                'is_Anatomy' => '{{WikiProject Anatomy',
                'is_Medicine' => '{{WikiProject Medicine',
		];

        static public $talk_is_clinical_tags = [
                '{{WPMED',
                '{{WikiProject Anatomy',
                '{{WikiProject Medicine',
                '{{WikiProject Computational Biology',
                '{{Wikiproject MCB',
                '{{EvolWikiProject}}',
                '{{WikiProject Pharmacology',
                ];


	static public $article_is_clinical_tags = [
"{{Infobox disease",
"{{Infobox anatomy",
"{{Infobox symptom",
"{{Infobox scientist",
"{{chembox",
"GraySubject",
"{{ICD10",
"{{ICD9",
"MedlinePlus=",
"eMedicineSubj=",
"eMedicineTopic",
"MeshNumber",
"DorlandsID",
"[[Category:Organs]]",
"{{Animal anatomy}}",
"MedlinePlus",
"[[Category:Symptoms and signs:",
"|geneid=",  
"{{Human homeostasis}}",
"{{Blood tests}}",
"[[Category:Human homeostasis]]",
"[[Category:Blood",
"{{Expert-subject|Medicine",
"eMedicineTopic",
"{{MeshName",
"{{Major drug groups}}",
"{{Chromosome genetics}}",
"{{Biology nav}}",  
"[[Category:Auxology",
"[[Category:Anthropometry",
"[[Category:Immunology",
"[[Category:Autoimmune diseases",
"{{System and organs}}",
"{{Digestive glands}}",
"{{Endocrine system}}",
"{{endocrine_pancreas}}",  
"[[Category:Human pregnancy",
"[[Category:Birth control",
"[[Category:Reproduction in mammals",
"[[Category:Obstetrics",
"[[Category:Fertility",
"{{Pregnancy",
"{{Reproductive health",
"{{Reproductive physiology",
"{{Humandevelopment",
"[[Category:Global health",
"pathology}}",
"[[Category:Cognition",
"{{Viral diseases", 
"{{PBB",
"{{PDB Gallery",
"[[Category:Disability",
"[[Category:Podiatry", 
"[[Category:Orthopedic braces",
"[[Category:Orthopedics",
"[[Category:Skeletal system",
"[[Category:Muscular system",
"[[Category:Rehabilitation team",  
"[[Category:Orthopedic surgery",
"PubChem_Ref",
"ChemSpiderID",
"EINECS",
"KEGG_Ref",
"ChEMBL",
"ATCCode_",
"StdInChI",
"{{Biology",
"{{Biochemical",
"{{Infobox particle",
"[[Category:Chemical elements",
"[[Category:Drugs",
"{{MolBioGeneExp",
"{{Nucleic acids",
"{{Genetics",
"[[Category:DNA",  
"[[Category:Genetics",
"[[Category:Oaths of medicine",
"[[Category:Medical",
"[[Category:Philosophy of medicine",
"[[Category:Sequestering cells",
"[[Category:Human cells",
"proteins}}",
"[[Category:Keratins",
"[[Category:Cytoskeleton",
"[[Category:Skin",
"[[Category:Physiology",
"Molecular and cellular biology}}",
"[[Category:Ageing",
"[[Category:Cellular",
"[[Category:Gerontology",
"[[Category:Molecular",
"[[Category:Mutation",
"[[Category:DNA repair",
"[[Category:Senescence",
"{{Immune system",
"{{Lymphatic system",
"{{System and organs",
"{{Immune receptors",
"Biology|Medicine}}",
"Medicine|Biology}}",
"{{Diets",
"[[Category:Medical treatments",
"[[Category:Syndromes",
"[[Category:History of medicine",
"{{History of medicine",
"{{Protein topics",
"[[Category:Proteins",
"[[Category:Protein complexes",
"[[Category:Organelles",
"[[Category:Apoptosis",
"[[Category:Biology",
		];


	public static function isWikitextClinical($wiki_text){
	
		$wiki_text = strtolower($wiki_text);
		$reasons = [];
		$is_clinical = false;
		foreach(WikiTags::is_clinical_tags as $term){
			$term = strtolower($term);
			if(strpos($wiki_text,$term) !== false){	//we find the term, in the wiki text, so this is clinical
				$is_clinical = true;
				$reasons[] = $term;
			}
		}

		$return_me = ['is_clinical' => $is_clinical];
		if(count($reasons) > 0){
			$return_me['reasons'] = $reasons;
		}

		return($return_me);
	

	}

	public static function isTitleClinical($title){
	
		//check our DB first..
		$results = WikiTags::isTitleClinicalFromDB($title);
		if($results['is_success']){	//then we found the answer in the DB!!
			return($results);
		}

		//getting here means that we do not have a record for this title in our 
		//database yet... lets use the API to generate a full DB entry...

		//check the API next, and save it to the DB.
		$results = WikiTags::isTitleClinicalFromAPI($title);
		
		//because this is the last thing we are going to try...
		//we can just return these results..
		return($results);
	
	}

/**
 *	returns an array of is_success and is_clinical 
 *	This runs generateWikiTagFromAPI and then returns
 *	the resulting is_titleclinical
 */
	public static function isTitleClinicalFromAPI($title){

		$return_me = [];

		$newWikiTag = WikiTags::generateWikiTagFromAPI($title);
		if(isset($newWikiTag->data_array['is_titleclinical'])){
			$return_me['is_titleclinical'] = $newWikiTag->data_array['is_titleclinical'];
			$return_me['is_success'] = true;
		}else{
			$return_me['is_success'] = false;
		}

		return($return_me);
	}

/**
 *	Use the API to generate an entry in WikiTags
 *	given a wikipedia title
 *	Saves it to the database and then 
 * 	returns a WikiTag object
 */
	public static function generateWikiTagFromAPI($title){

	//	$wiki_text = strtolower(WikiScrapper::get_clean_wikitext($title));
	//	$talk_text = strtolower(WikiScrapper::get_clean_talkpage($title));

		$articleWikiData = WikiData::makeFromAPI($title);		
		$talkWikiData = WikiData::makeFromAPI("Talk:$title");		
		
		if(!is_object($articleWikiData)){
			return(array('is_succcess' => false));
		}
		if(!is_object($talkWikiData)){
			return(array('is_succcess' => false));
		}

		$talk_text = strtolower($talkWikiData->data_array['wikitext']);
		$article_text = strtolower($articleWikiData->data_array['wikitext']);
		$revision_id = $articleWikiData->data_array['revision_id'];

		//echo "Just got $talk_text, $article_text with revision of $title $revision_id\n";

		//first, lets generate the core tags
		foreach(WikiTags::$talk_tags_maps as $tag => $search_string){
			$search_string = strtolower($search_string);
                        if(strpos($talk_text,$search_string) !== false){
                                $wiki_tag_array[$tag] = true;           
                        }else{
                                $wiki_tag_array[$tag] = false;          
                        }
		}	

		$WT = new WikiTags();
		$WT->data_array = $wiki_tag_array;

		$is_titleclinical = false;
		$because = [];
		foreach(WikiTags::$talk_is_clinical_tags as $search_string){
			$search_string = strtolower($search_string);
                        if(strpos($talk_text,$search_string) !== false){
                                $because[] = "Talk had: $search_string";           
				$is_titleclinical = true;
                        }
		}
	
		foreach(WikiTags::$article_is_clinical_tags as $search_string){
			$search_string = strtolower($search_string);
                        if(strpos($article_text,$search_string) !== false){
                                $because[] = "Article had: $search_string";           
				$is_titleclinical = true;
                        }
		}	


		if($is_titleclinical){
			$WT->data_array['is_clinical_because'] = $because;
		}
		$WT->data_array['is_titleclinical'] = $is_titleclinical;
		$WT->data_array['revision_id'] = $revision_id;	
		$WT->data_array['title'] = $title;
		//var_export($WT->data_array);

		$WT->sync("$title|$revision_id");
		
		return($WT);

	}

/**	
 *	returns an array of is_success and is_clinical 
 *	if it finds the is_titleclinical in the WikiTag Mongo collection 
 *
 */
	public static function isTitleClinicalFromDB($title){

		$return_me = [];

		$WT = new WikiTags();
		$WT->syncFromLatestTitle($title);
		if(isset($WT->data_array['is_titleclinical'])){
			$return_me['is_titleclinical'] = $WT->dataarray['is_titleclinical'];
			$return_me['is_success'] = true;
		}else{
			$return_me['is_success'] = false;
		}	

		return($return_me);

	}

/**
 *	Finds the lates version of a given title...
 *	And returns it.. 
 */
	public function syncFromLatestTitle($title){
		
		//first lets support the current '0' way of handling the current version...
		$wikidata_id = WikiData::get_wikidata_id($title,0);	

		$this->sync($wikidata_id);
		if(isset($this->data_array['title'])){
			return(true);
		}	

		//now lets search through all documents that have a matching title, and sync on the last one..	
		
		$search = ['title' => $title];
		$collection = $this->mongo->wikitags;
		$cursor = $collection->find($search);

		$highest_id = 0;
		$found_revision = false;
		$found_at_least_one = false;
		foreach($cursor as $thisWT){
			if(isset($thisWT->revision_id)){
				$found_revision = true;
				if($thisWT->revision_id > $highest_id){
					$highest_id = $thisWT->revision_id;
					$sync_me_id = $thisWT->wikitag_id;
				}	
			}
		}

		if($found_revision){
			$this->sync($sync_me_id);
			return(true);
		}else{
			//none of them are the revisioned... just return what you have..
			if($found_at_least_one){
				$this->sync($thisWT->data_array['wikitags_id']);
				return(true);
			}else{
				//then we do not have this title in the database
				return(false);
			}		

		}


	}


}//end class
?>
