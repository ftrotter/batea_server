<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?php if(isset($title)){echo $title;} ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="">



        <!-- standard styles -->
        <link type="text/css" rel="stylesheet" href="/css/bootstrap.css">
        <link type="text/css" rel="stylesheet" href="/css/font-awesome.css">
        <link type="text/css" rel="stylesheet" href="/css/colors.css">
        <link type="text/css" rel="stylesheet" href="/css/case.css">
        <link type="text/css" rel="stylesheet" href="/css/tablesorter.css"/>
        <link type="text/css" rel="stylesheet" href="/css/theme.bootstrap.css"/>
        <link type="text/css" rel="stylesheet" href="/css/nv.d3.css">
        <link type="text/css" rel="stylesheet" href="/css/leftlist.css"/>
        <link type="text/css" rel="stylesheet" href="/css/bootstrap-dialog.css"/>

        <!-- standard javascript -->
        <script type="text/javascript" language="javascript" src="/js/jquery.min.js"></script>
        <script type='text/javascript' language="javascript" src="/js/jquery.tablesorter.min.js"></script>
        <script type='text/javascript' language="javascript" src="/js/jquery.tablesorter.widgets.min.js"></script>
        <script type='text/javascript' language="javascript" src='/js/d3.v3.js'></script>
        <script type='text/javascript' language="javascript" src='/js/nv.d3.min.js'></script>   
        <script type='text/javascript' language="javascript" src='/js/dust-core-2.2.0.js'></script>
        <script type="text/javascript" language="javascript" src="/js/dust-helpers-1.1.1.js"></script>
        <script type="text/javascript" language="javascript" src="/js/bootstrap.min.js"></script>
        
        <script type="text/javascript" language="javascript" src="/js/jqBootstrapValidation.js"></script>
        <script type="text/javascript" language="javascript" src="/js/bootstrap-dialog.js"></script>
        <script type="text/javascript" src="/js/notify-combined.min.js"></script>
        <script type="text/javascript" src="/js/saveSvgAsPng.js"></script>
        <script type="text/javascript" src="/js/bootstrap-growl.js"></script>

        <!-- custom javascript -->
        <script type="text/javascript" src="/js/util.js"></script>
        <script type="text/javascript" src="/js/careset_api.js"></script>
        
        <!-- custom css -->
        <link type="text/css" rel="stylesheet" href="/css/colors.css"/>

        <!-- load our dust templates -->
        <script src='/dust/pubmed_article_dust.js'></script>
        <script src='/dust/wiki_article_dust.js'></script>
        <script src='/dust/pubmed_index_dust.js'></script>
        <script src='/dust/label_index_dust.js'></script>
        <style type="text/css">
                body {
                        padding-top: 50px;
                        padding-bottom: 20px;
                        }
section {
    padding-top: 60px;
    margin-top: -60px;
}


  .navbar {
      background-color: white;
      background-image:none;
  }

        </style>




 <script>
  $(document).ready(function() 
       { 
                $('#sorted_table').tablesorter({
                        widgets : ['filter'],
                        widgetOptions : {
                                        filter_hideFilters : false
                                        }
                }); 
                if ($("[rel=tooltip]").length) {
                        $("[rel=tooltip]").tooltip();
                }
       } 
  );
  </script>

</head>
<body>
	<?php echo $content; ?>
</body>
</html>
