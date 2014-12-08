<?php

	$pubtypes = file_get_contents("pubtypes");
	$pubtype_array = explode("\n",$pubtypes);

	var_export($pubtype_array);


?>
