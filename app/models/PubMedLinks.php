<?php


class PubMedLinks extends VeryMongo{


	var $big_cache;


	function buildData(){

		$WikiStructure = new WikiStructure();
		$allPages = $WikiStructure->get_all();

		foreach($allPages as $this_data){
			if(isset($this_data['wikistructure_id'])){
				list($wiki_title,$revision_id) = explode('|',$this_data['wikistructure_id']);
				if(isset($this_data['wikilines'])){
					foreach($this_data['wikilines'] as $wl_array){
						foreach($wl_array['pmids'] as $this_pmid){
							//echo "processing $this_pmid -> $wiki_title\n";
							if(isset($this->big_cache[$this_pmid][$wiki_title])){;
								$this->big_cache[$this_pmid][$wiki_title]++;
							}else{
								$this->big_cache[$this_pmid][$wiki_title] = 1;
							}
						}
					}
				}			
			}

		}

		foreach($this->big_cache as $pmid => $page_array){
		
			if(count($page_array) > 1){
				//then this is juicy and should be recorded...
				//var_export($page_array);
				
				$PubMedLinks = new PubMedLinks();
				$PubMedLinks->data_array['found_in_wikititles'] = $page_array;
				$PubMedLinks->data_array['title_count'] = count($page_array);
				$PubMedLinks->sync($pmid);	
				
			}

		}

	}

}



?>
