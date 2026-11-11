<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Private messaging system");
	
	$user_list=array();
	$title="";
	$body="";
	
	$all_users=array();
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	if($init !== true || empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
	
	$user_id=0;
	$query=$db->prepare("SELECT level FROM users WHERE id = ?");
	$query->bindParam(1,$user_id,SQLITE3_INTEGER);
	foreach(get_all_users() as $user_id=>$username)
	{
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			if($result["Level"] >= 6 || $user_level >= 8)
			{
				$all_users[$user_id]=$username;
			}
		}
		$results->finalize();
		$query->reset();
	}
	$query->close();
	
	$db->close();
	
	if(!empty($_POST['s']))
	{
		$title=htmlspecialchars($_POST['title']);
		$body=htmlspecialchars($_POST['body']);
		
		if($_POST['s'] != "Preview")
		{
			$created_time=time();
			
			foreach($_POST['users'] as $user)
			{
				$user_list[]=intval(preg_replace("/[^0-9]/","",$user));
			}
			
			$post=array("User"=>$_SESSION['user_id'],"Parent"=>NULL,"Title"=>$title,"Date"=>$created_time,"Text"=>$body,"Approved"=>1,"Deleted"=>0,"ReadBy"=>false);
			
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			
			$user_id=0;
			$query=$db->prepare("INSERT INTO privatemessages(user1,user2,created,lastpost,posts,deleted,title) VALUES (?,?,?,?,?,?,?)");
			$query->bindValue(1,$_SESSION['user_id'],SQLITE3_INTEGER);
			$query->bindParam(2,$user_id,SQLITE3_INTEGER);
			$query->bindValue(3,$created_time,SQLITE3_INTEGER);
			$query->bindValue(4,$created_time,SQLITE3_INTEGER);
			$query->bindValue(5,serialize(array($post)),SQLITE3_TEXT);
			$query->bindValue(6,0,SQLITE3_INTEGER);
			$query->bindValue(7,$title,SQLITE3_TEXT);
			foreach($user_list as $user_id)
			{
				$query->execute();
				$query->reset();
			}
			$query->close();
			
			$thread_id=0;
			$query=$db->prepare("SELECT threadid FROM privatemessages WHERE user1 = ? AND user2 = ? AND created = ?");
			$query->bindValue(1,$_SESSION['user_id'],SQLITE3_INTEGER);
			$query->bindValue(2,$user_list[0],SQLITE3_INTEGER);
			$query->bindValue(3,$created_time,SQLITE3_INTEGER);
			$results=$query->execute();
			while($result=$results->fetchArray(SQLITE3_ASSOC))
			{
				$thread_id=$result["ThreadID"];
			}
			$results->finalize();
			$query->close();
			
			$db->close();
			
			if(!empty($thread_id))
			{
				die("<p><b>Private message sent <span style=\"color:#00FF00;\">SUCCESSFULLY</span></b>. You can <a href=\"view_pm.php?thread_id=$thread_id\">view the sent message</a>, <a href=\"private_messages.php\">go back to all your PMs</a>, or <a href=\"index.php\">go home</a>.</p>");
			}
			else
			{
				echo("<p><b>Private message <span style=\"color:#FF0000;\">FAILED TO SEND</span></b>. There was an <b>internal error</b>. Please try again later.</p>");
			}
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - New Private Message</title>
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
<h2>Send a New Message</h2>
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
<form method="post" action="new_pm.php">
Send to the following user(s):<br>
<?php
	foreach($all_users as $id=>$name)
	{
		if($id == $_SESSION['user_id'])
		{
			continue;
		}
		echo("<input type=\"checkbox\" name=\"users[]\" value=\"$id\"");
		if(in_array($id,$user_list))
		{
			echo(" checked=\"checked\"");
		}
		echo("> " . display_username($id) . "<br>");
	}
?>
<br>
Message title: <input type="text" name="title" value="<?php echo $title; ?>" required="required" size="40"> (this is also your post title)<br><br>
Message body:<br>
<textarea name="body" required="required" rows="20" cols="150"><?php echo $body; ?></textarea><br>
Supported BBCodes: [b]###[/b],[i]###[/i],[u]###[/u],[url]###[/url] OR [url=###]optional text here[/url],[img]###[/img]<br><br>
<input type="submit" name="s" value="Send PM">&nbsp;&nbsp;&nbsp;<input type="submit" name="s" value="Preview">
</form>
</body>
</html>