<?php

class PubMedScrapper{


	public $PMID_cache = array();


	public $abstract_base_url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&retmode=text&rettype=abstract&id=";
	public $summary_base_url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=pubmed&retmode=json&rettype=abstract&id=";

	private $PubMedData = null;	


	
	public function __construct(){

		$PubMedData = new PubMedData();
		$all_pubmed_data = $PubMedData->get_all();
		
		foreach($all_pubmed_data as $this_pubmed_data){
			$this_pmid = $this_pubmed_data['pubmeddata_id'];
			$this->PMID_cache[$this_pmid] = $this_pubmed_data;
		}


	}



	public function get_PubMed_data($pmid){

		if(!is_numeric($pmid)){
			echo "PubMedScrapper: Error: pretty sure that pmid has to be a number\n";
			exit();
		}

		if(isset($this->PMID_cache[$pmid])){
			//echo "\t\tPMID $pmid is in cache\n";
			echo 'p';
			return($this->PMID_cache[$pmid]);
		}

		echo "\t\tPMID $pmid downloading\n";
		sleep(1);
		$this_abstract = file_get_contents("$this->abstract_base_url$pmid");
		sleep(1);
		$this_summary_json = file_get_contents("$this->summary_base_url$pmid");
		
		$this_summary_array = json_decode($this_summary_json,true);


		//we always only request one pmid at a time
		//we dont need a data structure that has lots of results in each json...
		//we want to end up with a flat structure that has one json file per result
		//so we flatten this like so...
		$this_result_array = $this_summary_array['result'][$pmid];

// The publication date
// can be any of these...
/*
2001 Apr 15
2001 Apr
2000 Spring
2000 Nov-Dec
2001
*/
// I wrote handleFirstDateType to try and handle this before I knew the API was wearing crazy pants
// Just look at how many date formats are here..
// http://www.nlm.nih.gov/bsd/mms/medlineelements.html#dp
// Insanity.
//Happily they know this, so they give us the reasonable sortpubdate to get us through.


		/*
		if(isset($this_result_array['pubdate']) && strlen($this_result_array['pubdate'] > 0)){
			$this_result_array['pubdate_js'] = $this->handleFirstDateType($this_result_array['pubdate']);
		}
		if(isset($this_result_array['epubdate']) && strlen($this_result_array['epubdate'] > 0)){
			$this_result_array['epubdate_js'] = $this->handleFirstDateType($this_result_array['epubdate']);
		}
		*/
	
		//use sorpubdate to get a pubdate that the database can use.
		$sortpubdate_time = strtotime($this_result_array['sortpubdate']);
		$this_result_array['pubdate_js'] = new MongoDate($sortpubdate_time);
	
		foreach($this_result_array['history'] as $id => $this_event_array){
			$better_time = strtotime($this_event_array['date']);
			$this_result_array['history'][$id]['date_js'] = new MongoDate($better_time);
		}


		$optimize_article_type = $this->getOptomizedArticleArrayFromPubtype($this_result_array['pubtype']);


		$this_result_array['abstract'] = $this_abstract;

		if(is_null($this->PubMedData)){
			$this->PubMedData = new PubMedData();
		}
		$this->PubMedData->data_array = $this_result_array; //hollows out the previous data if there was any...
		$this->PubMedData->sync($pmid);

		$this->PMID_cache[$pmid] = $this_summary_array;

		return($this_result_array);
		

	}


