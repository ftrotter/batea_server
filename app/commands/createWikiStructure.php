<?php

use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class createWikiStructure extends ScheduledCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'createWikiStructure:go';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'You have all of these clinical wikipages, now lets data mine them';

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
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{

		$arg = "Peanut_allergy";

	
	if(!isset($arg)){
		//then lets just process everything..
	
		$WikiTags = new WikiTags();
		if(is_object($WikiTags)){
			echo "Well WikiTags is an obejct..\n";
		}
		
		$wikitag_list = $WikiTags->get_all();
		
		if(is_object($wikitag_list)){
			echo "wiktag_list is an object (whch is strange\n";
		}

		if(is_array($wikitag_list)){
			echo "this makes more sense\n";
		}
			
		$clinical_titles = array();
		foreach($wikitag_list as $id => $this_wikitag){
			
			if(is_object($this_wikitag)){
				echo "This is an object too dammit\n";
			}

			if(	$this_wikitag['is_Medicine'] 
				|| $this_wikitag['is_WPMED']
				|| $this_wikitag['is_Anatomy']){
					//then this article needs structure dammit!!!
				
				//not sure why the 0 is missing from the end... but this should still work for both..
				$little_array = explode('|',$this_wikitag['wikitags_id']);
				$wikititle = $little_array[0];
				//echo "$wikititle\n";
				$clinical_titles[] = $wikititle;
			}

		}

		echo "The total records in the list is ".count($wikitag_list)." Of these clinical count is ".count($clinical_titles)."\n";


		foreach($clinical_titles as $this_clinical_title){
			echo "working on $this_clinical_title\n";
			$WikiStructure = new WikiStructure();
			$WikiStructure->buildStructureFromTitle($this_clinical_title);		
		}
	}else{

		$WikiStructure = new WikiStructure();
		$data = $WikiStructure->buildStructureFromTitle($arg);		

		$medical_links = array();
		foreach($data['wikilines'] as $this_line){
			foreach($this_line['links'] as $this_wikititle){
				$medical_links[] = $this_wikititle;
			}
		}


		echo "$arg has ".count($medical_links)." pages to process\n";
		foreach($medical_links as $this_wikititle){
			echo "Working on $this_wikititle\n";
			$WikiStructure = new WikiStructure();
			$data = $WikiStructure->buildStructureFromTitle($this_wikititle);				

		}

	}
}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return(array());
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return(array());
	}

}
