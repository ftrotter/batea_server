<?php
/*
	We have several classes that model wikipages... 
	We want to be able to do several actions on a table that represents a wikipage..
	Including getting rid of all records for a given title 
	other than the very latest version...

*/
class VeryWiki  extends VeryMongo{

	function trimOldVersionsOfTitle($title){

		$search = ['title' => $title];

		$my_results = $this->find($search);

		$latest_revision  = 0;
		foreach($my_results as $this_record){
			if(isset($this_record['revision_id'])){
				$this_revision_id = $this_record['revision_id'];
				if($this_revision_id > $latest_revision){
					$latest_revision = $this_revision_id;
				}
			}	
		}

	//	echo "Revision to keep $latest_revision\n";

		//now I know which record to keep, and I am going to delete the rest of them
		$delete_us = [];
		foreach($my_results as $this_record){
			$should_delete = false;
			if(isset($this_record['revision_id'])){
				$this_revision_id = $this_record['revision_id'];
				if($this_revision_id == $latest_revision){
					//do nothing 
				}else{
					$should_delete = true;
					//otherwise delete it...
				}
			}else{
				//there is no revision_id... definately delete that shit.
				$should_delete = true;

			}	

			if($should_delete){
				$id_to_delete = $this_record['_id'];
				$query = ['_id' => $id_to_delete];
				$this->myRemove($query);
			}

		}




	}
}

?>
