<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Forum viewer");
	
	$forum_id=0;
	$all_forums=get_forums();
	
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
	
	if(!empty($_GET['forum_id']))
	{
		$forum_id=preg_replace("/[^0-9]/","",$_GET['forum_id']);
	}
	if(empty($all_forums[$forum_id]))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	$forum_info=array();
	
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
			
	$query=$db->prepare("SELECT name,description,viewlevel,viewoverride,postlevel,postoverride,modlevel,modoverride,showtoall,pinnedtopic FROM forums WHERE id = ?");
	$query->bindValue(1,$forum_id,SQLITE3_INTEGER);
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		$forum_info=array("Name"=>$result["Name"],"Description"=>$result["Description"],"ShowToAll"=>$result["ShowToAll"],"PinnedTopic"=>$result["PinnedTopic"],"ViewLevel"=>$result["ViewLevel"],"ViewOverride"=>unserialize($result["ViewOverride"]),"PostLevel"=>$result["PostLevel"],"PostOverride"=>unserialize($result["PostOverride"]),"ModLevel"=>$result["ModLevel"],"ModOverride"=>unserialize($result["ModOverride"]));
	}
	$results->finalize();
	$query->close();
	
	if($user_level >= $forum_info["ViewLevel"] || in_array($_SESSION['user_id'],$forum_info["ViewOverride"]))
	{
		if($user_level >= $forum_info["ModLevel"] || in_array($_SESSION['user_id'],$forum_info["ModOverride"]))
		{
			$query=$db->prepare("SELECT id,creator,created,lastpost,deleted,posts FROM globaltopics ORDER BY lastpost DESC");
			$results=$query->execute();
			while($result=$results->fetchArray(SQLITE3_ASSOC))
			{
				$topic=array("Name"=>"","New"=>0,"Poll"=>0,"Locked"=>0,"Approved"=>1,"PostCount"=>0,"LastPost"=>array("Name"=>"","Poster"=>0,"Posted"=>0),"Creator"=>$result["Creator"],"Created"=>$result["Created"],"Deleted"=>$result["Deleted"],"Posts"=>unserialize($result["Posts"]));
				$posts=unserialize($result["Posts"]);
				$topic["PostCount"]=count($posts);
				foreach($posts as $post)
				{
					if(!in_array($_SESSION['user_id'],$post["ReadBy"]))
					{
						$topic["New"]=1;
						break;
					}
				}
				$last_post=array_pop($posts);
				if(count($posts) > 0)
				{
					$first_post=array_shift($posts);
				}
				else
				{
					$first_post=$last_post;
				}
				$topic["Name"]=$first_post["Title"];
				$topic["LastPost"]["Name"]=$last_post["Title"];
				$topic["LastPost"]["Poster"]=$last_post["User"];
				$topic["LastPost"]["Posted"]=$last_post["Date"];
				$global_topics[$result["ID"]]=$topic;
			}
			$results->finalize();
			$query->close();
			$query=$db->prepare("SELECT id,creator,created,lastpost,deleted,posts,approved,locked FROM topics WHERE forum = ? ORDER BY lastpost DESC");
			$query->bindValue(1,$forum_id,SQLITE3_INTEGER);
			$results=$query->execute();
			while($result=$results->fetchArray(SQLITE3_ASSOC))
			{
				$topic=array("Name"=>"","New"=>0,"Poll"=>0,"Locked"=>$result["Locked"],"Approved"=>$result["Approved"],"PostCount"=>0,"LastPost"=>array("Name"=>"","Poster"=>0,"Posted"=>0),"Creator"=>$result["Creator"],"Created"=>$result["Created"],"Deleted"=>$result["Deleted"],"Posts"=>unserialize($result["Posts"]));
				$posts=unserialize($result["Posts"]);
				foreach($posts as $post)
				{
					if(!in_array($_SESSION['user_id'],$post["ReadBy"]))
					{
						$topic["New"]=1;
						break;
					}
				}
				$topic["PostCount"]=count($posts);
				$last_post=array_pop($posts);
				if(count($posts) > 0)
				{
					$first_post=array_shift($posts);
				}
				else
				{
					$first_post=$last_post;
				}
				$topic["Name"]=$first_post["Title"];
				$topic["LastPost"]["Name"]=$last_post["Title"];
				$topic["LastPost"]["Poster"]=$last_post["User"];
				$topic["LastPost"]["Posted"]=$last_post["Date"];
				$topics[$result["ID"]]=$topic;
			}
			$results->finalize();
			$query->close();
			if(!empty($forum_info["PinnedTopic"]))
			{
				$pinned_topic=$topics[$forum_info["PinnedTopic"]];
				$pinned_topic["ID"]=$forum_info["PinnedTopic"];
				unset($topics[$forum_info["PinnedTopic"]]);
			}
			
			$topic_id=0;
			$query=$db->prepare("SELECT id FROM informalpolls WHERE topic = ?");
			$query->bindParam(1,$topic_id,SQLITE3_INTEGER);
			foreach(array_keys($topics) as $topic_id)
			{
				$results=$query->execute();
				while($result=$results->fetchArray(SQLITE3_ASSOC))
				{
					if(!empty($result["ID"]))
					{
						$topics[$topic_id]["Poll"]=1;
					}
				}
				$results->finalize();
				$query->reset();
			}
			$query->close();
			$topic_id=0;
			$query=$db->prepare("SELECT id FROM formalpolls WHERE topic = ?");
			$query->bindParam(1,$topic_id,SQLITE3_INTEGER);
			foreach(array_keys($topics) as $topic_id)
			{
				$results=$query->execute();
				while($result=$results->fetchArray(SQLITE3_ASSOC))
				{
					if(!empty($result["ID"]))
					{
						$topics[$topic_id]["Poll"]=1;
					}
				}
				$results->finalize();
				$query->reset();
			}
			$query->close();
		}
		else
		{
			$query=$db->prepare("SELECT id,creator,created,lastpost,posts FROM globaltopics WHERE deleted = 0 ORDER BY lastpost DESC");
			$results=$query->execute();
			while($result=$results->fetchArray(SQLITE3_ASSOC))
			{
				$topic=array("Name"=>"","New"=>0,"Poll"=>0,"Locked"=>0,"Approved"=>1,"LastPost"=>array("Name"=>"","Poster"=>0,"Posted"=>0),"Creator"=>$result["Creator"],"Created"=>$result["Created"],"Deleted"=>0,"Posts"=>unserialize($result["Posts"]));
				$posts=unserialize($result["Posts"]);
				$topic["PostCount"]=count($posts);
				foreach($posts as $post)
				{
					if(!in_array($_SESSION['user_id'],$post["ReadBy"]) && $post["Approved"] == 1 && $post["Deleted"] == 0)
					{
						$topic["New"]=1;
						break;
					}
				}
				$last_post=array_pop($posts);
				if(count($posts) > 0)
				{
					$first_post=array_shift($posts);
				}
				else
				{
					$first_post=$last_post;
				}
				$topic["Name"]=$first_post["Title"];
				$topic["LastPost"]["Name"]=$last_post["Title"];
				$topic["LastPost"]["Poster"]=$last_post["User"];
				$topic["LastPost"]["Posted"]=$last_post["Date"];
				$global_topics[$result["ID"]]=$topic;
			}
			$results->finalize();
			$query->close();
			$query=$db->prepare("SELECT id,creator,created,lastpost,posts,locked FROM topics WHERE forum = ? AND deleted = 0 AND approved = 1 ORDER BY lastpost DESC");
			$query->bindValue(1,$forum_id,SQLITE3_INTEGER);
			$results=$query->execute();
			while($result=$results->fetchArray(SQLITE3_ASSOC))
			{
				$topic=array("Name"=>"","New"=>0,"Poll"=>0,"Locked"=>$result["Locked"],"Approved"=>1,"PostCount"=>0,"LastPost"=>array("Name"=>"","Poster"=>0,"Posted"=>0),"Creator"=>$result["Creator"],"Created"=>$result["Created"],"Deleted"=>0,"Posts"=>unserialize($result["Posts"]));
				$posts=unserialize($result["Posts"]);
				foreach($posts as $post)
				{
					if(!in_array($_SESSION['user_id'],$post["ReadBy"]) && $post["Approved"] == 1 && $post["Deleted"] == 0)
					{
						$topic["New"]=1;
						break;
					}
				}
				$topic["PostCount"]=count($posts);
				$last_post=array_pop($posts);
				if(count($posts) > 0)
				{
					$first_post=array_shift($posts);
				}
				else
				{
					$first_post=$last_post;
				}
				$topic["Name"]=$first_post["Title"];
				$topic["LastPost"]["Name"]=$last_post["Title"];
				$topic["LastPost"]["Poster"]=$last_post["User"];
				$topic["LastPost"]["Posted"]=$last_post["Date"];
				$topics[$result["ID"]]=$topic;
			}
			$results->finalize();
			$query->close();
			if(!empty($forum_info["PinnedTopic"]) && !empty($topics[$forum_info["PinnedTopic"]]))
			{
				$pinned_topic=$topics[$forum_info["PinnedTopic"]];
				unset($topics[$forum_info["PinnedTopic"]]);
			}
			
			$topic_id=0;
			$query=$db->prepare("SELECT id FROM informalpolls WHERE topic = ? AND deleted = 0");
			$query->bindParam(1,$topic_id,SQLITE3_INTEGER);
			foreach(array_keys($topics) as $topic_id)
			{
				$results=$query->execute();
				while($result=$results->fetchArray(SQLITE3_ASSOC))
				{
					if(!empty($result["ID"]))
					{
						$topics[$topic_id]["Poll"]=1;
					}
				}
				$results->finalize();
				$query->reset();
			}
			$query->close();
			$topic_id=0;
			$query=$db->prepare("SELECT id FROM formalpolls WHERE topic = ? AND deleted = 0");
			$query->bindParam(1,$topic_id,SQLITE3_INTEGER);
			foreach(array_keys($topics) as $topic_id)
			{
				$results=$query->execute();
				while($result=$results->fetchArray(SQLITE3_ASSOC))
				{
					if(!empty($result["ID"]))
					{
						$topics[$topic_id]["Poll"]=1;
					}
				}
				$results->finalize();
				$query->reset();
			}
			$query->close();
		}
	}
	elseif($forum_info["ShowToAll"] == 1)
	{
		$query=$db->prepare("SELECT id,created,lastpost,posts,locked,approved FROM topics WHERE forum = ? AND deleted = 0 AND creator = ? ORDER BY lastpost DESC");
		$query->bindValue(1,$forum_id,SQLITE3_INTEGER);
		$query->bindValue(2,$_SESSION['user_id'],SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$topic=array("Name"=>"","New"=>0,"Poll"=>0,"Locked"=>$result["Locked"],"Approved"=>$result["Approved"],"PostCount"=>0,"LastPost"=>array("Name"=>"","Poster"=>0,"Posted"=>0),"Creator"=>$result["Creator"],"Created"=>$result["Created"],"Deleted"=>0,"Posts"=>unserialize($result["Posts"]));
			$posts=unserialize($result["Posts"]);
			foreach($posts as $post)
			{
				if(!in_array($_SESSION['user_id'],$post["ReadBy"]) && $post["Approved"] == 1 && $post["Deleted"] == 0)
				{
					$topic["New"]=1;
					break;
				}
			}
			$topic["PostCount"]=count($posts);
			$last_post=array_pop($posts);
			if(count($posts) > 0)
			{
				$first_post=array_shift($posts);
			}
			else
			{
				$first_post=$last_post;
			}
			$topic["Name"]=$first_post["Title"];
			$topic["LastPost"]["Name"]=$last_post["Title"];
			$topic["LastPost"]["Poster"]=$last_post["User"];
			$topic["LastPost"]["Posted"]=$last_post["Date"];
			$topics[$result["ID"]]=$topic;
		}
		$results->finalize();
		$query->close();
	}
	
	$db->close();
	
	if(!empty($_GET['mark_read']))
	{
		foreach($topics as $topic_id=>$topic_info)
		{
			mark_posts_as_read($topic_id,array_keys($topic_info["Posts"]));
		}
		die("<script type=\"text/javascript\">window.location = 'view_forum.php?forum_id=$forum_id'</script>");
	}
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
<h2><?php echo $forum_info["Name"]; ?></h2>
<p><?php echo $forum_info["Description"]; ?></p>
<table>
<tr>
<td>
<?php
	if($user_level >= $forum_info["PostLevel"] || in_array($_SESSION['user_id'],$forum_info["PostOverride"]))
	{
		echo("<a href=\"new_topic.php?forum=$forum_id\">Create New Topic</a>");
	}
	else
	{
		echo("<span style=\"text-decoration:line-through;\">Create New Topic</span>");
	}
	echo(" | <a href=\"view_forum.php?forum_id=$forum_id&mark_read=y\">Mark All Topics As Read</a>");
	if($user_level >= $forum_info["ModLevel"] || in_array($_SESSION['user_id'],$forum_info["ModOverride"]))
	{
		echo(" | <a href=\"pin_topic.php?id=$forum_id\">Edit Pinned Topic</a>");
	}
