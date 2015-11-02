<?php

use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class testIsClinical extends ScheduledCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'testIsClinical:go';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Make sure isClinical works';

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

		$articles = [
			'Diagnosis_of_HIV/AIDS' => 1,
			'Prenatal_diagnosis' => 1,
			'Diaphragm_(contraceptive)' => 1,
			'Diaphragmatic_rupture' => 1,
			'Dietary_fiber' => 1,
			'Cecum' => 1,
			'Cerebellar_vermis' => 1,
			'Claustrum' => 1,
			'STDs_in_the_porn_industry' => 1,
			'Brooke_Ashley' => 0,
			'Ario_Pardee,_Jr.' => 0,
			'Arno_Voss' => 0,
			'Aztec_Club_of_1847' => 0,
			'Colt_Army_Model_1860' => 0,
			'Dog' => 0,
			];

		$force_api = true;

		foreach($articles as $article => $expected_result){

			if($force_api){
				$result = WikiTags::isTitleClinicalFromAPI($article);
			}else{
				$result = WikiTags::isTitleClinical($article);
			}

			if($result['is_titleclinical']){
				$result_print = 1;
			}else{
				$result_print = 0;
			}	

			if($result_print == $expected_result){
				echo "Y $article expected $expected_result got $result_print\n";
			}else{
				echo "N $article expected $expected_result got $result_print\n";
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
