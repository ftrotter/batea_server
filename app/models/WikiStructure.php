<?php


class WikiStructure extends VeryWiki{


	public $run_silent = false;
	public $force_process = true;


	public function getDeepGraphData($wikititle){

		$graph_data = $this->getGraphDataPerTitle($wikititle,true);
/*		
	echo "<pre>";
	var_export($graph_data);
	exit();
*/

		$other_graphs = array();
		//now loop over all the clinical pages and get their graphs too.i.
		//we may not have data mined these yet...
/*
		foreach($graph_data['page_nodes'] as $other_wikititle){

			if(strcmp($other_wikititle,$wikititle) == 0){
				continue;
			}


			//lets make sure they exist...
			$MineThisOne = new WikiStructure();
			$other_graphs[] = $MineThisOne->getGraphDataPerTitle($other_wikititle);
			unset($MineThisOne);
		}
*/
	/*
		echo "<pre>";
		var_export($other_graphs);
		exit();
	*/

		//now lets merge all of the graphs...
		$all_other_page_nodes = array();
		$all_other_wikiline_nodes = array();
		$all_other_pmid_nodes = array();
		$all_other_edges = array();
		foreach($other_graphs as $this_other_graph){

			$all_other_wikiline_nodes = array_merge(	$all_other_wikiline_nodes,
									$this_other_graph['wikiline_nodes']);
			$all_other_pmid_nodes = array_merge(		$all_other_pmid_nodes,
									$this_other_graph['pmid_nodes']);
			$all_other_page_nodes 	= array_merge(	$all_other_page_nodes,
									$this_other_graph['page_nodes']);
			$all_other_edges = array_merge(			$all_other_edges,
									$this_other_graph['wikiline_nodes']);			
		}

		//now we have these pig data dumps	
		//now lets loop over all the edges to see which ones to include...
		$all_edges = $graph_data['edges'];
		$all_nodes = array_merge(	$graph_data['wikiline_nodes'],
						$graph_data['page_nodes'],
						$graph_data['pmid_nodes']
					);
		$all_nodes[$wikititle] = $wikititle;
		$new_all_nodes = array();
		foreach($all_nodes as $value){
			$new_all_nodes[$value] = $value;
		}
		$all_nodes = $new_all_nodes;
/*
		foreach($all_other_edges as $this_edge_pair){
			//if anything is to the orignal wikititle, include it..
				if(is_array($this_edge_pair)){
				$them = each($this_edge_pair);
				$to = $them['value'];
				$from = $them['key'];
				}else{

					var_export($this_edge_pair);
					exit();
				}
				//if they link to the starting node include them
				//this handles all of the wiki lines
				if(	strcmp($from,$wikititle) == 0 ||
					strcmp($to,$wikititle) == 0){
					$all_edges[] = array($from => $to);
					$all_nodes[$from] = $from;
					$all_nodes[$to] = $to;
				}else{
					//if they link to anything in the original data... include them..
					// if they link to anything in the medical pages.. include them..
					// if they link to anything in the pmids include them...				
					if(	isset($all_nodes[$from]) 
						|| isset($all_nodes[$to])
						|| isset($all_other_medical_nodes[$from])
						|| isset($all_other_medical_nodes[$to])
						|| isset($all_other_pmid_nodes[$from])
						|| isset($all_other_pmid_nodes[$to])
										){
						$all_edges[] = array($from => $to);
						$all_nodes[$from] = $from;
						$all_nodes[$to] = $to;
					}				

				}				
		}
*/


		$node_map = array_keys($all_nodes);
		$node_map = array_flip($node_map);
		//lets find out where they come from...	

		$return_me_links = array();
		$return_me_nodes = array();


		

//		echo "<pre>";
//		var_export($node_map);
//		var_export($all_edges);
//		exit();



		foreach($all_edges as $this_edge_pair){
			$them = each($this_edge_pair);
			$to = $them['value'];
			$from = $them['key'];

			//echo "Going |$from| and |$to| <br>";


			if(!strlen($to) > 1 || !strlen($from) > 1){
				echo "trouble with to:'$to' and from:'$from'\n";
				exit();
			}
				
			if(
				isset($graph_data['wikiline_nodes'][$from]) 			
				|| isset($all_other_wikiline_nodes[$from]) 			
			){ $from_group = 2; $from_size = 20;}

			if(
				isset($graph_data['wikiline_nodes'][$to]) 			
				|| isset($all_other_wikiline_nodes[$to]) 			
			){ $to_group = 2; $to_size = 15;}

			if(
				isset($graph_data['page_nodes'][$from]) 			
				|| isset($all_other_page_nodes[$from]) 			
			){ $from_group = 3; $from_size = 10;}

			if(
				isset($graph_data['page_nodes'][$to]) 			
				|| isset($all_other_page_nodes[$to]) 			
			){ $to_group = 3; $to_size = 10;}

			if(
				isset($graph_data['pmid_nodes'][$from]) 			
				|| isset($all_other_pmid_nodes[$from]) 			
			){ $from_group = 4; $from_size = 10;}

			if(
				isset($graph_data['pmid_nodes'][$to]) 			
				|| isset($all_other_pmid_nodes[$to]) 			
			){ $to_group = 4; $to_size = 10;}
/*
			echo "<pre>";
			var_export($node_map);
			var_export($all_edges);
			echo "from: $from to: $to";
			exit();
*/
	//		echo "<pre>";
//			echo "output is:";
//			echo "node map to $to|".$node_map[$to]."\n";;
//			echo "node map from $from|".$node_map[$from]."\n";;
			

			$source_index = $node_map[$from];
			$dest_index = $node_map[$to];

		//	echo "$from $source_index $to $dest_index <br>";
			$show_this_one = true;
			if((strcmp($wikititle,$to) == 0)  || (strcmp($wikititle,$from) == 0)){
				$dist = 50;
				$strength = 15;
				$class = 1;
				$show_this_one = false;		
			}else{

				$is_to_section = strpos($to,'Section') !== false;
				$is_from_section = strpos($from,'Section') !== false;
				if($is_to_section && $is_from_section){
					$dist = 10;
					$strength = 50;
					$class = 2;
					$linkStrength = 1;
				}else{
					$dist = 100;
					$strength = 5;
					$class = 3;
					$linkStrength = .5;
				}
			}	

			$return_me_nodes[$source_index] = array(
								'name' => $from,
								'group' => $from_group, 
								'size' => $from_size
								);
			$return_me_nodes[$dest_index] = array(
								'name' => $to,
								'group' => $to_group, 	
								'size' => $to_size
								);

			if($show_this_one){
				$return_me_links[] = array(	
							'source' => $source_index,
							'target' => $dest_index,
							'value' => $strength,
							'dist' => $dist,
							'class' => $class,
							'linkStrength' => $linkStrength,
						); 
			}
		}
		//if we dont do this, then the out of orderness turns the json into an object and not an array...	
		ksort($return_me_nodes);

		//we want the main node to be first, so that we can sort shit out..

		$return_me_nodes[$node_map[$wikititle]] = array('name' => $wikititle,'group' => 1, 'size' => 30); //the only node in group one..


//		echo "<pre>";
//		var_export($return_me_nodes);
//		var_export($return_me_links);
//		exit();



		$return_me = array( 'nodes' => $return_me_nodes, 'links' => $return_me_links);
		return($return_me);

	}

