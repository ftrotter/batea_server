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

		//lets get a wikipedia page
		//then save it to a database...
		$WikiScrapper = new WikiScrapper();
		$wikitext = $WikiScrapper->get_clean_wikitext("Myocardial_infarction");
		$wikilines = explode("\n",$wikitext);
		foreach($wikilines as $this_wikiline){
			if(strpos($this_wikiline,'cite') !== false){
				//echo "working on \n\n $this_wikiline \n\n";
				$pmids = $WikiScrapper->get_all_pmids_from_wikiline($this_wikiline);
				echo "PMIDS = \n";
				var_export($pmids);
				echo "\n";

				$links = $WikiScrapper->get_links_from_wikiline($this_wikiline);
				echo "Links \n";
				var_export($links);
				echo "\n";
		

			}
		}

		echo "/n";
		var_export($WikiScrapper->redirect_cache);
		echo "/n";


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
