<?php

class LabelController extends BaseController {



	public function view_all_links(){

                $view = View::make('html')->nest('content','dust_page',array(
                        'dust_file' => 'label_index.dust',
                        'json_data' => "/labellink/json",
                                        ));

                return($view);

	}


	public function view_all_links_json(){


		$LabelLinks = new LabelLinks();
		$links_list = $LabelLinks->get_all();
		$labels = array();
		/*
		echo "<pre>";
		var_export($links_list);
		exit();
		*/
		foreach($links_list as $this_link_data){
			$my_title = $this_link_data['labellinks_id'];
			$all_my_labels = array();
			foreach($this_link_data['labels'] as  $label_array){
				$label = $label_array['label'];
				$found_in = $label_array['found_in'];
				$found_in = array_keys($found_in);
				$all_my_labels[] = array('label' => $label,
							'found_in' => $found_in);
			}

			$labels[] = array(	'title' => $this_link_data['labellinks_id'],
						'labels' => $all_my_labels,
					);


		}
		$return_me['titles'] = $labels;

		return Response::json($return_me, $status=200, $headers=[], $options=JSON_PRETTY_PRINT);

	}


}
