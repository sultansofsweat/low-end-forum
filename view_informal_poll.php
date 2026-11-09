<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Poll viewer");
	
	$poll_id=0;
	$poll_info=array();
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
	
	if(!empty($_GET['id']))
	{
		$poll_id=preg_replace("/[^0-9]/","",$_GET['id']);
	}
	if(empty($poll_id))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$poll_info=get_poll($poll_id,false);
	$topic_info=get_topic($poll_info["Topic"]);
	
	$forum_info=array();
	
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
			
	$query=$db->prepare("SELECT postlevel,postoverride FROM forums WHERE id = ?");
	$query->bindValue(1,$topic_info["Forum"],SQLITE3_INTEGER);
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		$forum_info=array("PostLevel"=>$result["PostLevel"],"PostOverride"=>unserialize($result["PostOverride"]));
	}
	$results->finalize();
	$query->close();
	
	$db->close();
	
	if(($user_level < $forum_info["PostLevel"] && !in_array($_SESSION['user_id'],$forum_info["PostOverride"])) || $user_level < 6 || $topic_info["Locked"] == 1 || $topic_info["Approved"] == 0 || $topic_info["Deleted"] == 1)
	{
		die("<script type=\"text/javascript\">window.location = 'view_topic.php?topic_id=$topic_id'</script>");
	}
	
	$poll_open=false;
	$poll_closed=true;
	
	if($poll_info["Opened"] < time())
	{
		$poll_open=true;
	}
	if($poll_info["Closed"] >= time())
	{
		$poll_closed=false;
	}
	
	if(!empty($_GET['voted']))
	{
		echo("<p>Your vote has been recorded. Thank you!</p>");
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - View Poll: <?php echo $topic_info["Title"]; ?></title>
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
<h2>Poll: <?php echo $topic_info["Title"]; ?></h2>
<p>Poll started by: <?php echo display_username($poll_info["CreatedBy"]); ?><br>
Open date: <?php echo print_date($poll_info["Opened"]); ?><br>
Close date: <?php if($poll_info["Closed"] != PHP_INT_MAX) { echo print_date($poll_info["Closed"]); } else { echo "When closed by poll creator"; } ?>
<?php
	if($poll_open === false || $poll_closed === true)
	{
		echo("<br>This poll is <span style=\"font-color:#FF0000;\"><b>CLOSED</b></span>!");
	}
	if($poll_info["Deleted"] == 1)
	{
		echo("<br>This poll is <span style=\"font-color:#FF0000;\"><b>DELETED</b></span>!");
	}
?>
</p>
<table>
<tr>
<td>
<?php
	echo("<a href=\"view_topic.php?topic_id=" . $poll_info["Topic"] . "\">Back to parent topic</a>");
	if($poll_info["CreatedBy"] == $_SESSION['user_id'] && $poll_open === false && $poll_closed !== true)
	{
		echo(" | <a href=\"open_poll.php?type=informal&id=$poll_id\">Open this poll</a>");
	}
	else
	{
		echo(" | <span style=\"text-decoration:line-through;\">Open this poll</span>");
	}
	if($poll_info["CreatedBy"] == $_SESSION['user_id'] && $poll_closed === false && $poll_open !== false)
	{
		echo(" | <a href=\"close_poll.php?type=informal&id=$poll_id\">Close this poll</a>");
	}
	else
	{
		echo(" | <span style=\"text-decoration:line-through;\">Close this poll</span>");
	}
	if($user_level >= 8)
	{
		if($poll_info["Deleted"] == 0)
		{
			echo(" | <a href=\"delete_poll.php?type=informal&id=$poll_id\">Delete this poll</a>");
		}
		else
		{
			echo(" | <a href=\"undelete_poll.php?type=informal&id=$poll_id\">Undelete this poll</a>");
		}
	}
?>
</td>
</tr>
</table>
<p></p>
<h3>Vote In This Poll Below!</h3>
<p>Choose one of the options below.</p>
<form method="post" action="vote_in_poll.php">
<input type="hidden" name="s" value="y">
<input type="hidden" name="type" value="informal">
<input type="hidden" name="id" value="<?php echo $poll_id; ?>">
<?php
	$votes=0;
	foreach($poll_info["Options"] as $option=>$count)
	{
		$votes+=$count;
		if($poll_open === true && $poll_closed === false)
		{
			echo("<input type=\"radio\" name=\"vote\" value=\"$option\" required=\"required\"> " . base64_decode($option) . " (selected <b>$count</b> times so far)<br><br>");
		}
		else
		{
			echo("<input type=\"radio\" name=\"vote\" value=\"$option\" disabled=\"disabled\"> " . base64_decode($option) . " (selected <b>$count</b> times)<br><br>");
		}
	}
	echo("Total votes: $votes<br><br>");
	if($poll_open === true && $poll_closed === false)
	{
		echo("<input type=\"submit\" value=\"Place vote\">");
	}
	else
	{
		echo("<input type=\"submit\" disabled=\"disabled\" value=\"Voting DISABLED\">");
	}
?>
</form>
</body>
</html>