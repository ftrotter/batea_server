<?php


class WikiStructure extends VeryMongo{

	//we need for parsing functions... that we do not need to build twice...
	var $WikiScrapper = null;

	public function buildStructureFromTitle($wikititle){

		if(is_null($this->WikiScrapper)){
			$this->WikiScrapper = new WikiScrapper();
		}	

		

	}

}

?>
