<?php

use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class scrapeWikiMedicine extends ScheduledCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'scrapeWikiMedicine:go';

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
		return $scheduler->daily()->hours(4)->minutes(17);
	}




	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		if(is_null($this->WikiScrapper)){
                	$this->WikiScrapper = new WikiScrapper();
		}

		$starting_links = $this->WikiScrapper->get_clean_talkpage('Diabetes_mellitus');
		//exit();


		$starting_links = $this->recurse_wikipage('Diabetes_mellitus');
		$two_degrees_links = array();
		foreach($starting_links as $label => $wikititle){
			$this_second_degree = $this->recurse_wikipage($wikititle);
			$two_degrees_links = array_merge($two_degrees_links,$this_second_degree);
		}

	}

	private $med_titles_array = array(); 
	private $WikiScrapper = null;


	function recurse_wikipage($wikititle){

		if(is_null($this->WikiScrapper)){
                	$this->WikiScrapper = new WikiScrapper();
		}
                $wikitext = $this->WikiScrapper->get_clean_wikitext($wikititle);
                $wikilines = explode("\n",$wikitext);
                $all_links = array();
                foreach($wikilines as $this_wikiline){
                        if(strpos($this_wikiline,'cite') !== false){
                                //echo "working on \n\n $this_wikiline \n\n";
                                $links = $this->WikiScrapper->get_medical_links_from_wikiline($this_wikiline);

                                $all_links = array_merge($links,$all_links);

                        }
                }


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
