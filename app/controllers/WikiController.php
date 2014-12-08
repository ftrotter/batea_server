<?php

class WikiController extends BaseController {

	public function index(){



	}



	public function article($wikititle,$revision_id = null){
	
		//this is how we support both url types at once...
		//accounts for when the revision id is not provided...
		if(!is_numeric($revision_id)){
			$revision_id = 0;
		}

		$view = View::make('html')->nest('content','dust_page',array(
			'dust_file' => 'wiki_article.dust',
			'json_data' => "/wiki/$wikititle/$revision_id/json",
			'wikititle' => $wikititle,
			'revision_id' => $revision_id,
					));

		return($view);

	}


	public function get_json($wikititle,$revision_id = null)
	{

		//this is how we support both url types at once...
		//accounts for when the revision id is not provided...
		if(!is_numeric($revision_id)){
			$revision_id = 0;
		}


		$WikiData = new WikiData();
		$wikidata_id = WikiData::get_wikidata_id($wikititle,$revision_id);
		$WikiData->sync($wikidata_id);

		$better_data = $WikiData->getPageArray();
	
		return Response::json($better_data, $status=200, $headers=[], $options=JSON_PRETTY_PRINT);


	}

}
