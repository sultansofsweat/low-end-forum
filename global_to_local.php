<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Topic creation page");
	
	$topic_id=0;
	
	$is_moderator=false;
	
	$all_forums=get_forums();
	$postable_forums=array();
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	if($init !== true || empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	if(!empty($_GET['topic_id']))
	{
		$topic_id=intval(preg_replace("/[^0-9]/","",$_GET['topic_id']));
	}
	if(!empty($_POST['topic_id']))
	{
		$topic_id=intval(preg_replace("/[^0-9]/","",$_POST['topic_id']));
	}
	if(empty($topic_id))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	$topic_info=get_topic($topic_id,true);
	
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
			
	$query=$db->prepare("SELECT id,postlevel,postoverride FROM forums");
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		if($user_level >= $result["PostLevel"] || in_array($_SESSION['user_id'],unserialize($result["PostOverride"])))
		{
			$postable_forums[$result["ID"]]=$all_forums[$result["ID"]];
		}
	}
	$results->finalize();
	$query->close();
	
	$db->close();
	
	if($user_level >= 8)
	{
		$is_moderator=true;
	}
	
	if($is_moderator === false)
	{
		die("<p>You are not authorized to transfer this post! ?<a href=\"view_global_topic.php?topic_id=$topic_id\">Click here</a> to go back.</p>");
	}
	
	if(!empty($_POST['s']))
	{
		$created_time=time();
		$forum_id=preg_replace("/[^0-9]/","",$_POST['forum_id']);
		$global_topic_id=$topic_id;
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			
		$query=$db->prepare("INSERT INTO topics(creator,created,lastpost,posts,forum,approved) VALUES (?,?,?,?,?,?)");
		$query->bindValue(1,$_SESSION['user_id'],SQLITE3_INTEGER);
		$query->bindValue(2,$topic_info["Created"],SQLITE3_INTEGER);
		$query->bindValue(3,$created_time,SQLITE3_INTEGER);
		$query->bindValue(4,serialize($topic_info["Posts"]),SQLITE3_TEXT);
		$query->bindValue(5,$forum_id,SQLITE3_INTEGER);
		$query->bindValue(6,1,SQLITE3_INTEGER);
		$query->execute();
		$query->close();
		
		$topic_id=0;
		$query=$db->prepare("SELECT id FROM topics WHERE forum = ? AND creator = ? AND created = ?");
		$query->bindValue(1,$forum_id,SQLITE3_INTEGER);
		$query->bindValue(2,$_SESSION['user_id'],SQLITE3_INTEGER);
		$query->bindValue(3,$topic_info["Created"],SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$topic_id=$result["ID"];
		}
		$results->finalize();
		$query->close();
		
		if(!empty($topic_id))
		{
			$query=$db->prepare("DELETE FROM globaltopics WHERE id = ?");
			$query->bindValue(1,$global_topic_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
		}
		
		$db->close();
		
		if(!empty($topic_id))
		{
			die("<p><b>Topic transferred <span style=\"color:#00FF00;\">SUCCESSFULLY</span></b>. You can <a href=\"view_topic.php?topic_id=$topic_id\">view the new topic</a>, <a href=\"view_forum.php?forum_id=$forum_id\">go back to the last viewed forum</a>, or <a href=\"index.php\">go home</a>.</p>");
		}
		else
		{
			echo("<p><b>Topic transfer <span style=\"color:#FF0000;\">FAILED</span></b>. There was an <b>internal error</b>. Please try again later.</p>");
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Transfer Topic To Forum</title>
<link rel="stylesheet" href="<?php echo $stylesheet; ?>">
</head>
<body>
<?php if(!empty($forum_logo)) { echo("<p><img src=\"avatars/$forum_logo\" alt=\"Low End Forum Logo\" style=\"border:0px;\"></p>"); } ?>
<h1 style="text-decoration:underline;"><?php echo $forum_name; ?></h1>
<table>
<tr>
<td><?php display_header(); ?></td>
</tr>
</table>
<h2>Transfer Global Topic "<?php echo $topic_info["Title"]; ?>" To Forum</h2>
<form method="post" action="global_to_local.php">
<input type="hidden" name="s" value="y">
<input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
Transfer topic to: <select name="forum_id" required="required">
<option value=""></option>
<?php
	foreach($postable_forums as $id=>$name)
	{
		echo("<option value=\"$id\">$name</option>");
	}
?>
</select><br><br>
<input type="submit" value="Transfer topic">
</form>
</body>
</html>