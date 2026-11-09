<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Private messaging system");
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	if($init !== true || empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	$pm_threads=get_private_messages($_SESSION['user_id']);
?>
<html>
<head>
<title><?php echo $forum_name; ?> - View Forum: <?php echo $forum_info["Name"]; ?></title>
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
<h2>All Private Messages</h2>
<table>
<tr>
<td>
<?php
	if($user_level >= 6)
	{
		echo("<a href=\"new_pm.php\">Send Private Message</a>");
	}
	else
	{
		echo("<span style=\"text-decoration:line-through;\">Send Private Message</span>");
	}
	echo(" | <a href=\"private_messages.php?mark_read=y\">Mark All PMs As Read</a>");
?>
</td>
</tr>
</table>
<p></p>
<?php
	echo("<table>");
	if(count($pm_threads) > 0)
	{
		foreach($pm_threads as $pm_id=>$pm)
		{
			$bgcolour="rgba(255,255,255,0);";
			if(!empty($pm["Deleted"]))
			{
				$bgcolour="rgba(255,0,0,0.2);";
			}
			echo("<tr>");
			echo("<td style=\"background-color:$bgcolour\">");
			if(!empty($pm["New"]))
			{
				echo("<img src=\"new.gif\" alt=\"new\" style=\"border:0px;\">");
			}
			echo("</td>");
			echo("<td style=\"background-color:$bgcolour\"><a href=\"view_pm.php?thread_id=$pm_id\"><b>" . $pm["Title"] . "</b></a><br>Between " . display_username($pm["User1"]) . " and " . display_username($pm["User2"]) . "<br>Thread created on " . print_date($pm["Created"]) . "</td>");
			echo("<td style=\"background-color:$bgcolour text-align:center; vertical-align:middle;\">" . $pm["PostCount"] . "</td>");
			echo("</tr>");
		}
	}
	else
	{
		echo("<tr>");
		echo("<td><i>There are no private messages to display.</i></td>");
		echo("</tr>");
	}
	echo("</table>");
?>
</body>
</html>