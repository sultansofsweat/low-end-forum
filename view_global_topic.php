<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Topic viewer");
	
	$topic_id=0;
	$topic_info=array();
	
	$is_viewable=false;
	$is_postable=false;
	$is_moderator=false;
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	if($init !== true || empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	if(!empty($_GET['topic_id']))
	{
		$topic_id=preg_replace("/[^0-9]/","",$_GET['topic_id']);
	}
	if(empty($topic_id))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$topic_info=get_topic($topic_id,true);
	
	/*$forum_info=array();
	
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
			
	$query=$db->prepare("SELECT viewlevel,viewoverride,postlevel,postoverride,modlevel,modoverride,showtoall FROM forums WHERE id = ?");
	$query->bindValue(1,$topic_info["Forum"],SQLITE3_INTEGER);
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		$forum_info=array("ShowToAll"=>$result["ShowToAll"],"ViewLevel"=>$result["ViewLevel"],"ViewOverride"=>unserialize($result["ViewOverride"]),"PostLevel"=>$result["PostLevel"],"PostOverride"=>unserialize($result["PostOverride"]),"ModLevel"=>$result["ModLevel"],"ModOverride"=>unserialize($result["ModOverride"]));
	}
	$results->finalize();
	$query->close();
	
	$db->close();*/
	
	if($user_level >= 8)
	{
		$is_moderator=true;
	}
	if($user_level >= 6)
	{
		$is_postable=true;
	}
	if($user_level >= 4)
	{
		$is_viewable=true;
	}
	
	if($is_viewable === false)
	{
		$is_postable=false;
	}
	
	$posts=array();
	foreach($topic_info["Posts"] as $id=>$post)
	{
		if($is_moderator === true || $post["User"] == $_SESSION['user_id'] || ($post["Approved"] == 1 && $post["Deleted"] == 0))
		{
			$posts[$id]=$post;
		}
	}
	
	if(!empty($_GET['unread']))
	{
		foreach($posts as $id=>$post)
		{
			if(!in_array($_SESSION['user_id'],$post["ReadBy"]))
			{
				die("<script type=\"text/javascript\">window.location = 'view_global_topic.php?topic_id=$topic_id#$id'</script>");
			}
		}
	}
	
	if(!empty($_GET['last_post']))
	{
		$id=-1;
		foreach(array_keys($posts) as $id)
		{
			//Do absolutely nothing
		}
		die("<script type=\"text/javascript\">window.location = 'view_global_topic.php?topic_id=$topic_id#$id'</script>");
	}
	
	if(isset($_GET['mark_read']))
	{
		$post_id=preg_replace("/[^a-z0-9]/","",$_GET['mark_read']);
		if($post_id == "y")
		{
			$good=mark_global_posts_as_read($topic_id,array_keys($posts));
			
			if($good === true)
			{
				die("<script type=\"text/javascript\">window.location = 'view_global_topic.php?topic_id=$topic_id&marked_read=y'</script>");
			}
			else
			{
				echo("<p>Failed to mark entire topic as read. Please try again later.</p>");
			}
		}
		else
		{
			$good=mark_global_posts_as_read($topic_id,array($post_id));
			
			if($good === true)
			{
				die("<script type=\"text/javascript\">window.location = 'view_global_topic.php?topic_id=$topic_id&marked_read=y'</script>");
			}
			else
			{
				echo("<p>Failed to mark post as read. Please try again later.</p>");
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
<title><?php echo $forum_name; ?> - View Topic: <?php echo $topic_info["Title"]; ?></title>
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
<h2><?php echo $topic_info["Title"]; ?></h2>
<p>Topic started by: <?php echo display_username($topic_info["Creator"]); ?><br>
Topic started on: <?php echo print_date($topic_info["Created"]); ?><br>
Last post made on: <?php echo print_date($topic_info["LastPost"]); ?>
<?php
	if($topic_info["Approved"] == 0)
	{
		echo("<br>This topic is <span style=\"font-color:#FF0000;\"><b>NOT APPROVED</b></span>!");
	}
	if($topic_info["Deleted"] == 1)
	{
		echo("<br>This topic is <span style=\"font-color:#FF0000;\"><b>DELETED</b></span>!");
	}
	if($topic_info["Locked"] == 1)
	{
		echo("<br>This topic is <span style=\"font-color:#FF0000;\"><b>LOCKED</b></span>!");
	}
?>
</p>
<table>
<tr>
<td>
<?php
	echo("<a href=\"view_global_topic.php?topic_id=$topic_id&mark_read=y\">Mark all posts as read</a>");
	if($is_postable === true)
	{
		echo(" | <a href=\"post_global_reply.php?topic_id=$topic_id\">Reply to this topic</a>");
	}
	else
	{
		echo(" | <span style=\"text-decoration:line-through;\">Reply to this topic</span>");
	}
	if($is_moderator === true)
	{
		echo(" | <a href=\"global_to_local.php?topic_id=$topic_id\">Transfer topic to a forum</a>");
	}
?>
</td>
</tr>
</table>
<p></p>
<?php
	if($is_viewable === true)
	{
		if(count($posts) > 0)
		{
			if($is_moderator === true)
			{
				$rows=5;
			}
			else
			{
				$rows=4;
			}
			echo("<table>");
			foreach($posts as $id=>$post)
			{
				$bgcolour="rgba(255,255,255,0);";
				if(empty($post["Approved"]))
				{
					$bgcolour="rgba(255,255,0,0.2);";
				}
				if(!empty($post["Deleted"]))
				{
					$bgcolour="rgba(255,0,0,0.2);";
				}
				echo("<tr>");
				echo("<td style=\"background-color:$bgcolour\" rowspan=\"$rows\" style=\"text-align:center;\"><a name=\"$id\"></a>");
				display_avatar($post["User"]);
				echo("<br>" . display_username($post["User"]) . "</td>");
				echo("<th style=\"background-color:$bgcolour\">");
				if(!in_array($_SESSION['user_id'],$post["ReadBy"]))
				{
					echo("<img src=\"new.gif\" alt=\"new\" style=\"border:0px;\">&nbsp;&nbsp;&nbsp;");
				}
				echo($post["Title"] . "</th>");
				echo("</tr>");
				echo("<tr>");
				echo("<td style=\"background-color:$bgcolour\">");
				if($post["Parent"] !== NULL)
				{
					echo("In reply to: <b>" . $posts[$post["Parent"]]["Title"] . "</b> by " . display_username($posts[$post["Parent"]]["User"]) . ". <a href=\"view_global_topic.php?topic_id=$topic_id#" . $post["Parent"] . "\">GO TO</a>");
				}
				else
				{
					echo("&nbsp;");
				}
				echo("</td>");
				echo("</tr>");
				echo("<tr>");
				echo("<td style=\"background-color:$bgcolour\">" . nl2br(process_bbcode($post["Text"])) . "<hr>");
				display_signature($post["User"]);
				echo("</td>");
				echo("</tr>");
				if($is_moderator === true)
				{
					echo("<tr>");
					echo("<td style=\"background-color:$bgcolour\">Viewed by: ");
					$output=array();
					foreach($post["ReadBy"] as $user_id)
					{
						$output[]=display_username($user_id);
					}
					echo implode(", ", $output);
					echo("</td>");
					echo("</tr>");
				}
				echo("<tr>");
				echo("<td style=\"background-color:$bgcolour\">");
				if($is_postable === true)
				{
					echo("<a href=\"post_global_reply.php?topic_id=$topic_id&post_id=$id\">Reply to this post</a>");
				}
				else
				{
					echo("<span style=\"text-decoration:line-through;\">Reply to this post</span>");
				}
				if(!in_array($_SESSION['user_id'],$post["ReadBy"]))
				{
					echo(" | <a href=\"view_global_topic.php?topic_id=$topic_id&mark_read=$id\">Mark as read</a>");
				}
				else
				{
					echo(" | <span style=\"text-decoration:line-through;\">Mark as read</span>");
				}
				if($is_moderator === true)
				{
					if($post["Deleted"] == 0)
					{
						echo(" | <a href=\"delete_global_post.php?topic_id=$topic_id&post_id=$id\">Delete this post</a>");
					}
					else
					{
						echo(" | <a href=\"undelete_global_post.php?topic_id=$topic_id&post_id=$id\">Undelete this post</a>");
					}
				}
				echo("</td>");
				echo("</tr>");
			}
			echo("</table>");
		}
	}
	else
	{
		echo("<table>");
		echo("<tr>");
		echo("<td><i>You do not have permission to view this topic.</i></td>");
		echo("</tr>");
		echo("</table>");
	}
?>
</body>
</html>