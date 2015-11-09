<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::post("/debugpost", function() {
	
	$all_input = Input::all();

	Log::info(var_export($all_input,true));

	$return_me = ['is_success' => true, 'sent_in' => $all_input];
	
	return(Response::json($return_me));

});

Route::post("/API/Donator/{browser_token}/foragerComment/new","APIController@foragerComment");
Route::get("/API/Donator/{browser_token}/foragerComment/debug","APIController@foragerCommentDebug");
Route::post("/API/Donator/{browser_token}/historyTree/new","APIController@historyTree");
Route::get("/API/Donator/{browser_token}/historyTree/debug","APIController@historyTreeDebug");
Route::post("/API/Donator/{browser_token}/wikiComment/new","APIController@wikiComment");
Route::get("/API/Donator/{browser_token}/wikiComment/debug","APIController@wikiCommentDebug");
Route::any("/API/Donator/{browser_token}/saveSettings/","APIController@saveSettings");
Route::get("/API/Donator/{browser_token}/saveSettings/debug","APIController@saveSettingsDebug");
Route::post("/API/DonatorToken/new","APIController@DonatorToken");

Route::get("/API/isURLClinical/{that_url}","APIController@isURLClinical");
Route::get("/API/clinicalURLStubs/","APIController@clinicalURLStubs");
Route::get("/API/Donator/{browser_token}","APIController@Donator");

Route::any("/TestAPI/CryptoTest/","APIController@cryptoTest");


Route::get("/pubmed/{pmid}/json", "PubMedController@get_json");
Route::get("/pubmed/{pmid}", "PubMedController@article");
Route::get("/pubmed/", "PubMedController@index");
Route::get("/pubmedlink/json", "PubMedController@view_all_links_json");
Route::get("/pubmedlink", "PubMedController@view_all_links");

Route::get("/labellink/json", "LabelController@view_all_links_json");
Route::get("/labellink", "LabelController@view_all_links");

Route::get("/wiki/{wikititle}/", "WikiController@article");
Route::get("/wiki/{wikititle}/json", "WikiController@get_json");
Route::get("/wiki/{wikititle}/{revision_id}/json", "WikiController@get_json");
Route::get("/wiki/{wikititle}/{revision_id}", "WikiController@article");

Route::get("/analysis/{wikititle}/graph_json", "AnalysisController@recurse_graph_json");
Route::get("/analysis/{wikititle}", "AnalysisController@browse");


// retired..
Route::get("/batea/token/{user_token}/json", "BateaController@get_user_json");
Route::get("/batea/token/{user_token}", "BateaController@show_user");
Route::get("/batea/tree/{user_token}", "BateaController@show_tree");
Route::get("/batea", "BateaController@index");


Route::get("/wikitext/", "WikitextController@index");
Route::get("/wikitext/{wikititle?}/", "WikitextController@wikitext")->where('wikititle', '(.*)');

Route::get("/project/{project}/{importance}/{quality}/","ProjectController@project_json");
Route::get("/project/{project}/{importance}/","ProjectController@project_json");
Route::get("/project/{project}/","ProjectController@project_json");

Route::get('/', function()
{
	return View::make('html')->nest('content','hello',array(                                                          	)
                                    	);
});
