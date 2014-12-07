<?php

class PubMedScrapper{


	public $PMID_cache = array();


	public $abstract_base_url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&retmode=text&rettype=abstract&id=";
	public $summary_base_url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=pubmed&retmode=json&rettype=abstract&id=";

	private $PubMedData = null;	


	
	public function __construct(){

		$PubMedData = new PubMedData();
		$all_pubmed_data = $PubMedData->get_all();
		
		foreach($all_pubmed_data as $this_pubmed_data){
			$this_pmid = $this_pubmed_data['pubmeddata_id'];
			$this->PMID_cache[$this_pmid] = $this_pubmed_data;
		}


	}



	public function get_PubMed_data($pmid){

		if(!is_numeric($pmid)){
			echo "PubMedScrapper: Error: pretty sure that pmid has to be a number\n";
			exit();
		}

		if(isset($this->PMID_cache[$pmid])){
			//echo "\t\tPMID $pmid is in cache\n";
			echo 'p';
			return($this->PMID_cache[$pmid]);
		}

		echo "\t\tPMID $pmid downloading\n";
		sleep(1);
		$this_abstract = file_get_contents("$this->abstract_base_url$pmid");
		sleep(1);
		$this_summary_json = file_get_contents("$this->summary_base_url$pmid");
		
		$this_summary_array = json_decode($this_summary_json,true);
		$this_summary_array['abstract'] = $this_abstract;

		if(is_null($this->PubMedData)){
			$this->PubMedData = new PubMedData();
		}
		$this->PubMedData->data_array = $this_summary_array; //hollows out the previous data if there was any...
		$this->PubMedData->sync($pmid);

		$this->PMID_cache[$pmid] = $this_summary_array;

		return($this_summary_array);
		

	}


}

?>
