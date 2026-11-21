<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Selector edit page");
	
	$sel_id=0;
	$sel_info=array();
	$topic_info=array();
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	if($init !== true || empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	if(!empty($_GET['sel_id']))
	{
		$sel_id=preg_replace("/[^0-9]/","",$_GET['sel_id']);
	}
	if(!empty($_POST['sel_id']))
	{
		$sel_id=preg_replace("/[^0-9]/","",$_POST['sel_id']);
	}
	if(empty($sel_id))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$sel_info=get_selection($sel_id);
	$topic_info=get_topic($sel_info["Topic"]);
	
	$forum_info=array();
	
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
			
	$query=$db->prepare("SELECT modlevel,modoverride FROM forums WHERE id = ?");
	$query->bindValue(1,$topic_info["Forum"],SQLITE3_INTEGER);
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		$forum_info=array("ModLevel"=>$result["ModLevel"],"ModOverride"=>unserialize($result["ModOverride"]));
	}
	$results->finalize();
	$query->close();
	
	$db->close();
	
	$is_modable=false;
	if($user_level >= $forum_info["ModLevel"] || in_array($_SESSION['user_id'],$forum_info["ModOverride"]) || $sel_info["CreatedBy"] == $_SESSION['user_id'])
	{
		$is_modable=true;
	}
	
	if($is_modable === false || $sel_info["Deleted"] == 1 || $sel_info["Closed"] == 0)
	{
		die("<script type=\"text/javascript\">window.location = 'view_topic.php?topic_id=" . $sel_info["Topic"] . "'</script>");
	}
	
	if(!empty($_POST['s']))
	{
		$good=false;
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
		
		$query=$db->prepare("UPDATE selection SET closed = 0 WHERE id = ?");
		$query->bindValue(1,$sel_id,SQLITE3_INTEGER);
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
		
		if($good === true)
		{
			die("<p><b>Selector opened <span style=\"color:#00FF00;\">SUCCESSFULLY</span></b>. You can <a href=\"view_topic.php?topic_id=" . $sel_info["Topic"] . "\">go back to the topic</a>, or <a href=\"index.php\">go home</a>.</p>");
		}
		else
		{
			echo("<p><b>Selector open <span style=\"color:#FF0000;\">FAILED</span></b>. There was an <b>internal error</b>. Please try again later.</p>");
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Re-open Selector</title>
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
<h2>Re-Open Selector "<?php echo $sel_info["Name"]; ?>"</h2>
<form method="post" action="open_selection.php">
<input type="hidden" name="sel_id" value="<?php echo $sel_id; ?>">
<input type="checkbox" name="s" value="y" required="required"> Confirm selector open<br><br>
<input type="submit" value="Open selector">
</form>
</body>
</html>