	var $starting_is_pubtype_defaults = array(
		'is_pubtype_review' => false,
		'is_pubtype_casestudy' => false,
		'is_pubtype_casestudy_and_not_review' => false,
		'is_pubtype_bibliography' => false,
		'is_pubtype_dataset' => false,
		'is_pubtype_stronger_study_type' => false,
		'is_pubtype_editorial' => false,
		'is_pubtype_retracted' => false,
		);



/*
 *	This looks at the entries of pubtype and then creates an array of "is_pubtype" variables 
 * 	That can be use to optomized analysis of articles as the pertain to Wikipedia.
 */

public function getOptomizedArticleArrayFromPubtype($pubtype_array){

	$my_starting_array = $this->starting_is_pubtype_defaults;	

	foreach($pubtype_array as $this_pubtype){
				if(in_array($this_pubtype

	}




}


	public static $pubmed_opinion_types = array(
  		'Comment',
  		'Editorial',
	);

	public static $pubmed_good_study_types = array(

  7 => 'Clinical Trial',
  18 => 'Controlled Clinical Trial',
  56 => 'Randomized Controlled Trial',
  29 => 'Guideline',
  35 => 'Journal Article',
  40 => 'Meta-Analysis',
  50 => 'Practice Guideline',
  69 => 'Twin Study',
	);

	public static $pubmed_data_study_types = array(

  2 => 'Bibliography',
  20 => 'Dataset',
	);


	public static $pubmed_publication_types = array (
  0 => 'Addresses',
  1 => 'Autobiography',
  2 => 'Bibliography',
  3 => 'Biography',
  4 => 'Case Reports',
  5 => 'Classical Article',
  6 => 'Clinical Conference',
  7 => 'Clinical Trial',
  8 => 'Clinical Trial, Phase I',
  9 => 'Clinical Trial, Phase II',
  10 => 'Clinical Trial, Phase III',
  11 => 'Clinical Trial, Phase IV',
  12 => 'Collected Works',
  13 => 'Comment',
  14 => 'Comparative Study',
  15 => 'Congresses',
  16 => 'Consensus Development Conference',
  17 => 'Consensus Development Conference, NIH',
  18 => 'Controlled Clinical Trial',
  19 => 'Corrected and Republished Article',
  20 => 'Dataset',
  21 => 'Dictionary',
  22 => 'Directory',
  23 => 'Duplicate Publication',
  24 => 'Editorial',
  25 => 'English Abstract',
  26 => 'Evaluation Studies',
  27 => 'Festschrift',
  28 => 'Government Publications',
  29 => 'Guideline',
  30 => 'Historical Article',
  31 => 'In Vitro',
  32 => 'Interactive Tutorial',
  33 => 'Interview',
  34 => 'Introductory Journal Article',
  35 => 'Journal Article',
  36 => 'Lectures',
  37 => 'Legal Cases',
  38 => 'Legislation',
  39 => 'Letter',
  40 => 'Meta-Analysis',
  41 => 'Multicenter Study',
  42 => 'News',
  43 => 'Newspaper Article',
  44 => 'Observational Study',
  45 => 'Overall',
  46 => 'Patient Education Handout',
  47 => 'Periodical Index',
  48 => 'Personal Narratives',
  49 => 'Portraits',
  50 => 'Practice Guideline',
  51 => 'Pragmatic Clinical Trial',
  52 => 'Publication Components',
  53 => 'Publication Formats',
  54 => 'Publication Type Category',
  55 => 'Published Erratum',
  56 => 'Randomized Controlled Trial',
  57 => 'Research Support, American Recovery and Reinvestment Act',
  58 => 'Research Support, N.I.H., Extramural',
  59 => 'Research Support, N.I.H., Intramural',
  60 => 'Research Support, Non-U.S. Gov\'t Research Support, U.S. Gov\'t, Non-P.H.S.',
  61 => 'Research Support, U.S. Gov\'t, P.H.S.',
  62 => 'Retracted Publication',
  63 => 'Retraction of Publication',
  64 => 'Review',
  65 => 'Scientific Integrity Review',
  66 => 'Study Characteristics',
  67 => 'Support of Research',
  68 => 'Technical Report',
  69 => 'Twin Study',
  70 => 'Validation Studies',
  71 => 'Video-Audio Media',
  72 => 'Webcasts',
);

/*
*       The pubmed api gives date in a string formate of '1991 Oct' OR '1991 Oct 12'
*       This handles both of those cases and returns a MongoDate..
*       I give up I will probably never actually use this function...
*/
        public function handleFirstDateType($a_date_string){

  
                $possible_months = array(
                        1 => 'jan',
                        2 => 'feb',
                        3 => 'mar',
                        4 => 'apr',
                        5 => 'may',
                        6 => 'jun',
                        7 => 'jul',
                        8 => 'aug',
                        9 => 'sep',
                        10 => 'oct',
                        11 => 'nov',
                        12 => 'dec',
                        );

                //to lazy to rewrite the above backwards...
                $possible_months = array_flip($possible_months);


                if(!strlen($a_date_string) > 0){
                        return(false);
                }

                //if we dont have a year, then why bother?
                $day = '1';
                $month_string = 'Jan';

                $date_array = explode(' ',$a_date_string);

                //now it all depends on how many values are in the resulting string...

                if(count($date_array) == 1){
                        list($year) = $date_array;
                }
                if(count($date_array) == 2){
                        list($year, $month_string) = $date_array;
                }
                if(count($date_array) == 3){
                        list($year, $month_string, $day) = $date_array;
                }

                $month = $possible_months[strtolower($month_string)];

                //just so you can see that I am setting the minutes, seconds and hours to 0
                $m = 0;
                $s = 0;
                $h = 0;

                $myMongoDate = new MongoDate(mktime($h,$m,$s,$month,$day,$year));
                return($myMongoDate);
        }


}

?>
