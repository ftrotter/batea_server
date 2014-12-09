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

Route::get("/pubmed/{pmid}/json", "PubMedController@get_json");
Route::get("/pubmed/{pmid}", "PubMedController@article");
Route::get("/pubmed/", "PubMedController@index");


Route::get("/wiki/{wikititle}/", "WikiController@article");
Route::get("/wiki/{wikititle}/json", "WikiController@get_json");
Route::get("/wiki/{wikititle}/{revision_id}/json", "WikiController@get_json");
Route::get("/wiki/{wikititle}/{revision_id}", "WikiController@article");

Route::get("/analysis/{wikititle}/graph_json", "AnalysisController@recurse_graph_json");
Route::get("/analysis/{wikititle}", "AnalysisController@browse");




Route::get('/', function()
{
	return View::make('hello');
});
