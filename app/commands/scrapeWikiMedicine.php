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

		//List of all FA level Medicine articles and the articles from the 2014 UCSF class.
		$starting_pages = array(
			"Alzheimer%27s_disease",
			"1984_Rajneeshee_bioterror_attack",
			"Acute_myeloid_leukemia",
			"Alaska_Mental_Health_Enabling_Act",
			"Anti-tobacco_movement_in_Nazi_Germany",
			"Antioxidant",
			"Asperger_syndrome",
			"Autism",
			"Bacteria",
			"Golding_Bird",
			"Bupropion",
			"Frank_Macfarlane_Burnet",
			"Chagas_disease",
			"Cholangiocarcinoma",
			"Coeliac_disease",
			"Dengue_fever",
			"Diffuse_panbronchiolitis",
			"Endometrial_cancer",
			"Neil_Hamilton_Fairley",
			"Ray_Farquharson",
			"Oxygen_toxicity",
			"Osteochondritis_dissecans",
			"Multiple_sclerosis",
			"Menstrual_cycle",
			"Meningitis",
			"Management_of_multiple_sclerosis",
			"Major_depressive_disorder",
			"Lung_cancer",
			"Ketogenic_diet",
			"Keratoconus",
			"Samuel_Johnson",
			"Introduction_to_viruses",
			"Influenza",
			"Huntington%27s_disease",
			"Hippocrates",
			"Hepatorenal_syndrome",
			"Helicobacter_pylori",
			"Genetics",
			"Fungus",
			"Female_genital_mutilation",
			"Parkinson%27s_disease",
			"Poliomyelitis",
			"Pulmonary_contusion",
			"Race_Against_Time:_Searching_for_Hope_in_AIDS-Ravaged_Africa",
			"Reactive_attachment_disorder",
			"Rhabdomyolysis",
			"Rosetta@home",
			"Rotavirus",
			"William_S._Sadler",
			"Schizophrenia",
			"Social_history_of_viruses",
			"Subarachnoid hemorrhage",
			"Paul_Nobuo_Tatsuguchi",
			"Thyrotoxic_periodic_paralysis",
			"Tourette_syndrome",
			"Virus",
			"Water_fluoridation",
			"Ryan_White",
			"Michael_Woodruff",
			"Amyloidosis",
			"Premature_rupture_of_membranes",
			"Prostatectomy",
			"Ventilator-associated_pneumonia",
			"Postpartum_depression",
			"Actinic_keratosis",
			"Placental_abruption",
			"Omphalitis_of_newborn",
			"Vulvar_cancer",
			"Toxic_epidermal_necrolysis",
			"Umbilical_cord_prolapse",
			"Appendicitis",
			"Endometriosis",
			"Dyspareunia",
			"Cholecystitis",
			"Nicotine_replacement_therapy",
			);

		$this->recurse_title_list($starting_pages);

	}

	function recurse_title_list($wikititle_list){

		if(count($wikititle_list) == 0){
			return;
		}

		foreach($wikititle_list as $label => $this_wikititle){
			$new_wikititle_list = $this->expand_wikipage($this_wikititle);
			$this->recurse_title_list($new_wikititle_list);
		}

	}



	private $med_titles_array = array(); 
	private $WikiScrapper = null;
	private $PubMedScrapper = null;


	function expand_wikipage($wikititle){

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
                                $links = $this->WikiScrapper->get_medical_links_from_wikiline($this_wikiline);

				$pmids = $this->WikiScrapper->get_all_pmids_from_wikiline($this_wikiline);
				foreach($pmids as $this_pmid){
					$this_pubmed_summary = $this->PubMedScrapper->get_PubMed_data($this_pmid);
					//I dont need this data here PubMed Scrapper saves it
				}

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
