<?php


class WikiTags extends VeryMongo{

	static public $talk_tags_maps = [
                'is_WPMED' => '{{WPMED',
                'is_vital' => '{{Vital',
                'is_Anatomy' => '{{WikiProject Anatomy',
                'is_Medicine' => '{{WikiProject Medicine',
		];

        static public $medical_page_filters = [
                '{{WPMED',
                '{{WikiProject Anatomy',
                '{{WikiProject Medicine',
                '{{WikiProject Computational Biology',
                '{{Wikiproject MCB',
                '{{EvolWikiProject}}',
                '{{WikiProject Pharmacology',
                ];


	static public $is_clinical_tags = [
"{{Infobox disease",
"{{Infobox anatomy",
"{{Infobox symptom",
"{{Infobox scientist",
"{{chembox",
"GraySubject",
"{{ICD10",
"{{ICD9",
"MedlinePlus=",
"eMedicineSubj=",
"eMedicineTopic",
"MeshNumber",
"DorlandsID",
"[[Category:Organs]]",
"{{Animal anatomy}}",
"MedlinePlus",
"[[Category:Symptoms and signs:",
"|geneid=",  
"{{Human homeostasis}}",
"{{Blood tests}}",
"[[Category:Human homeostasis]]",
"[[Category:Blood",
"{{Expert-subject|Medicine",
"eMedicineTopic",
"{{MeshName",
"{{Major drug groups}}",
"{{Chromosome genetics}}",
"{{Biology nav}}",  
"[[Category:Auxology",
"[[Category:Anthropometry",
"[[Category:Immunology",
"[[Category:Autoimmune diseases",
"{{System and organs}}",
"{{Digestive glands}}",
"{{Endocrine system}}",
"{{endocrine_pancreas}}",  
"[[Category:Human pregnancy",
"[[Category:Birth control",
"[[Category:Reproduction in mammals",
"[[Category:Obstetrics",
"[[Category:Fertility",
"{{Pregnancy",
"{{Reproductive health",
"{{Reproductive physiology",
"{{Humandevelopment",
"[[Category:Global health",
"pathology}}",
"[[Category:Cognition",
"{{Taxobox",
"{{Viral diseases", 
"{{PBB",
"{{PDB Gallery",
"[[Category:Disability",
"[[Category:Podiatry", 
"[[Category:Orthopedic braces",
"[[Category:Orthopedics",
"[[Category:Skeletal system",
"[[Category:Muscular system",
"[[Category:Rehabilitation team",  
"[[Category:Orthopedic surgery",
"PubChem_Ref",
"ChemSpiderID",
"EINECS",
"KEGG_Ref",
"ChEMBL",
"ATCCode_",
"StdInChI",
"{{Biology",
"{{Biochemical",
"{{Infobox particle",
"[[Category:Chemical elements",
"[[Category:Drugs",
"{{MolBioGeneExp",
"{{Nucleic acids",
"{{Genetics",
"[[Category:DNA",  
"[[Category:Genetics",
"[[Category:Oaths of medicine",
"[[Category:Medical",
"[[Category:Philosophy of medicine",
"[[Category:Sequestering cells",
"[[Category:Human cells",
"proteins}}",
"[[Category:Keratins",
"[[Category:Cytoskeleton",
"[[Category:Skin",
"[[Category:Physiology",
"Molecular and cellular biology}}",
"[[Category:Ageing",
"[[Category:Cellular",
"[[Category:Gerontology",
"[[Category:Molecular",
"[[Category:Mutation",
"[[Category:DNA repair",
"[[Category:Senescence",
"{{Immune system",
"{{Lymphatic system",
"{{System and organs",
"{{Immune receptors",
"Biology|Medicine}}",
"Medicine|Biology}}",
"{{Diets",
"[[Category:Medical treatments",
"[[Category:Syndromes",
"[[Category:History of medicine",
"{{History of medicine",
"{{Protein topics",
"[[Category:Proteins",
"[[Category:Protein complexes",
"[[Category:Organelles",
"[[Category:Apoptosis",
"[[Category:Biology",
		];


	public static function isTitleClinical($wiki_text){
	
		$wiki_text = strtolower($wiki_text);
		$reasons = [];
		$is_clinical = false;
		foreach(WikiTags::is_clinical_tags as $term){
			$term = strtolower($term);
			if(strpos($wiki_text,$term) !== false){	//we find the term, in the wiki text, so this is clinical
				$is_clinical = true;
				$reasons[] = $term;
			}
		}

		$return_me = ['is_clinical' => $is_clinical];
		if(count($reasons) > 0){
			$return_me['reasons'] = $reasons;
		}

		return($return_me);
	

	}

}

?>
