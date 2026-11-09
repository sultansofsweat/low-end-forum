<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Private messaging system");
	
	$thread_id=0;
	$thread_info=array();
	
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
		$thread_id=preg_replace("/[^0-9]/","",$_GET['thread_id']);
	}
	if(empty($thread_id))
	{
		die("<script type=\"text/javascript\">window.location = 'private_messages.php'</script>");
	}
	
	$thread_info=get_private_message($thread_id);
	
	if(($thread_info["User1"] != $_SESSION['user_id'] && $thread_info["User2"] != $_SESSION['user_id']) || ($thread_info["User1"] != $_SESSION['user_id'] && $thread_info["Deleted"] == 1))
	{
		die("<script type=\"text/javascript\">window.location = 'private_messages.php'</script>");
	}
	
	$posts=array();
	foreach($thread_info["Posts"] as $id=>$post)
	{
		if($post["User"] == $_SESSION['user_id'] || $post["Deleted"] == 0)
		{
			$posts[$id]=$post;
		}
	}
	
	if(isset($_GET['mark_read']))
	{
		$post_id=preg_replace("/[^a-z0-9]/","",$_GET['mark_read']);
		if($post_id == "y")
		{
			$good=mark_messages_as_read($thread_id,array_keys($posts));
			
			if($good === true)
			{
				die("<script type=\"text/javascript\">window.location = 'view_pm.php?thread_id=$thread_id&marked_read=y'</script>");
			}
			else
			{
				echo("<p>Failed to mark entire thread as read. Please try again later.</p>");
			}
		}
		else
		{
			$good=mark_messages_as_read($thread_id,array($post_id));
			
			if($good === true)
			{
				die("<script type=\"text/javascript\">window.location = 'view_pm.php?thread_id=$thread_id&marked_read=y'</script>");
			}
			else
			{
				echo("<p>Failed to mark message as read. Please try again later.</p>");
			}
		}
	}
	
	if(isset($_GET['marked_read']))
	{
		echo("<p>Marked topic or post as read.</p>");
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - View Private Message: <?php echo $thread_info["Title"]; ?></title>
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
<h2><?php echo $thread_info["Title"]; ?></h2>
<p>Thread started by: <?php echo display_username($thread_info["User1"]); ?><br>
Thread started on: <?php echo print_date($thread_info["Created"]); ?><br>
Last message sent on: <?php echo print_date($thread_info["LastPost"]); ?>
<?php
	if($thread_info["Deleted"] == 1)
	{
		echo("<br>This topic is <span style=\"font-color:#FF0000;\"><b>DELETED</b></span>!");
	}
?>
</p>
<table>
<tr>
<td>
<?php
	echo("<a href=\"private_messages.php\">Back to inbox</a> | <a href=\"view_pm.php?thread_id=$thread_id&mark_read=y\">Mark all posts as read</a>");
	if(get_user_level($_SESSION['user_id']) >= 6)
	{
		echo(" | <a href=\"reply_to_pm.php?thread_id=$thread_id\">Reply to this thread</a>");
	}
	else
	{
		echo(" | <span style=\"text-decoration:line-through;\">Reply to this thread</span>");
	}
	if($thread_info["User1"] == $_SESSION['user_id'])
	{
		if($thread_info["Deleted"] == 0)
		{
			echo(" | <a href=\"delete_thread.php?thread_id=$thread_id\">Delete entire thread</a>");
		}
		else
		{
			echo(" | <a href=\"undelete_thread.php?thread_id=$thread_id\">Restore this thread</a>");
		}
	}
?>
</td>
</tr>
</table>
<p></p>
<?php
	echo("<table>");
	foreach($posts as $id=>$post)
	{
		$bgcolour="rgba(255,255,255,0);";
		if(!empty($post["Deleted"]))
		{
			$bgcolour="rgba(255,0,0,0.2);";
		}
		echo("<tr>");
		echo("<td style=\"background-color:$bgcolour\" rowspan=\"5\" style=\"text-align:center;\"><a name=\"$id\"></a>");
		display_avatar($post["User"]);
		echo("<br>" . display_username($post["User"]) . "</td>");
		echo("<th style=\"background-color:$bgcolour\">");
		if($post["ReadBy"] === false)
		{
			echo("<img src=\"new.gif\" alt=\"new\" style=\"border:0px;\">&nbsp;&nbsp;&nbsp;");
		}
		echo($post["Title"] . "</th>");
		echo("</tr>");
		echo("<tr>");
		echo("<td style=\"background-color:$bgcolour\">");
		if($post["Parent"] !== NULL)
		{
			echo("In reply to: <b>" . $posts[$post["Parent"]]["Title"] . "</b> by " . display_username($posts[$post["Parent"]]["User"]) . ". <a href=\"view_pm.php?thread_id=$thread_id#" . $post["Parent"] . "\">GO TO</a>");
		}
		else
		{
			echo("&nbsp;");
		}
		echo("</td>");
		echo("</tr>");
		echo("<tr>");
		echo("<td style=\"background-color:$bgcolour\"><br>" . nl2br(process_bbcode($post["Text"])) . "<br><br></td>");
		echo("</tr>");
		echo("<tr>");
		echo("<td style=\"background-color:$bgcolour\">Viewed by other member: ");
		if($post["ReadBy"] === true)
		{
			echo("YES");
		}
		else
		{
			echo("NO");
		}
		echo("</td>");
		echo("</tr>");
		echo("<tr>");
		echo("<td style=\"background-color:$bgcolour\">");
		if(get_user_level($_SESSION['user_id']) >= 6)
		{
			echo("<a href=\"reply_to_pm.php?thread_id=$thread_id&post_id=$id\">Reply to this message</a>");
		}
		else
		{
			echo("<span style=\"text-decoration:line-through;\">Reply to this message</span>");
		}
		if($post["User"] != $_SESSION['user_id'] && $post["ReadBy"] === false)
		{
			echo(" | <a href=\"view_pm.php?thread_id=$thread_id&mark_read=$id\">Mark as read</a>");
		}
		else
		{
			echo(" | <span style=\"text-decoration:line-through;\">Mark as read</span>");
		}
		if($_SESSION['user_id'] == $post["User"])
		{
			if($post["Deleted"] == 0)
			{
				echo(" | <a href=\"delete_message.php?thread_id=$thread_id&post_id=$id\">Delete this message</a>");
			}
			else
			{
				echo(" | <a href=\"undelete_message.php?thread_id=$thread_id&post_id=$id\">Restore this message</a>");
			}
		}
		echo("</td>");
		echo("</tr>");
	}
	echo("</table>");
?>
</body>
</html>