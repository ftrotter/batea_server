<?php
	/* 
		This is for getting the pages of the current wikipedia
		This ignore history and is only going for the current contents of the pages
		There are two download options, one really huge file and lots of smaller files broken up
		This downloads the lots of smaller files broken up.

	*/

	require_once('simple_html_dom.php');

	$https_stub = "https://dumps.wikimedia.org";
	
	$dest = "/home/storage/wikitools/";

	if(isset($argv[1])){
		$url = $argv[1];
	}else{
		echo "I need the url of a particular backup page to work.. look here\n https://dumps.wikimedia.org/enwiki/\n";
		exit();	
	}

	if(isset($argv[2])){
		$dest = $argv[2];
	}

	echo "Downloading to $dest\n";


	$html = file_get_html($url);

	if(!$html){
		echo "\n\nWorking with |$url| did not work... you need to link to a wikipedia data dump page...\n";
		exit();
	}
	
	


	$links = array();
	foreach($html->find("a") as $a){
		if(strpos($a->href,"-pages-meta-current.") !== false){ //this should only match one file... the recombined xml file...
			//this is the big file...	
	
		}else{
			if(strpos($a->href,"-pages-meta-current") !== false){ //this should only match one file... the recombined xml file...
				//this is one of the small files...
				$links[] = $https_stub . $a->href;
			}
		}
	}


	foreach($links as $download_me){
		$wget = "wget $download_me -P $dest";
		echo "downloading $download_me with wget...";
		echo "\n $wget \n\n";
		exec($wget);
		echo "done.\n";
	}


?>
