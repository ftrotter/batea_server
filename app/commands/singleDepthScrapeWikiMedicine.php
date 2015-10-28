<?php

use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class singleDepthScrapeWikiMedicine extends ScheduledCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'scrapeWikiMedicine:shallow';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Creates a comprehensive database of the pages on wikipedia that are related to Medicine';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * When a command should run
	 *
	 * @param Scheduler $scheduler
	 * @return \Indatus\Dispatcher\Scheduling\Schedulable
	 */
	public function schedule(Schedulable $scheduler)
	{
		return $scheduler->yearly();
		//return $scheduler->daily()->hours(4)->minutes(17);
	}




	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire(){

		echo "Staring shallow scrape\n";
		
	


		$article_params = [
			[
				'project' => 'Medicine',
				'importance' => 'Top',
				'quality' => 'all',
			],
			[
				'project' => 'Anatomy',
				'importance' => 'Top',
				'quality' => 'all',
			],
		];

		$articles = [];
	
		$PA = new ProjectArticles();

		foreach($article_params as $this_params){

			$results = $PA->doSearch(
					$this_params['project'],
					$this_params['importance'],
					$this_params['quality']);

			$this_articles = [];
			foreach($results as $this_result){
				$this_articles[] = rawurlencode($this_result['name']);
			}
			
			$articles = array_merge($articles,$this_articles);
		}

		shuffle($articles);

		echo "Articles loaded. Starting cache load\n";
		$this->shallow_recurse_title_list($articles);

	}


function shuffle_assoc(&$array) {
        $keys = array_keys($array);

        shuffle($keys);

	$new = array();

        foreach($keys as $key) {
            $new[$key] = $array[$key];
        }

        return $new;
}


	function shallow_recurse_title_list($wikititle_list, $depth = '', $go_deeper = true ){


		$wikititle_list  = $this->shuffle_assoc($wikititle_list);

		$depth .= ' ';

		if(count($wikititle_list) == 0){
			return;
		}

		
		$to_recurse = count($wikititle_list);
		$depth_length = strlen($depth);
		$wont_recurse = count($this->visited_titles);
		echo "\nrecursing $to_recurse at depth $depth_length the list to not recurse is now $wont_recurse\n";

		foreach($wikititle_list as $label => $this_wikititle){
			if(!isset($this->visited_titles[$this_wikititle])){
				$new_wikititle_list = $this->expand_wikipage($this_wikititle, $depth);
				echo "\nabout to recurse $this_wikititle\n";
				if($go_deeper){
					$this->shallow_recurse_title_list($new_wikititle_list, $depth, false);
				}
			}else{
				//I need a signal the recursing has an end... 
			}
		}

		echo "------------------------------------------------\n";
	}

	private $visited_titles = array();

	private $med_titles_array = array(); 
	private $WikiScrapper = null;
	private $PubMedScrapper = null;


	function expand_wikipage($wikititle,$depth){

		if(is_null($this->WikiScrapper)){
			$this->WikiScrapper = new WikiScrapper();
		}

		if(is_null($this->PubMedScrapper)){
                	$this->PubMedScrapper = new PubMedScrapper();
		}

                $wikitext = $this->WikiScrapper->get_clean_wikitext($wikititle);
                $wikilines = explode("\n",$wikitext);
                $all_links = array();
                foreach($wikilines as $this_wikiline){
                        if(strpos($this_wikiline,'cite') !== false){
                                //echo "working on \n\n $this_wikiline \n\n";
                                $links = $this->WikiScrapper->get_medical_links_from_wikiline($this_wikiline,$depth);

				$pmids = WikiData::get_all_pmids_from_wikiline($this_wikiline);
				foreach($pmids as $this_pmid){
					$this_pubmed_summary = $this->PubMedScrapper->get_PubMed_data($this_pmid);
					//I dont need this data here PubMed Scrapper saves it
				}

                                $all_links = array_merge($links,$all_links);

                        }
                }

		$this->visited_titles[$wikititle] = true;
		return($all_links);

	}


	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('example', InputArgument::OPTIONAL, 'An example argument.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
	}

}
