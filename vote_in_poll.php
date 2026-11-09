<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Poll viewer");
	
	$poll_id=0;
	$poll_info=array();
	$topic_info=array();
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	if($init !== true || empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	if(!empty($_POST['id']))
	{
		$poll_id=preg_replace("/[^0-9]/","",$_POST['id']);
	}
	if(empty($poll_id))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	switch($_POST['type'])
	{
		case "informal":
		$poll_info=get_poll($poll_id,false);
		break;
		
		case "formal":
		$poll_info=get_poll($poll_id,true);
		break;
	}
	$topic_info=get_topic($poll_info["Topic"]);
	
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
	
	$db->close();
	
	if(($user_level < $forum_info["PostLevel"] && !in_array($_SESSION['user_id'],$forum_info["PostOverride"])) || $user_level < 6 || $topic_info["Locked"] == 1 || $topic_info["Approved"] == 0 || $topic_info["Deleted"] == 1)
	{
		die("<script type=\"text/javascript\">window.location = 'view_topic.php?topic_id=$topic_id'</script>");
	}
	
	$poll_open=false;
	$poll_closed=true;
	
	if($poll_info["Opened"] < time())
	{
		$poll_open=true;
	}
	if($poll_info["Closed"] >= time())
	{
		$poll_closed=false;
	}
	
	if($poll_open === true && $poll_closed === false)
	{
		switch($_POST['type'])
		{
			case "informal":
			$poll_info["Options"][$_POST['vote']]++;
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			
			$query=$db->prepare("UPDATE informalpolls SET options = ? WHERE id = ?");
			$query->bindValue(1,serialize($poll_info["Options"]),SQLITE3_TEXT);
			$query->bindValue(2,$poll_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			
			$db->close();
			
			die("<script type=\"text/javascript\">window.location = 'view_informal_poll.php?id=$poll_id&voted=y'</script>");
			break;
			
			case "formal":
			$poll_info["Votes"][$_SESSION['user_id']]=$_POST['vote'];
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			
			$query=$db->prepare("UPDATE formalpolls SET votes = ? WHERE id = ?");
			$query->bindValue(1,serialize($poll_info["Votes"]),SQLITE3_TEXT);
			$query->bindValue(2,$poll_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			
			$db->close();
			
			die("<script type=\"text/javascript\">window.location = 'view_formal_poll.php?id=$poll_id&voted=y'</script>");
		}
	}
	else
	{
		die("<script type=\"text/javascript\">window.location = 'view_informal_poll.php?id=$poll_id'</script>");
	}
?>