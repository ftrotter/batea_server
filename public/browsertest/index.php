<?php

$is_chrome = false;

if(preg_match('/(Chrome|CriOS)\//i',$_SERVER['HTTP_USER_AGENT'])
 && !preg_match('/(Aviator|ChromePlus|coc_|Dragon|Edge|Flock|Iron|Kinza|Maxthon|MxNitro|Nichrome|OPR|Perk|Rockmelt|Seznam|Sleipnir|Spark|UBrowser|Vivaldi|WebExplorer|YaBrowser)/i',$_SERVER['HTTP_USER_AGENT'])){
    // Browser might be Google Chrome
	$is_chrome = true;
}

if($is_chrome){
	$button_html = " 
<a href='https://chrome.google.com/webstore/detail/docgraph-batea/pckjiggjadicimnfogjomigkgcpfpapn' class='btn btn-danger btn-lg'>  <span class='glyphicon glyphicon-thumbs-up' aria-hidden='true'>
</span>  
Install the Batea Chrome Extension
</a>
";
}else{
	$button_html = " 
<a href='https://www.google.com/chrome/browser/' class='btn btn-warning btn-lg'>  <span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true'>
</span>  
You must use Chrome to use Batea! Download Chrome here.
</a>
";
}



echo "
<!DOCTYPE html>
<html lang='en'>
    <head>
        <meta http-equiv='content-type' content='text/html; charset=UTF-8'> 
        <meta charset='utf-8'>
        <title></title>
        <meta name='generator' content='Bootply' />
        <meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=1'>
        <link href='//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css' rel='stylesheet'>
        
        <!--[if lt IE 9]>
          <script src='//html5shim.googlecode.com/svn/trunk/html5.js'></script>
        <![endif]-->
<base target='_parent' />
</head>
<body >
        <script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js'></script>
        <script type='text/javascript' src='//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js'></script>
<div class='text-center'>
       $button_html 
</div>
    </body>
</html>";
