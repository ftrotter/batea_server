<?php


class ProjectArticles extends VeryMongo{

	function doSearch($project,$importance,$quality){

		$importance = strtolower($importance);	

		$good_importance = [
			'all' => 'all',
			'???' => 'Unknown',
			'unknown' => 'Unknown',
			'na' => 'NA',
			'top' => 'Top',
			'high' => 'High',
			'mid' => 'Mid',
			'low' => 'Low',
			'top-class' => 'Top',
			'high-class' => 'High',
			'mid-class' => 'Mid',
			'low-class' => 'Low',
			];
	
		if(isset($good_importance[$importance])){
			$real_importance = $good_importance[$importance];
		}else{
			$error = [
				'is_success' => false,
				'error_message' => 'I do not know that importance, choose from the ones listed here',
				'good_importance' => $good_importance
				];
			return($error);

		}

		$quality = strtolower($quality);

                $good_quality = [
			'all' => 'all',
			'fa' => 'FA',
			'fl' => 'FL',
			'fm' => 'FM',
			'ga' => 'GA',
			'b' => 'B',
			'c' => 'C',
			'start' => 'Start',
			'stub' => 'Stub',
			'list' => 'List',
			'book' => 'Book',
			'category' => 'Category',	
			'disambig' => 'Disambig',
			'file' => 'File',
			'portal' => 'Portal',
			'project' => 'Project',
			'redirect' => 'Redirect',
			'template' => 'Template',
			'na' => 'NA',
			'other' => 'Other',
			'unassessed' => 'Unassessed', 
                        'fa-class' => 'FA',
                        'fl-class' => 'FL',
                        'fm-class' => 'FM',
                        'ga-class' => 'GA',
                        'b-class' => 'B',
                        'c-class' => 'C',
                        'start-class' => 'Start',
                        'stub-class' => 'Stub',
                        'list-class' => 'List',
                        'book-class' => 'Book',
                        'category-class' => 'Category',
                        'disambig-class' => 'Disambig',
                        'file-class' => 'File',
                        'portal-class' => 'Portal',
                        'project-class' => 'Project',
                        'redirect-class' => 'Redirect',
                        'template-class' => 'Template',
                        'na-class' => 'NA',
                        'other-class' => 'Other',
                        'unassessed-class' => 'Unassessed',
                        ];

                if(isset($good_quality[$quality])){
                        $real_quality = $good_quality[$quality];
                }else{
			$error = [
				'is_success' => false,
				'error_message' => 'I do not know that quality, choose from the ones listed here',
				'good_quality' => $good_quality
				];
			return($error);
		}


		$search = ['project' => $project];

		if($real_quality != 'all'){
			$search['quality'] = $real_quality;
		}

		if($real_importance != 'all'){
			$search['importance'] = $real_importance;
		}

	
		//$search should be good to go here...

        	$collection = $this->mongo->projectarticles;

        	$cursor = $collection->find($search);

		$this_result = iterator_to_array($cursor);
		sort($this_result);

        	return($this_result);
	}


}

?>
