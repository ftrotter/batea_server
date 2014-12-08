<?php

class PubMedController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'HomeController@showWelcome');
	|
	*/

	public function index(){



	}



	public function article($pmid){
	
		$view = View::make('html')->nest('content','dust_page',array(
			'dust_file' => 'pubmed_article.dust',
			'json_data' => "/pubmed/$pmid/json",
					));

		return($view);

	}


	public function get_json($pmid)
	{

		if(!is_numeric($pmid)){
			echo "Shenanigans.";
			exit();
		}

		$PubMedData = new PubMedData();
		$PubMedData->sync($pmid);
	
		//we need to convert the abstract to use web newlines because apparently dust does not handle that well.
		//we will leave the other abstract there for others to use...
		$PubMedData->data_array['abstract_html'] = nl2br($PubMedData->data_array['abstract']);
	
		return Response::json($PubMedData->data_array, $status=200, $headers=[], $options=JSON_PRETTY_PRINT);


	}

}
