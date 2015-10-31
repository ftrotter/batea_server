<?php

use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class refreshWikiData extends ScheduledCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'refreshWikiData:go';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Get a huge list of clinical articles and download them all freshly';

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
		return $scheduler;
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{


		$MetaTools = new MetaTools();
		
		$articles = $MetaTools->getBigClinicalArticleList();

		$article_count = count($articles);
			
		echo "Looking at $article_count articles\n";	

		$i = 0;
		foreach($articles as $title){
			$i++;
			if($i%10000 == 0){
				echo "\non $i out of $article_count\n"; 
			}
			echo "$title";
			if(strpos($title,':') === false){ //avoids Talk: and Template: etc
				//$result = WikiTags::isTitleClinical($title);
				$result = WikiTags::isTitleClinicalFromAPI($title);  //lets force the use of the API

				if($result['is_success']){
					if($result['is_titleclinical']){
				//		echo "$title is clinical\n";
						echo ' y ';
					}else{
						echo ' n ';
				//		echo "$title is not clinical\n";
					}
				}	
			}else{
				echo " : ";
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
