<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Post editing page");
	
	$topic_id=0;
	$post_id=-1;
	$title="";
	$body="";
	
	$is_moderator=false;
	
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
	
	if(!empty($_GET['topic_id']))
	{
		$topic_id=intval(preg_replace("/[^0-9]/","",$_GET['topic_id']));
	}
	if(!empty($_POST['topic_id']))
	{
		$topic_id=intval(preg_replace("/[^0-9]/","",$_POST['topic_id']));
	}
	if(empty($topic_id))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	if(isset($_GET['post_id']))
	{
		$post_id=intval(preg_replace("/[^0-9]/","",$_GET['post_id']));
	}
	if(isset($_POST['post_id']))
	{
		$post_id=intval(preg_replace("/[^0-9\-]/","",$_POST['post_id']));
	}
	if($post_id < 0)
	{
		die("<script type=\"text/javascript\">window.location = 'view_topic.php?topic_id=$topic_id'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	$topic_info=get_topic($topic_id);
	
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
			
	$query=$db->prepare("SELECT id,modlevel,modoverride FROM forums");
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		if($user_level >= $result["ModLevel"] || in_array($_SESSION['user_id'],unserialize($result["ModOverride"])))
		{
			$is_moderator=true;;
		}
	}
	$results->finalize();
	$query->close();
	
	$db->close();
	
	$post=$topic_info["Posts"][$post_id];
	
	if($is_moderator === false && $post["User"] != $_SESSION['user_id'])
	{
		die("<p>You are not authorized to edit this post! ?<a href=\"view_topic.php?topic_id=$topic_id\">Click here</a> to go back.</p>");
	}
	
	$title=$post["Title"];
	$body=$post["Text"];
	
	if(!empty($_POST['s']))
	{
		$title=htmlspecialchars($_POST['title']);
		$body=htmlspecialchars($_POST['body']);
		$created_time=time();
		
		$body.="\n\n[i]This post was edited by [b]" . get_username($_SESSION['user_id']) . "[/b] on " . print_date($created_time) . ", " . print_tz_offset() . "[/i]";
		
		$topic_info["Posts"][$post_id]["Title"]=$title;
		$topic_info["Posts"][$post_id]["Text"]=$body;
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
		
		$query=$db->prepare("UPDATE topics SET lastpost = ?,posts = ? WHERE id = ?");
		$query->bindValue(1,$created_time,SQLITE3_INTEGER);
		$query->bindValue(2,serialize($topic_info["Posts"]),SQLITE3_TEXT);
		$query->bindValue(3,$topic_id,SQLITE3_INTEGER);
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
			die("<p><b>Reply edited <span style=\"color:#00FF00;\">SUCCESSFULLY</span></b>. You can <a href=\"view_topic.php?topic_id=$topic_id\">go back to the topic</a>, or <a href=\"index.php\">go home</a>.</p>");
		}
		else
		{
			echo("<p><b>Reply edit <span style=\"color:#FF0000;\">FAILED</span></b>. There was an <b>internal error</b>. Please try again later.</p>");
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Edit Post</title>
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
<h2>Edit Post In Topic "<?php echo $topic_info["Title"]; ?>"</h2>
<form method="post" action="edit_post.php">
<input type="hidden" name="s" value="y">
<input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
<input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
Post title: <input type="text" name="title" value="<?php echo $title; ?>" required="required" size="40"><br><br>
Post body:<br>
<textarea name="body" required="required" rows="20" cols="150"><?php echo $body; ?></textarea><br>
Supported BBCodes: [b]###[/b],[i]###[/i],[u]###[/u],[url]###[/url] OR [url=###]optional text here[/url],[img]###[/img]<br><br>
<input type="submit" value="Edit post">
</form>
</body>
</html>