<?php

class AnalysisController extends BaseController {


	public function recurse_graph_json($wikititle,$revision_id = null){
		//this is how we support both url types at once...
		//accounts for when the revision id is not provided...
		if(!is_numeric($revision_id)){
			$revision_id = 0;
		}


		$WikiStructure = new WikiStructure();
			
	
		$data = $WikiStructure->getDeepGraphData($wikititle);

	
		$debug = Input::get('debug',false);
		if($debug){
			echo "<pre>";
			var_export($data);
			exit();
		}

		return Response::json($data, $status=200, $headers=[], $options=JSON_PRETTY_PRINT);

	}

	public function browse($wikititle,$revision_id = null){

		$view = View::make('html')->nest('content','d3_page',array(
                        'json_data' => "/analysis/$wikititle/graph_json",
                        'wikititle' => $wikititle,
                        'revision_id' => $revision_id,
                                        ));

		return($view);
	}




}
