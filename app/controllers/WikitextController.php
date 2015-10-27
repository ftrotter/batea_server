<?php

class WikitextController extends BaseController {

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

	public function index()
	{

		$search = trim(Input::get('search',''));

		$matches = array();
		$search_term = false;
		if(strlen($search) > 3){
			$search_term = $search;
			$search = DB::connection()->getPdo()->quote($search . "%");
			$sql = "
SELECT * 
FROM enwiki.`page` 
WHERE page_title LIKE $search
";

			$results = DB::select( DB::raw($sql));			

			foreach($results as $this_result){
				$matches[] = $this_result->page_title;
			}
		}
	
		$view = View::make('html')->nest('content','wikitext_list',array(
                        'matches' => $matches,
			'search_term' => $search_term,
                   	));
			
		return($view);	
		
	}


	public function wikitext($page_title,$revision_id = null){

		$page_title = urldecode($page_title);
		$page_title_quote = DB::connection()->getPdo()->quote($page_title);

		$sql = "
SELECT * FROM enwiki.`page` 
JOIN enwiki.revision ON 
		revision.rev_id = 
        page.page_latest
JOIN enwiki.`text` ON	
	`text`.`old_id` =
    revision.rev_text_id
WHERE page_title = $page_title_quote
";


		$results = DB::select(DB::raw($sql));
		
		foreach($results as $this_result){//of course there should be just one..
			$old_text = $this_result->old_text;
			$old_flags = $this_result->old_flags;
		}

		$wiki_html = WikiScrapper::get_html_from_wikitext($old_text);


		return "
<html><head><title>Wikitext raw</title></head><body>

<h1> Result </h1>
<h3>Flags</h3>
<pre>
$old_flags
</pre>
<table width='1000px'>
<tr>
<td style='vertical-align:top' width='50%'>
<h3>Wikitext</h3>
$old_text
</td>
<td style='vertical-align:top'>
<h3> Wiki HTML</h3>
$wiki_html
</td>
</tr>
</table>
</body></html>

";

	}



}