	public function getGraphDataPerTitle($wikititle, $link_to_sections = false){

		$WikiStructure = new WikiStructure();  
                $wikidata_id = WikiData::get_wikidata_id($wikititle,0);  
                $WikiStructure->sync($wikidata_id);  

		if(!isset($WikiStructure->data_array['wikilines'])){

                	$empty_data = array(
                        	'wikiline_nodes' => array(),
                        	'page_nodes' => array(),
                        	'pmid_nodes' => array(),
                        	'edges' => array(),
                	);
			return($empty_data);
		}


                $wikilines = $WikiStructure->data_array['wikilines'];

		$wikiline_nodes = array();
		$page_nodes = array();
		$pmid_nodes = array();
		$edges = array();

		//start with the home page itself...
		$page_nodes[$wikititle] = $wikititle;
		$used_sections = array();
		foreach($wikilines as $real_line_number => $this_wikiline_array){
			//lets ignore the blank lines
                        if(strlen($this_wikiline_array['wiki_text']) > 0){

				$section = $this->_get_sectionname($this_wikiline_array['section'],$wikititle);

				if(!$link_to_sections){
					$link_to_me = $wikititle;
				}else{
					$link_to_me = $section;
				}


                                $link_count = count($this_wikiline_array['links']);
                                $pmid_count = count($this_wikiline_array['pmids']);
                                $total_edges = $link_count + $pmid_count;
				//and ignore if there are no edges...
                                if($total_edges > 0){
					if($link_to_sections){
						$edges[$section][$wikititle] = true;
						if(!in_array($section,$used_sections)){
							$used_sections[] = $section; //by using dynamic keys I get the order right...
						}
					}						
					$this_line_name = $this->_get_linename($real_line_number,$wikititle);	
					$wikiline_nodes[$link_to_me] = $link_to_me;

                                        foreach($this_wikiline_array['links'] as $this_page){
						$page_nodes[$this_page] = $this_page;
						$edges[$link_to_me][$this_page] = true;						
                                        }

                                        foreach($this_wikiline_array['pmids'] as $this_pmid){
						$pmid_nodes[$this_pmid] = $this_pmid;
						$edges[$link_to_me][$this_pmid] = true;						
                                        }

				}
			}
		}	
		
		foreach($used_sections as $id => $this_section){
			if($id > 0){
				$edges[$this_section][$last_section] = true;
			}else{
				$first_section = $this_section;
			}
			$last_section = $this_section;
		}
		//now lets make a ring...
		$edges[$first_section][$last_section] = true;


		$new_edges = array();
		foreach($edges as $inner_node => $outer_array){
			foreach($outer_array as $outer_node => $trash){
				$new_edges[] = array($inner_node =>$outer_node);
			}
		}
		$edges = $new_edges;


		$data = array(
			'wikiline_nodes' => $wikiline_nodes,
			'page_nodes' => $page_nodes,
			'pmid_nodes' => $pmid_nodes,
			'edges' => $edges,
		);

		//echo "<pre>";
		//var_export($data);
		//exit();

		return($data);

	}

