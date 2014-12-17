
<h2>Batea Contributors</h2>
<ul>
<?php foreach($list as $user_array){
		$count = $user_array['total_rows'];
		$user_token = $user_array['user_token'];
		echo "<li> $user_token
			 <a href='/batea/token/$user_token'>FD</a> | 
			 <a href='/batea/tree/$user_token'>Tree</a> |
			<a href='/batea/token/$user_token/json?show_original=true'> raw</a> 
		</li>\n";

	}

?> 

</ul>
