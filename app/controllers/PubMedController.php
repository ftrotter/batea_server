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

	public function view_all_links(){

                $view = View::make('html')->nest('content','dust_page',array(
                        'dust_file' => 'pubmed_index.dust',
                        'json_data' => "/pubmedlink/json",
                                        ));

                return($view);

	}


	public function view_all_links_json(){

		$PubMedData = new PubMedData();

		$PubMedLinks = new PubMedLinks();
		$links_list = $PubMedLinks->get_all();
		$articles = array();
		foreach($links_list as $this_link_data){
			$pmid = $this_link_data['pubmedlinks_id'];
			$new_array = array();
			foreach($this_link_data['found_in_wikititles'] as $title => $found_count){
				$new_array[] = array('title' => $title, 'found_count' => $found_count);	
			}
			$this_link_data['found_in_wikititles'] = $new_array;
			$PubMedData->data_array = array(); //just in case...
			$PubMedData->sync($pmid);
			$articles[] = array_merge($PubMedData->data_array,$this_link_data);
		}
		$return_me['articles'] = $articles;

		return Response::json($return_me, $status=200, $headers=[], $options=JSON_PRETTY_PRINT);

	}


}
