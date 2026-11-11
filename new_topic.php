<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Topic creation page");
	
	$forum_id=0;
	$title="";
	$body="";
	
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
	
	if(!empty($_GET['forum']))
	{
		$forum_id=preg_replace("/[^0-9]/","",$_GET['forum']);
	}
	if(!empty($_POST['forum']))
	{
		$forum_id=preg_replace("/[^0-9]/","",$_POST['forum']);
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
			
	$query=$db->prepare("SELECT id,postlevel,postoverride FROM forums");
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		if($user_level >= $result["PostLevel"] || in_array($_SESSION['user_id'],unserialize($result["PostOverride"])))
		{
			$postable_forums[$result["ID"]]=$all_forums[$result["ID"]];
		}
	}
	$results->finalize();
	$query->close();
	
	$db->close();
	
	if(!empty($_POST['s']))
	{
		$title=htmlspecialchars($_POST['title']);
		$body=htmlspecialchars($_POST['body']);
		
		if($_POST['s'] != "Preview")
		{
			$created_time=time();
			
			if(!empty($postable_forums[$forum_id]) || ($forum_id == PHP_INT_MAX && $user_level >= 8))
			{
				$post=array("User"=>$_SESSION['user_id'],"Parent"=>NULL,"Title"=>$title,"Date"=>$created_time,"Text"=>$body,"Approved"=>0,"Deleted"=>0,"ReadBy"=>array($_SESSION['user_id']));
				if($user_level >= 6)
				{
					$post["Approved"]=1;
				}
				$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
				
				if($forum_id == PHP_INT_MAX)
				{
					$query=$db->prepare("INSERT INTO globaltopics(creator,created,lastpost,posts) VALUES (?,?,?,?)");
					$query->bindValue(1,$_SESSION['user_id'],SQLITE3_INTEGER);
					$query->bindValue(2,$created_time,SQLITE3_INTEGER);
					$query->bindValue(3,$created_time,SQLITE3_INTEGER);
					$query->bindValue(4,serialize(array($post)),SQLITE3_TEXT);
				}
				else
				{
					$query=$db->prepare("INSERT INTO topics(creator,created,lastpost,posts,forum,approved) VALUES (?,?,?,?,?,?)");
					$query->bindValue(1,$_SESSION['user_id'],SQLITE3_INTEGER);
					$query->bindValue(2,$created_time,SQLITE3_INTEGER);
					$query->bindValue(3,$created_time,SQLITE3_INTEGER);
					$query->bindValue(4,serialize(array($post)),SQLITE3_TEXT);
					$query->bindValue(5,$forum_id,SQLITE3_INTEGER);
					if($user_level >= 6)
					{
						$query->bindValue(6,1,SQLITE3_INTEGER);
					}
					else
					{
						$query->bindValue(6,0,SQLITE3_INTEGER);
					}
				}
				$query->execute();
				$query->close();
				
				$topic_id=0;
				if($forum_id == PHP_INT_MAX)
				{
					$query=$db->prepare("SELECT id FROM globaltopics WHERE creator = ? AND created = ?");
					$query->bindValue(1,$_SESSION['user_id'],SQLITE3_INTEGER);
					$query->bindValue(2,$created_time,SQLITE3_INTEGER);
				}
				else
				{
					$query=$db->prepare("SELECT id FROM topics WHERE forum = ? AND creator = ? AND created = ?");
					$query->bindValue(1,$forum_id,SQLITE3_INTEGER);
					$query->bindValue(2,$_SESSION['user_id'],SQLITE3_INTEGER);
					$query->bindValue(3,$created_time,SQLITE3_INTEGER);
				}
				$results=$query->execute();
				while($result=$results->fetchArray(SQLITE3_ASSOC))
				{
					$topic_id=$result["ID"];
				}
				$results->finalize();
				$query->close();
				
				$db->close();
				
				if(!empty($topic_id))
				{
					if($forum_id == PHP_INT_MAX)
					{
						die("<p><b>Topic creation <span style=\"color:#00FF00;\">SUCCESSFUL</span></b>. You can <a href=\"view_global_topic.php?topic_id=$topic_id\">view the new topic</a>, or <a href=\"index.php\">go home</a>.</p>");
					}
					else
					{
						die("<p><b>Topic creation <span style=\"color:#00FF00;\">SUCCESSFUL</span></b>. You can <a href=\"view_topic.php?topic_id=$topic_id\">view the new topic</a>, <a href=\"view_forum.php?forum_id=$forum_id\">go back to the last viewed forum</a>, or <a href=\"index.php\">go home</a>.</p>");
					}
				}
				else
				{
					echo("<p><b>Topic creation <span style=\"color:#FF0000;\">FAILED</span></b>. There was an <b>internal error</b>. Please try again later.</p>");
				}
			}
			else
			{
				echo("<p>You do not have permission to post in this forum! Post will not be made. Please select a forum you can post in to try again.</p>");
			}
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Post New Topic</title>
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
<h2>Post A New Topic</h2>
<?php
	if(!empty($title) && !empty($body))
	{
		echo("<table>");
		echo("<tr>");
		echo("<th colspan=\"2\">POST PREVIEW</th>");
		echo("</tr>");
		echo("<tr>");
		echo("<th style=\"text-align:right;\">Title</th>");
		echo("<td>$title</td>");
		echo("</tr>");
		echo("<tr>");
		echo("<th style=\"text-align:right;\">Content</th>");
		echo("<td>" . nl2br(process_bbcode($body)) . "<hr>");
		display_signature($_SESSION['user_id']);
		echo("</td>");
		echo("</tr>");
		echo("</table>");
		echo("<p></p>");
	}
?>
<form method="post" action="new_topic.php">
Post in forum: <select name="forum" required="required">
<option value=""></option>
<?php
	if($user_level >= 8)
	{
		echo("<option value=\"" . PHP_INT_MAX . "\"");
		if($forum_id == PHP_INT_MAX)
		{
			echo(" selected=\"selected\"");
		}
		echo("\">Global post</option>");
	}
	foreach($postable_forums as $id=>$name)
	{
		echo("<option value=\"$id\"");
		if($id == $forum_id)
		{
			echo(" selected=\"selected\"");
		}
		echo(">$name</option>");
	}
?>
</select><br><br>
Topic title: <input type="text" name="title" value="<?php echo $title; ?>" required="required" size="40"> (this is also your post title)<br><br>
Post body:<br>
<textarea name="body" required="required" rows="20" cols="150"><?php echo $body; ?></textarea><br>
Supported BBCodes: [b]###[/b],[i]###[/i],[u]###[/u],[url]###[/url] OR [url=###]optional text here[/url],[img]###[/img]<br><br>
<input type="submit" name="s" value="Create topic">&nbsp;&nbsp;&nbsp;<input type="submit" name="s" value="Preview">
</form>
</body>
</html>