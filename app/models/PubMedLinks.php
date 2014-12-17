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
							echo "processing $this_pmid -> $wiki_title\n";
							$this->big_cache[$this_pmid][] = $wiki_title;
						}
					}
				}			
			}

			if(count($this->big_cache) > 10000){
				var_export($this->big_cache);
				exit();
			}

		}

		var_export($this->big_cache);


	}

}



?>
