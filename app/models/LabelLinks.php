<?php


class LabelLinks extends VeryMongo{


	var $big_cache;


	function buildData(){

		//first we need our database of medical titles
		//because we do not care about every relabeling..
		$WikiTags = new WikiTags();
		$all_wiki_tags = $WikiTags->get_all();
		$is_this_clinical = array();
		foreach($all_wiki_tags as $this_wiki_tag){
			$this_title = str_replace('|','',$this_wiki_tag['wikitags_id']);
			if($this_wiki_tag['is_Anatomy'] || $this_wiki_tag['is_Medicine'] || $this_wiki_tag['is_WPMED']){
				$is_this_clinical[$this_title] = true;
			}else{
				$is_this_clinical[$this_title] = false;
			}
		}



		$WikiStructure = new WikiStructure();
		$allPages = $WikiStructure->get_all();

		foreach($allPages as $this_data){
			if(isset($this_data['wikistructure_id'])){
				list($wiki_title,$revision_id) = explode('|',$this_data['wikistructure_id']);
				if(isset($this_data['wikilines'])){
					foreach($this_data['wikilines'] as $wl_array){
						foreach($wl_array['links'] as $this_label => $this_link){
							//echo "processing $this_pmid -> $wiki_title\n";
							if(isset($this->big_cache[$this_link][$this_label][$wiki_title])){;
								$this->big_cache[$this_link][$this_label][$wiki_title]++;
							}else{
								$this->big_cache[$this_link][$this_label][$wiki_title] = 1;
							}
						}
					}
				}			
			}

		}

	
		$save_this = array();
		foreach($this->big_cache as $this_link => $label_array){
			if(count($label_array) > 1){
				//then this is juicy and should be recorded...
				//var_export($page_array);
				$title_string = strtolower(str_replace('_',' ',$this_link));

				if(isset($is_this_clinical[$this_link]) && $is_this_clinical[$this_link]){

					foreach($label_array as $this_label => $this_title_array){
						$this_label = strtolower($this_label);
					
						if(strcmp($this_label,$title_string) != 0){ //because obviously...
						/*	
							echo "\n\n        \tLabel         \t\t\tTitle\n";
							echo "Comparing \t$this_label  \t\t\t$title_string\n";
	
							$lev = levenshtein($this_label,$title_string);
							echo "\t$lev levenshtein \n";

							similar_text($this_label,$title_string,$percent);
							echo "\t$percent similar_text \n";

							$similar = LetterPairSimilarity::compareStrings($this_label,$title_string);
							echo "\t$similar LetterPairSimilarity\n";
						*/

						//from the above testing it looks like a match rate 
						//of .8 is so high, that basically the terms might as well be identical
						//so there is no reason to store them in the DB
						$similar = LetterPairSimilarity::compareStrings($this_label,$title_string);
						if($similar < .8){
							//then these terms are really pretty different...
							$save_this[$title_string][] = array(
									'label' => $this_label,
									'found_in' => $this_title_array,
								);					
						
						}


						}

					}
				}

				/*
				*/
			}

		}


		foreach($save_this as $title_string => $stuff_to_save){
			$LabelLinks = new LabelLinks();
			$LabelLinks->data_array['labels'] = $stuff_to_save;
			$LabelLinks->data_array['label_count'] = count($stuff_to_save);
			$LabelLinks->sync($title_string);	
		}





	}

}







?>
