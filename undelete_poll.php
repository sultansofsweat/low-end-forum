<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Poll edit page");
	
	$poll_id=0;
	$options=array();
	$opens=0;
	$closes=0;
	
	$all_forums=get_forums();
	$postable_forums=array();
	
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
	
	if(!empty($_GET['id']))
	{
		$poll_id=intval(preg_replace("/[^0-9]/","",$_GET['id']));
	}
	if(!empty($_POST['id']))
	{
		$poll_id=intval(preg_replace("/[^0-9]/","",$_POST['id']));
	}
	if(empty($poll_id))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	if(!empty($_GET['type']))
	{
		switch(preg_replace("/[^a-z]/","",$_GET['type']))
		{
			case "informal":
			$formal=false;
			break;
			
			case "formal":
			$formal=true;
			break;
		}
		
	}
	if(!empty($_POST['type']))
	{
		switch(preg_replace("/[^a-z]/","",$_POST['type']))
		{
			case "informal":
			$formal=false;
			break;
			
			case "formal":
			$formal=true;
			break;
		}
	}
	if(!isset($formal))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	$poll_info=get_poll($poll_id,$formal);
	$topic_info=get_topic($poll_info["Topic"]);
	
	if($user_level < 8)
	{
		if($formal === true)
		{
			die("<script type=\"text/javascript\">window.location = 'view_formal_poll.php?id=$poll_id'</script>");
		}
		else
		{
			die("<script type=\"text/javascript\">window.location = 'view_informal_poll.php?id=$poll_id'</script>");
		}
	}
	
	if(!empty($_POST['s']))
	{
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
		
		if($formal === true)
		{
			$query=$db->prepare("UPDATE formalpolls SET deleted = 0 WHERE id = ?");
		}
		else
		{
			$query=$db->prepare("UPDATE informalpolls SET deleted = 0 WHERE id = ?");
		}
		$query->bindValue(1,$poll_id,SQLITE3_INTEGER);
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
		
		if(!empty($poll_id))
		{
			if($formal === true)
			{
				die("<p><b>Poll restored <span style=\"color:#00FF00;\">SUCCESSFULLY</span></b>. You can <a href=\"view_topic.php?topic_id=" . $poll_info["Topic"] . "\">go back to the topic</a>, or <a href=\"index.php\">go home</a>.</p>");
			}
			else
			{
				die("<p><b>Poll restored <span style=\"color:#00FF00;\">SUCCESSFULLY</span></b>. You can <a href=\"view_topic.php?topic_id=" . $poll_info["Topic"] . "\">go back to the topic</a>, or <a href=\"index.php\">go home</a>.</p>");
			}
		}
		else
		{
			echo("<p><b>Poll restoration <span style=\"color:#FF0000;\">FAILED</span></b>. There was an <b>internal error</b>. Please try again later.</p>");
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Restore Poll</title>
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
<h2>Restore Poll In Topic "<?php echo $topic_info["Title"]; ?>"</h2>
<form method="post" action="undelete_poll.php">
<input type="hidden" name="type" value="<?php echo preg_replace("/[^a-z]/","",$_GET['type']); ?>">
<input type="hidden" name="id" value="<?php echo $poll_id; ?>">
<input type="checkbox" name="s" value="y"> Confirm poll restoration<br><br>
<input type="submit" value="Restore poll">
</form>
</body>
</html>