        function _get_sectionname($section,$wikititle){
                return("$wikititle: Section $section");
        }

        function _get_linename($line_number,$wikititle){
                return("$wikititle: Line $line_number");
        }

        function _get_pmidname($pmid){
                return("PMID $pmid");
        }













	//we need for parsing functions... that we do not need to build twice...
	var $WikiScrapper = null;

	public function buildStructureFromTitle($wikititle = null,$revision_id = null){

		if(is_null($revision_id)){
			$revision_id = 0;
		}

		if(is_null($wikititle)){
			echo "WikiStructure: I must have a wikititle\n";
			exit();
		}

		$wikidata_id = WikiData::get_wikidata_id($wikititle,$revision_id);

		//lets see if we have this one...
		$this->sync($wikidata_id);
		if(count($this->data_array) > 2){ //there will always be at least two..
			if(!$this->run_silent){  echo 'c'; }
			if(!$this->force_process){
				return($this->data_array); //well that was easy!!!
			}
		}

		//if its cached just return it...


		if(is_null($this->WikiScrapper)){
			$this->WikiScrapper = new WikiScrapper();
		}	

		$WikiData = new Wikidata();
		$WikiData->sync($wikidata_id);
		$wikitext = $WikiData->getWikiText();
		$data = $this->fromWikitextSections($wikitext);
	
		//always save a structure run...
		$NewWikiStructure = new WikiStructure();
		$NewWikiStructure->data_array['wikilines'] = $data;
		$NewWikiStructure->sync($wikidata_id);

		return($NewWikiStructure->data_array);
	
		//Lets get the file splin
		//call fromWikitextSections

	}
/*
 *	from a whole wikitext, give me back an array that is structured into sections
 *	
 *
 */

function fromWikitextSections($wikitext){

	$wikitext = WikiData::compress_wikitext_templates('{{','}}',$wikitext);
	$wikitext = WikiData::compress_wikitext_templates('{|','|}',$wikitext);


	$all_wikilines = explode("\n",$wikitext);
	$line_number = 0;
	$last_section = "Introduction";
       	$all_templates = array();
       	$all_citations = array();
       	$all_links = array();
       	$medical_links = array();
       	$section_map = array();
	$all_pmids = array();
	foreach($all_wikilines as $line_number => $this_wikiline){
		if(!$this->run_silent){ echo '.'; }
		$is_heading = WikiData::is_wikiline_heading($this_wikiline);

		if($is_heading){
			$last_section = $is_heading;
                }else{
                        if(WikiData::is_wikiline_template($this_wikiline)){
				//this entire line is a template... 
				//like an infobox...
				//nothing to do here...
                        }else{

				$all_templates[$line_number] = WikiData::get_citation_templates_from_wikiline($this_wikiline);
				//we really should cache the link redirect mappings...
				$all_links[$line_number] = $this->WikiScrapper->get_links_from_wikiline($this_wikiline);
				$medical_links[$line_number] = $this->WikiScrapper->get_medical_links_from_wikiline($this_wikiline);
				$all_pmids[$line_number] = WikiData::get_all_pmids_from_wikiline($this_wikiline);

                        }//end not a special line
                }//end not a heading

		$section_map[$line_number] = $last_section; //remember what section every line number is.

        }//end foreach wiki_line	

	$data = array();
	foreach($all_wikilines as $line_number => $this_wikitext){
	       if(isset($all_links[$line_number])){
        	        $this_links = $all_links[$line_number];
       	 	}else{
                	$this_links = array();
        	}
	       if(isset($all_medical_links[$line_number])){
        	        $this_medical_links = $all_medical_links[$line_number];
       	 	}else{
                	$this_medical_links = array();
        	}

        	if(isset($all_templates[$line_number])){
                	$this_templates = $all_templates[$line_number];
        	}else{
                	$this_templates = array();
        	}

        	if(isset($all_pmids[$line_number])){
                	$this_pmids = $all_pmids[$line_number];
        	}else{
                	$this_pmids = array();
        	}

        	if(isset($section_map[$line_number])){
                	$this_section = $section_map[$line_number];
        	}else{
                	$this_section = "Error with my section logic";
        	}
	

			$data[$line_number] = array(
				'wiki_text' => $this_wikitext,
				'links' => $this_links,
				'medical_links' => $this_medical_links,
				'templates' => $this_templates,
				'pmids' => $this_pmids,
				'section' => $this_section,
			);

	}

		return($data);
		

}//end parse function...
	


}//class end

?>
