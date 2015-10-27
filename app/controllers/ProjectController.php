<?php
/*
	There are at least two projects that we care about...
	The Medicine Project, and the Anatomy Project...
	This json data source regurgitates the data that we get from here:
	https://tools.wmflabs.org/enwp10/cgi-bin/list2.fcgi?run=yes&projecta=Medicine&importance=Mid-Class&quality=B-Class
	using the scrape_article_list.php tool from the original non-frameworkd batea server project.

*/
class ProjectController extends BaseController {


	public function project_json($project,$importance = 'all',$quality = 'all')
	{

		$PA = new ProjectArticles();
		$results = $PA->doSearch($project,$importance,$quality);
	
		return Response::json($results);


	}

}
