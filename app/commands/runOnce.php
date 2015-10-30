<?php

use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class runOnce extends ScheduledCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'runOnce:go';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This is a container for running process that only need to happen one time.';

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

		$WT = new WikiTags();
		$allTags = $WT->get_all_cursor();

		foreach($allTags as $thisTag){
			$tag_id = $thisTag['wikitags_id'];
			list($title, $revision_id) = explode('|',$tag_id);
			echo "Moving from $tag_id to $title\n";
			$thisWT = new WikiTags();
			$thisWT->sync($tag_id);/// loads this record into our ORM
			$thisWT->data_array['title'] = $title;
			$thisWT->sync($tag_id);
				
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
