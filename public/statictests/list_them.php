<?php

                $theseUrls = [
"http://medscape.com",
"http://www.medscape.com",
"http://webmd.com",
"http://www.webmd.com",
"http://nih.gov",
"http://www.ncbi.nlm.nih.gov/pubmed",
"http://blast.ncbi.nlm.nih.gov/Blast.cgi",
"http://pubchem.ncbi.nlm.nih.gov/",
"http://merckmanuals.com",
"http://www.merckmanuals.com/professional/resourcespages/disclaimer",
"http://mayoclinic.org",
"http://www.mayoclinic.org/diseases-conditions/diabetes/basics/definition/con-20033091",
"http://clevelandclinic.org",
"http://my.clevelandclinic.org/services/heart",
"http://nejm.org",
"http://www.nejm.org/medical-articles/review",
"http://icsi.org",
"http://www.icsi.org/education__services/",
"https://medscape.com",
"https://www.medscape.com",
"https://webmd.com",
"https://www.webmd.com",
"https://nih.gov",
"https://www.nlm.nih.gov",
"https://www.ncbi.nlm.nih.gov/pubmed",
"http://blast.ncbi.nlm.nih.gov/Blast.cgi",
"http://locatorplus.gov/",
"https://merckmanuals.com",
"https://www.merckmanuals.com/professional/resourcespages/disclaimer",
"https://mayoclinic.org",
"https://www.mayoclinic.org/diseases-conditions/diabetes/basics/definition/con-20033091",
"https://clevelandclinic.org",
"https://my.clevelandclinic.org/services/heart",
"https://nejm.org",
"https://www.nejm.org/medical-articles/review",
"https://icsi.org",
"https://www.icsi.org/education__services/",
];

echo "<html><head></head><body><h1> Google Test URLS </h1> <ul>";

foreach($theseUrls as $this_url){
	$url_encode = urlencode($this_url);
	$google_url = "https://www.google.com/#safe=off&q=$url_encode";
	echo "<li><a target='_blank' href='$google_url'>$google_url</a>\n";	
}
echo "<h1> Direct Link tests </h1>";
foreach($theseUrls as $this_url){
	echo "<li><a target='_blank' href='$this_url'>$this_url</a>\n";	
}

echo "</ul></body></html>";
