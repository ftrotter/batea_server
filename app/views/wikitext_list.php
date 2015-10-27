<div class='container-fluid'>
<h1> Search </h1>
<form>
Search for articles beginning with: <br>
<input type='text' name='search'><br>
<input type='submit' name='Search' value='Search'>
</form>

<?php 
	if($search_term){ 

		echo "<h1> $search_term Matches </h1>";

		echo "<ul>";

		foreach($matches as $this_match){


			echo "<li>
<a href='/wikitext/$this_match/'>
$this_match
</a>
</li>";
		} 

		echo "</ul>";
	}
?>

</div>
