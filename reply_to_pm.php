<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Private messaging system");
	
	$thread_id=0;
	$post_id=-1;
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
	if(isset($_GET['post_id']))
	{
		$post_id=intval(preg_replace("/[^0-9]/","",$_GET['post_id']));
	}
	if(isset($_POST['post_id']))
	{
		$post_id=intval(preg_replace("/[^0-9\-]/","",$_POST['post_id']));
	}
	
	$thread_info=get_private_message($thread_id);
	
	if(($thread_info["User1"] != $_SESSION['user_id'] && $thread_info["User2"] != $_SESSION['user_id']) || ($thread_info["User1"] != $_SESSION['user_id'] && $thread_info["Deleted"] == 1))
	{
		die("<script type=\"text/javascript\">window.location = 'private_messages.php'</script>");
	}
	
	$title="RE: " . $thread_info["Title"];
	if($post_id > -1)
	{
		$post=explode("\n",$thread_info["Posts"][$post_id]["Text"]);
		$lines=array();
		foreach($post as $line)
		{
			$split=str_split($line,100);
			foreach($split as $l)
			{
				$lines[]=$l;
			}
		}
		foreach($lines as $line)
		{
			$body.=" >    " . $line . "\n";
		}
		$title="RE: " . $thread_info["Posts"][$post_id]["Title"];
	}
	
	if(!empty($_POST['s']))
	{
		$title=htmlspecialchars($_POST['title']);
		$body=htmlspecialchars($_POST['body']);
		if($_POST['s'] != "Preview")
		{
			$created_time=time();
			
			$parent_post=max($post_id,0);
			$post=array("User"=>$_SESSION['user_id'],"Parent"=>$parent_post,"Title"=>$title,"Date"=>$created_time,"Text"=>$body,"Approved"=>1,"Deleted"=>0,"ReadBy"=>false);
			$thread_info["Posts"][]=$post;
			
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			
			$query=$db->prepare("UPDATE privatemessages SET lastpost = ?,posts = ? WHERE threadid = ?");
			$query->bindValue(1,$created_time,SQLITE3_INTEGER);
			$query->bindValue(2,serialize($thread_info["Posts"]),SQLITE3_TEXT);
			$query->bindValue(3,$thread_id,SQLITE3_INTEGER);
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
				die("<p><b>Reply creation <span style=\"color:#00FF00;\">SUCCESSFUL</span></b>. You can <a href=\"view_pm.php?thread_id=$thread_id\">go back to the thread</a>, <a href=\"private_messages.php\">go back to your inbox</a>, or <a href=\"index.php\">go home</a>.</p>");
			}
			else
			{
				echo("<p><b>Reply creation <span style=\"color:#FF0000;\">FAILED</span></b>. There was an <b>internal error</b>. Please try again later.</p>");
			}
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Reply To PM</title>
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
<h2>Reply To Private Message "<?php echo $thread_info["Title"]; ?>"</h2>
<p>If you are replying to a specific post, the text from that post will be quoted. <b>Please remove everything except the text that you actually wish to quote</b>! This is a common courtesy, and consistently quoting entire posts, especially if they are large, may result in moderator action.</p>
<?php
	if(!empty($_POST['s']) && !empty($title) && !empty($body))
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
<form method="post" action="reply_to_pm.php">
<input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>">
<input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
Message title: <input type="text" name="title" value="<?php echo $title; ?>" required="required" size="40"><br><br>
Message body:<br>
<textarea name="body" required="required" rows="20" cols="150"><?php echo $body; ?></textarea><br>
Supported BBCodes: [b]###[/b],[i]###[/i],[u]###[/u],[url]###[/url] OR [url=###]optional text here[/url],[img]###[/img]<br><br>
<input type="submit" name="s" value="Send message">&nbsp;&nbsp;&nbsp;<input type="submit" name="s" value="Preview">
</form>
</body>
</html>