?>
</td>
</tr>
</table>
<p></p>
<?php
	if($user_level >= $forum_info["ViewLevel"] || in_array($_SESSION['user_id'],$forum_info["ViewOverride"]))
	{
		if(count($global_topics) > 0)
		{
			echo("<table>");
			echo("<tr>");
			echo("<th colspan=\"4\">GLOBAL POSTS</th>");
			echo("</tr>");
			foreach($global_topics as $topic_id=>$topic)
			{
				$bgcolour="rgba(255,255,255,0);";
				if(empty($topic["Approved"]))
				{
					$bgcolour="rgba(255,255,0,0.2);";
				}
				if(!empty($topic["Deleted"]))
				{
					$bgcolour="rgba(255,0,0,0.2);";
				}
				echo("<tr>");
				echo("<td style=\"background-color:$bgcolour\">");
				if(!empty($topic["New"]))
				{
					echo("<img src=\"new.gif\" alt=\"new\" style=\"border:0px;\">");
				}
				if(!empty($topic["Poll"]))
				{
					echo("<img src=\"poll.gif\" alt=\"poll\" style=\"border:0px;\">");
				}
				if(!empty($topic["Locked"]))
				{
					echo("<img src=\"lock.gif\" alt=\"locked\" style=\"border:0px;\">");
				}
				echo("</td>");
				echo("<td style=\"background-color:$bgcolour\"><b>" . $topic["Name"] . "</b><br>Created by " . display_username($topic["Creator"]) . " on " . print_date($topic["Created"]) . "<br>View topic: <a href=\"view_global_topic.php?topic_id=$topic_id\">From top</a> | <a href=\"view_global_topic.php?topic_id=$topic_id&unread=y\">From last read post</a> | <a href=\"view_global_topic.php?topic_id=$topic_id&last_post=y\">From last post</a></td>");
				echo("<td style=\"background-color:$bgcolour text-align:center; vertical-align:middle;\">" . $topic["PostCount"] . "</td>");
				echo("<td style=\"background-color:$bgcolour\">");
				display_last_post($topic_id,true);
				echo("</td>");
				echo("</tr>");
			}
			echo("</table>");
			echo("<p></p>");
		}
		if(!empty($pinned_topic))
		{
			echo("<table>");
			echo("<tr>");
			echo("<th colspan=\"4\">PINNED TOPIC</th>");
			echo("</tr>");
			$bgcolour="rgba(255,255,255,0);";
			if(empty($pinned_topic["Approved"]))
			{
				$bgcolour="rgba(255,255,0,0.2);";
			}
			if(!empty($pinned_topic["Deleted"]))
			{
				$bgcolour="rgba(255,0,0,0.2);";
			}
			echo("<tr>");
			echo("<td style=\"background-color:$bgcolour\">");
			if(!empty($pinned_topic["New"]))
			{
				echo("<img src=\"new.gif\" alt=\"new\" style=\"border:0px;\">");
			}
			if(!empty($pinned_topic["Poll"]))
			{
				echo("<img src=\"poll.gif\" alt=\"poll\" style=\"border:0px;\">");
			}
			if(!empty($pinned_topic["Locked"]))
			{
				echo("<img src=\"lock.gif\" alt=\"locked\" style=\"border:0px;\">");
			}
			echo("</td>");
			echo("<td style=\"background-color:$bgcolour\"><b>" . $pinned_topic["Name"] . "</b><br>Created by " . display_username($pinned_topic["Creator"]) . " on " . print_date($pinned_topic["Created"]) . "<br>View topic: <a href=\"view_topic.php?topic_id=" . $pinned_topic["ID"] . "\">From top</a> | <a href=\"view_topic.php?topic_id=" . $pinned_topic["ID"] . "&unread=y\">From last read post</a> | <a href=\"view_topic.php?topic_id=" . $pinned_topic["ID"] . "&last_post=y\">From last post</a></td>");
			echo("<td style=\"background-color:$bgcolour text-align:center; vertical-align:middle;\">" . $pinned_topic["PostCount"] . "</td>");
			echo("<td style=\"background-color:$bgcolour\">");
			display_last_post($pinned_topic["ID"]);
			echo("</td>");
			echo("</tr>");
			echo("</table>");
			echo("<p></p>");
		}
		echo("<table>");
		if(count($topics) > 0)
		{
			foreach($topics as $topic_id=>$topic)
			{
				$bgcolour="rgba(255,255,255,0);";
				if(empty($topic["Approved"]))
				{
					$bgcolour="rgba(255,255,0,0.2);";
				}
				if(!empty($topic["Deleted"]))
				{
					$bgcolour="rgba(255,0,0,0.2);";
				}
				echo("<tr>");
				echo("<td style=\"background-color:$bgcolour\">");
				if(!empty($topic["New"]))
				{
					echo("<img src=\"new.gif\" alt=\"new\" style=\"border:0px;\">");
				}
				if(!empty($topic["Poll"]))
				{
					echo("<img src=\"poll.gif\" alt=\"poll\" style=\"border:0px;\">");
				}
				if(!empty($topic["Locked"]))
				{
					echo("<img src=\"lock.gif\" alt=\"locked\" style=\"border:0px;\">");
				}
				echo("</td>");
				echo("<td style=\"background-color:$bgcolour\"><b>" . $topic["Name"] . "</b><br>Created by " . display_username($topic["Creator"]) . " on " . print_date($topic["Created"]) . "<br>View topic: <a href=\"view_topic.php?topic_id=$topic_id\">From top</a> | <a href=\"view_topic.php?topic_id=$topic_id&unread=y\">From last read post</a> | <a href=\"view_topic.php?topic_id=$topic_id&last_post=y\">From last post</a></td>");
				echo("<td style=\"background-color:$bgcolour text-align:center; vertical-align:middle;\">" . $topic["PostCount"] . "</td>");
				echo("<td style=\"background-color:$bgcolour\">");
				display_last_post($topic_id);
				echo("</td>");
				echo("</tr>");
			}
		}
		else
		{
			echo("<tr>");
			echo("<td><i>There are no topics in this forum to display.</i></td>");
			echo("</tr>");
		}
		echo("</table>");
	}
	elseif(!empty($forum_info["ShowToAll"]))
	{
		echo("<table>");
		echo("<tr>");
		echo("<td><i>You do not have permission to view this forum. Posts you make and have made are below.</i></td>");
		echo("</tr>");
		if(count($topics) > 0)
		{
			foreach($topics as $topic_id=>$topic)
			{
				$bgcolour="rgba(255,255,255,0);";
				if(empty($topic["Approved"]))
				{
					$bgcolour="rgba(255,255,0,0.2);";
				}
				if(!empty($topic["Deleted"]))
				{
					$bgcolour="rgba(255,0,0,0.2);";
				}
				echo("<tr>");
				echo("<td style=\"background-color:$bgcolour\">");
				if(!empty($topic["New"]))
				{
					echo("<img src=\"new.gif\" alt=\"new\" style=\"border:0px;\">");
				}
				if(!empty($topic["Poll"]))
				{
					echo("<img src=\"poll.gif\" alt=\"poll\" style=\"border:0px;\">");
				}
				if(!empty($topic["Locked"]))
				{
					echo("<img src=\"lock.gif\" alt=\"locked\" style=\"border:0px;\">");
				}
				echo("</td>");
				echo("<td style=\"background-color:$bgcolour\"><b>" . $topic["Name"] . "</b><br>Created by " . display_username($topic["Creator"]) . " on " . print_date($topic["Created"]) . "<br>View topic: <a href=\"view_topic.php?topic_id=$topic_id\">From top</a> | <a href=\"view_topic.php?topic_id=$topic_id&unread=y\">From last read post</a> | <a href=\"view_topic.php?topic_id=$topic_id&last_post=y\">From last post</a></td>");
				echo("<td style=\"background-color:$bgcolour text-align:center; vertical-align:middle;\">" . $topic["PostCount"] . "</td>");
				echo("<td style=\"background-color:$bgcolour\">");
				display_last_post($topic_id);
				echo("</td>");
				echo("</tr>");
			}
		}
		else
		{
			echo("<tr>");
			echo("<td><i>There are no topics in this forum to display.</i></td>");
			echo("</tr>");
		}
		echo("</table>");
	}
	else
	{
		echo("<table>");
		echo("<tr>");
		echo("<td><i>You do not have permission to view this forum.</i></td>");
		echo("</tr>");
		echo("</table>");
	}
?>
</body>
</html>