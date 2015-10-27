<?php

class ClickStreamController extends BaseController {


	public function get_json($page_id,$revision_id = null){
		//this is how we support both url types at once...
		//accounts for when the revision id is not provided...
		if(!is_numeric($revision_id)){
			$revision_id = 0;
		}


		


		return Response::json($data, $status=200, $headers=[], $options=JSON_PRETTY_PRINT);

	}

	public function browse($page_id,$revision_id = null){

		$view = View::make('html')->nest('content','d3_page',array(
                        'json_data' => "/clickstream/$page_id/json",
                        'revision_id' => $revision_id,
                                        ));

		return($view);
	}




}
