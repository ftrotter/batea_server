<?php

class BateaController extends BaseController {


	public function get_user_json($user_token = null){
		if(is_null($user_token)){
			echo "Whaa splutter.. thbbt .but... arggg..";
			exit();
		}

		$sql = "SELECT * FROM `json_trees` WHERE `user_token` LIKE '$user_token' ORDER BY `json_trees`.`upload_time` ASC";
                $PDO = DB::connection('mysql')->getPdo();
                $statement = $PDO->query($sql);
                $result_array = $statement->fetchAll();

		$merged_urls = array();
		$merged_visits = array();
		$links = array();
		$visitid_to_urlid = array();
		
		foreach($result_array as $this_result){

			$tree = json_decode($this_result['tree_json']);
			//this could be firefox someday...
			//oh well..

			$this_chrome_urls = $tree->chrome_urls;
			$this_chrome_visits = $tree->chrome_visits;
					
			foreach($this_chrome_urls as $this_one_url){
				$merged_urls[$this_one_url->url_id] = (array) $this_one_url;
			}

			foreach($this_chrome_visits as $this_one_visit){
				$merged_visits[$this_one_visit->visit_id] = $this_one_visit;
				$visitid_to_urlid[$this_one_visit->visit_id] = $this_one_visit->url_id;
				if($this_one_visit->from_visit > 0){
					$links[] = array(
							'from_visit_id' => $this_one_visit->from_visit, 
							'to_visit_id' => $this_one_visit->visit_id,
							'visit_data' => $this_one_visit,
							);
				}
			}

		}
		//artificailly create the root node...
		$visitid_to_urlid[0] = 0;
/*		$merged_urls[0] = array(
			'url_id' => 0,
			'url' => '',
			'title' => 'Starting Node',
			'visit_count' => 0,
			'typed_count' => 0,
			'last_visit_time' => 0,
			'hidden' => 0,
			'favicon_id' => 0,
			);
*/

		$missing_node = array(
                        'url_id' => 0,
                        'url' => '',
                        'title' => 'Missing Node',
                        'visit_count' => 0,
                        'typed_count' => 0,
                        'last_visit_time' => 0,
                        'hidden' => 0,
                        'favicon_id' => 0,
                        );

                $node_map = array_keys($merged_urls);
                $node_map = array_flip($node_map);	


		//first pass find all of the missing nodes.
		foreach($links as $this_edge){
			$to_visit_id = $this_edge['to_visit_id'];
			$from_visit_id = $this_edge['from_visit_id'];
			$visit_data = $this_edge['visit_data'];
		
			if(!isset($visitid_to_urlid[$from_visit_id])){
				$visitid_to_urlid[$from_visit_id] = 0;
			}
			$from_url_id = $visitid_to_urlid[$from_visit_id];
			$to_url_id = $visitid_to_urlid[$to_visit_id];

			if(!isset($node_map[$from_url_id])){
				$missing_node['title'] = "Missing Node $from_url_id";
				$merged_urls[$from_url_id] = $missing_node;
			}
			if(!isset($node_map[$to_url_id])){
				$missing_node['title'] = "Missing Node $to_url_id";
				$merged_urls[$to_url_id] = $missing_node;
			}

		}


		//now $merged_urls has all of the nodes from any of the links..

                $node_map = array_keys($merged_urls);
                $node_map = array_flip($node_map);	
		$return_me_nodes = array();
		$return_me_links = array();

		foreach($links as $this_edge){
			$to_visit_id = $this_edge['to_visit_id'];
			$from_visit_id = $this_edge['from_visit_id'];
			$visit_data = $this_edge['visit_data'];

			$to_url_id = $visitid_to_urlid[$to_visit_id];
			$from_url_id = $visitid_to_urlid[$from_visit_id];

                        $source_index = $node_map[$from_url_id];
                        $dest_index = $node_map[$to_url_id];

			$visit_time = $visit_data->visit_time;
			$transition = $visit_data->transition;

			$return_me_links[] = array(
				'source' => $source_index,
				'target' => $dest_index,
				'value' => 10,
				'visit_time' => $visit_time,
				'transition' => $transition,
				);

		}	

	
		foreach($merged_urls as $old_id => $this_url){
			$new_index = $node_map[$old_id];
                        $return_me_nodes[$new_index] = $this_url;
			if(strlen($this_url['title']) > 1){
                        	$return_me_nodes[$new_index]['name'] = $this_url['title'];
			}else{
                        	$return_me_nodes[$new_index]['name'] = $this_url['url'];

			}
                        $return_me_nodes[$new_index]['group'] = 1;
                        $return_me_nodes[$new_index]['size'] = 20;
		}
	

		$show_original = Input::get('show_original',false);
		//this returns a merely merged array, perhaps get an option for this one?
		if($show_original){
			$return_me  = array(
				'urls' => $merged_urls,
				'visits' => $merged_visits,
				);
		}else{
		//this one returns d3 graph data format
			$return_me = array( 'nodes' => $return_me_nodes, 'links' => $return_me_links);
		}
		return Response::json($return_me, $status=200, $headers=[], $options=JSON_PRETTY_PRINT);

	}

        public function show_tree($user_token = null){

                if(is_null($user_token)){
                        echo "Whaa whas...but... arggg..";
                        exit();
                }

                $json_data = "/batea/token/$user_token/json";

                $view = View::make('batea_hangingtree',array(
                                                       	'user_token' => $user_token,
                                                      	'json_data' => $json_data,
                                                   	)
                                           	);

                return($view);

        }



	public function show_user($user_token = null){

		if(is_null($user_token)){
			echo "Whaa whas...but... arggg..";
			exit();
		}
	
		$json_data = "/batea/token/$user_token/json";
				
		$view = View::make('html')->nest('content','batea_user',array(
									'user_token' => $user_token,
									'json_data' => $json_data,
									)
						);
				
		return($view);

	}



	public function index(){

		$sql = "SELECT COUNT(*) as total_rows, user_token FROM `json_trees` GROUP BY `user_token` ORDER BY `user_token` DESC";

		$result_array = array();
	        //$results = DB::select(DB::raw($sql));
	        $PDO = DB::connection('mysql')->getPdo();
		$statement = $PDO->query($sql);
		$result_array = $statement->fetchAll();

		$view = View::make('html')->nest('content','batea_index',array('list' => $result_array));

		return($view);
	}




}
