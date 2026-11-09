<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Post deletion page");
	
	$topic_id=0;
	$post_id=-1;
	$title="";
	$body="";
	
	$is_moderator=false;
	
	$global_topics=array();
	$pinned_topic=array();
	$topics=array();
	
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
	if(isset($_GET['post_id']))
	{
		$post_id=intval(preg_replace("/[^0-9]/","",$_GET['post_id']));
	}
	if(isset($_POST['post_id']))
	{
		$post_id=intval(preg_replace("/[^0-9\-]/","",$_POST['post_id']));
	}
	if($post_id < 0)
	{
		die("<script type=\"text/javascript\">window.location = 'view_topic.php?topic_id=$topic_id'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	$topic_info=get_topic($topic_id);
	
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
			
	$query=$db->prepare("SELECT id,modlevel,modoverride FROM forums");
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		if($user_level >= $result["ModLevel"] || in_array($_SESSION['user_id'],unserialize($result["ModOverride"])))
		{
			$is_moderator=true;;
		}
	}
	$results->finalize();
	$query->close();
	
	$db->close();
	
	$post=$topic_info["Posts"][$post_id];
	
	if($is_moderator === false && $post["User"] != $_SESSION['user_id'])
	{
		die("<p>You are not authorized to undelete this post! ?<a href=\"view_topic.php?topic_id=$topic_id\">Click here</a> to go back.</p>");
	}
	
	if(!empty($_POST['s']))
	{
		$deletion_time=time();
		
		$topic_info["Posts"][$post_id]["Deleted"]=0;
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
		
		$query=$db->prepare("UPDATE topics SET lastpost = ?,posts = ? WHERE id = ?");
		$query->bindValue(1,$deletion_time,SQLITE3_INTEGER);
		$query->bindValue(2,serialize($topic_info["Posts"]),SQLITE3_TEXT);
		$query->bindValue(3,$topic_id,SQLITE3_INTEGER);
		$result=$query->execute();
		if($result !== false)
		{
			$good=true;
		}
		else
		{
			$good=false;
		}
		$query->close();
		
		$db->close();
		
		if($good === true)
		{
			die("<p><b>Post restored <span style=\"color:#00FF00;\">SUCCESSFULLY</span></b>. You can <a href=\"view_topic.php?topic_id=$topic_id\">go back to the topic</a>, or <a href=\"index.php\">go home</a>.</p>");
		}
		else
		{
			echo("<p><b>Post restoration <span style=\"color:#FF0000;\">FAILED</span></b>. There was an <b>internal error</b>. Please try again later.</p>");
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Undelete Post</title>
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
<h2>Restore Post In Topic "<?php echo $topic_info["Title"]; ?>"</h2>
<form method="post" action="undelete_post.php">
<input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
<input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
<input type="checkbox" name="s" value="y"> Confirm restoration of this post<br><br>
<input type="submit" value="Restore post">
</form>
</body>
</html>