<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Control Panel");
	
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
	
	if(!empty($_GET['id']))
	{
		$forum_id=intval(preg_replace("/[^0-9]/","",$_GET['id']));
	}
	if(!empty($_POST['id']))
	{
		$forum_id=intval(preg_replace("/[^0-9]/","",$_POST['id']));
	}
	if(empty($all_forums[$forum_id]))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	$topics=get_topics_for_forum($forum_id);
	
	$forum_info=array();
	
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
			
	$query=$db->prepare("SELECT name,modlevel,modoverride,pinnedtopic FROM forums WHERE id = ?");
	$query->bindValue(1,$forum_id,SQLITE3_INTEGER);
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		$forum_info=array("Name"=>$result["Name"],"PinnedTopic"=>$result["PinnedTopic"],"ModLevel"=>$result["ModLevel"],"ModOverride"=>unserialize($result["ModOverride"]));
	}
	$results->finalize();
	$query->close();
	
	if($user_level < $forum_info["ModLevel"] && !in_array($_SESSION['user_id'],$forum_info["ModOverride"]))
	{
		die("<script type=\"text/javascript\">window.location = 'view_forum.php?forum_id=$forum_id'</script>");
	}
	
	if(!empty($_POST['s']))
	{
		$topic_id=intval(preg_replace("/[^0-9]/","",$_POST['topic_id']));
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			
		$query=$db->prepare("UPDATE forums SET pinnedtopic = ? WHERE id = ?");
		$query->bindValue(1,$topic_id,SQLITE3_INTEGER);
		$query->bindValue(2,$forum_id,SQLITE3_INTEGER);
		$result=$query->execute();
		if($result !== false)
		{
			$good=true;
		}
		$query->close();
		
		$db->close();
		
		echo("<p>Updated pinned topic.</p>");
	}
	
	/*if($user_level >= $forum_info["ViewLevel"] || in_array($_SESSION['user_id'],$forum_info["ViewOverride"]))
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
		die("<script type=\"text/javascript\"></script>");
	}*/
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Edit Pinned Topic In Forum: <?php echo $forum_info["Name"]; ?></title>
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
<h2>Change Pinned Topic In Forum: <?php echo $forum_info["Name"]; ?></h2>
<form method="post" action="pin_topic.php">
<input type="hidden" name="s" value="y">
<input type="hidden" name="id" value="<?php echo $forum_id; ?>">
Select a topic to pin: <select name="topic_id">
<option value="">No pinned topic</option>
<?php
	foreach($topics as $id=>$info)
	{
		echo("<option value=\"$id\"");
		if($id == $forum_info["PinnedTopic"])
		{
			echo(" selected=\"selected\"");
		}
		echo(">" . $info["Title"] . " by " . get_username($info["Creator"]) . "</option>");
	}
?>
</select><br><br>
<input type="submit" value="Pin topic"><br><br>
<input type="button" value="Cancel" onclick="window.location = 'view_forum.php?forum_id=<?php echo $forum_id; ?>'">
</form>
</body>
</html>