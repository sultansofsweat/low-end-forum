<pre>
<?php
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
	
	$posts=array();
	$query=$db->prepare("SELECT posts FROM topics WHERE id = 1");
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		$posts=unserialize($result["Posts"]);
	}
	$results->finalize();
	$query->close();
	
	var_dump($posts);
	
	$posts=array_shift($posts);
	
	var_dump($posts);
	
	$query=$db->prepare("UPDATE topics SET posts = ? WHERE id = 1");
	$query->bindValue(1,serialize($posts),SQLITE3_TEXT);
	//var_dump($query->execute());
	$query->close();
	
	$db->close();
?>
</pre>