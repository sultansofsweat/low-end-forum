<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Private messaging system");
	
	$thread_id=0;
	$title="";
	$body="";
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	if($init !== true || empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	if(!empty($_GET['thread_id']))
	{
		$thread_id=intval(preg_replace("/[^0-9]/","",$_GET['thread_id']));
	}
	if(!empty($_POST['thread_id']))
	{
		$thread_id=intval(preg_replace("/[^0-9]/","",$_POST['thread_id']));
	}
	if(empty($thread_id))
	{
		die("<script type=\"text/javascript\">window.location = 'private_messages.php'</script>");
	}
	
	$thread_info=get_private_message($thread_id);
	
	if($thread_info["User1"] != $_SESSION['user_id'] && $thread_info["User2"] != $_SESSION['user_id'])
	{
		die("<script type=\"text/javascript\">window.location = 'private_messages.php'</script>");
	}
	
	if(!empty($_POST['s']))
	{
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
		
		$query=$db->prepare("UPDATE privatemessages SET deleted = 0 WHERE threadid = ?");
		$query->bindValue(1,$thread_info,SQLITE3_INTEGER);
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
			die("<p><b>PM thread restored <span style=\"color:#00FF00;\">SUCCESSFULLY</span></b>. You can <a href=\"view_pm.php?thread_id=$thread_id\">go back to the thread</a>, <a href=\"private_messages.php\">go back to your inbox</a>, or <a href=\"index.php\">go home</a>.</p>");
		}
		else
		{
			echo("<p><b>PM thread restoration <span style=\"color:#FF0000;\">FAILED</span></b>. There was an <b>internal error</b>. Please try again later.</p>");
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Restore Private Message Thread</title>
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
<h2>Restore Thread "<?php echo $thread_info["Title"]; ?>"</h2>
<form method="post" action="undelete_thread.php">
<input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>">
<input type="checkbox" name="s" value="y"> Confirm restoration of this thread<br><br>
<input type="submit" value="Undelete thread">
</form>
</body>
</html>