
<script type="text/javascript" > 
	var hello = "what?";
        var query_url = '<?php echo $json_data ?>';


        $.getJSON(query_url, function(data){

                        dust.render("<?php echo $dust_file  ?>", data, 
                                function(err,out){
                                        $('#content').html(out);                                        
                                });

                       $('#sorted_table').tablesorter({
                                widgets : ['filter'],
                                widgetOptions : {
                                        filter_hideFilters : false
                                }
                        });
                });    

</script>

<a href='<?php echo $json_data; ?>'>.</a>

<div id='content'>


</div>

I know about Dust file: <?php echo $dust_file; ?> <br>
and JSON data 
<a href='<?php echo $json_data; ?>'><?php echo $json_data; ?></a>
