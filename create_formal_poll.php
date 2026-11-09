<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Poll creation page");
	
	$topic_id=0;
	$options=array();
	$opens=0;
	$closes=0;
	$all_users=array();
	$user_list=array();
	
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
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	$topic_info=get_topic($topic_id);
	
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
	
	$user_id=0;
	$query=$db->prepare("SELECT level FROM users WHERE id = ?");
	$query->bindParam(1,$user_id,SQLITE3_INTEGER);
	foreach(get_all_users() as $user_id=>$username)
	{
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			if($result["Level"] >= 6)
			{
				$all_users[$user_id]=$username;
			}
		}
		$results->finalize();
		$query->reset();
	}
	$query->close();
	
	$db->close();
	
	if(($user_level < $forum_info["PostLevel"] && !in_array($_SESSION['user_id'],$forum_info["PostOverride"])) || $user_level < 6 || $topic_info["Locked"] == 1 || $topic_info["Approved"] == 0 || $topic_info["Deleted"] == 1)
	{
		die("<script type=\"text/javascript\">window.location = 'view_topic.php?topic_id=$topic_id'</script>");
	}
	
	if(!empty($_POST['s']))
	{
		foreach($_POST['users'] as $user)
		{
			$user_list[]=intval(preg_replace("/[^0-9]/","",$user));
		}
		$options=explode("\n",htmlspecialchars($_POST['options']));
		foreach(array_keys($options) as $key)
		{
			$options[$key]=trim($options[$key]);
		}
		$open=preg_replace("/[^0-9]/","",$_POST['open']);
		$open_count=preg_replace("/[^0-9]/","",$_POST['open_count']);
		$open_mod=preg_replace("/[^0-9]/","",$_POST['open_mod']);
		$close=preg_replace("/[^0-9]/","",$_POST['close']);
		$close_count=preg_replace("/[^0-9]/","",$_POST['close_count']);
		$close_mod=preg_replace("/[^0-9]/","",$_POST['close_mod']);
		
		$open_time=0;
		$close_time=0;
		$current_time=time();
		
		if($open == 0)
		{
			$open_time=$current_time;
		}
		else
		{
			if($open_count > 0 && in_array($open_mod,array(60,3600,86400)))
			{
				$open_time=$current_time+($open_count*$open_mod);
			}
		}
		if($close == 0)
		{
			$close_time=PHP_INT_MAX;
		}
		else
		{
			if($close_count > 0 && in_array($close_mod,array(60,3600,86400)))
			{
				$close_time=$current_time+($close_count*$close_mod);
			}
		}
		
		if($open_time > 0 && $close_time > 0 && !empty($options) && !empty($user_list))
		{
			$option_array=array();
			foreach($options as $option)
			{
				$option_array[base64_encode($option)]=0;
			}
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			
			$query=$db->prepare("INSERT INTO formalpolls(topic,createdby,opened,closed,deleted,options,voters,votes) VALUES (?,?,?,?,?,?,?,?)");
			$query->bindValue(1,$topic_id,SQLITE3_INTEGER);
			$query->bindValue(2,$_SESSION['user_id'],SQLITE3_INTEGER);
			$query->bindValue(3,$open_time,SQLITE3_INTEGER);
			$query->bindValue(4,$close_time,SQLITE3_INTEGER);
			$query->bindValue(5,0,SQLITE3_INTEGER);
			$query->bindValue(6,serialize($option_array),SQLITE3_TEXT);
			$query->bindValue(7,serialize($user_list),SQLITE3_TEXT);
			$query->bindValue(8,serialize(array()),SQLITE3_TEXT);
			$query->execute();
			$query->close();
			
			$poll_id=0;
			$query=$db->prepare("SELECT id FROM formalpolls WHERE createdby = ? AND opened = ? AND closed = ?");
			$query->bindValue(1,$_SESSION['user_id'],SQLITE3_INTEGER);
			$query->bindValue(2,$open_time,SQLITE3_INTEGER);
			$query->bindValue(3,$close_time,SQLITE3_INTEGER);
			$results=$query->execute();
			while($result=$results->fetchArray(SQLITE3_ASSOC))
			{
				$poll_id=$result["ID"];
			}
			$results->finalize();
			$query->close();
			
			$db->close();
			
			if(!empty($poll_id))
			{
				die("<p><b>Poll created <span style=\"color:#00FF00;\">SUCCESSFULLY</span></b>. You can <a href=\"view_formal_poll.php?id=$poll_id\">view the new poll</a>, <a href=\"view_topic.php?topic_id=$topic_id\">go back to the topic</a>, or <a href=\"index.php\">go home</a>.</p>");
			}
			else
			{
				echo("<p><b>Poll creation <span style=\"color:#FF0000;\">FAILED</span></b>. There was an <b>internal error</b>. Please try again later.</p>");
			}
		}
		else
		{
			echo("<p><b>Poll creation <span style=\"color:#FF0000;\">FAILED</span></b>. Check your submitted data.</p>");
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Create Poll</title>
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
<h2>Create Poll In Topic "<?php echo $topic_info["Title"]; ?>"</h2>
<form method="post" action="create_formal_poll.php">
<input type="hidden" name="s" value="y">
<input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
Accept votes from the following user(s):<br>
<?php
	foreach($all_users as $id=>$name)
	{
		echo("<input type=\"checkbox\" name=\"users[]\" value=\"$id\"");
		if(in_array($id,$user_list))
		{
			echo(" checked=\"checked\"");
		}
		echo("> " . display_username($id) . "<br>");
	}
?>
<br>
Enter your vote options, one per line, below:<br>
<textarea name="options" required="required" rows="10" cols="50"><?php echo implode("\n",$options); ?></textarea><br><br>
Poll opens: <input type="radio" name="open" value="0" required="required"> Now | <input type="radio" name="open" value="1" required="required"> In: <input type="number" style="width:50px;" name="open_count" value="1" min="1">&nbsp;<select name="open_mod">
<option value="86400">days</option>
<option value="3600">hours</option>
<option value="60">minutes</option>
</select> from now<br><br>
Poll closes: <input type="radio" name="close" value="0" required="required"> Until I close it | <input type="radio" name="close" value="1" required="required"> In: <input type="number" style="width:50px;" name="close_count" value="1" min="1">&nbsp;<select name="close_mod">
<option value="86400">days</option>
<option value="3600">hours</option>
<option value="60">minutes</option>
</select> from now<br><br>
<input type="submit" value="Create poll">
</form>
</body>
</html>