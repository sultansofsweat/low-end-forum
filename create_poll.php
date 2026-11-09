<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Poll creation page");
	
	$topic_id=0;
	
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
	
	$db->close();
	
	if(($user_level < $forum_info["PostLevel"] && !in_array($_SESSION['user_id'],$forum_info["PostOverride"])) || $user_level < 6 || $topic_info["Locked"] == 1 || $topic_info["Approved"] == 0 || $topic_info["Deleted"] == 1)
	{
		die("<script type=\"text/javascript\">window.location = 'view_topic.php?topic_id=$topic_id'</script>");
	}
	
	if(!empty($_POST['s']))
	{
		switch(preg_replace("/[^0-9]/","",$_POST['type']))
		{
			case 1:
			die("<script type=\"text/javascript\">window.location = 'create_informal_poll.php?topic_id=$topic_id'</script>");
			break;
			
			case 2:
			die("<script type=\"text/javascript\">window.location = 'create_formal_poll.php?topic_id=$topic_id'</script>");
			break;
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
<p>Please choose a poll type below.</p>
<form method="post" action="create_poll.php">
<input type="hidden" name="s" value="y">
<input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
<input type="radio" name="type" value="1" required="required"> <b>INFORMAL</b>: anyone with posting privileges for the topic in question can vote. Usernames are not recorded. Vote tallies are shown even before the vote closes.<br><br>
<input type="radio" name="type" value="2" required="required"> <b>FORMAL</b>: only users you specify can vote. Everyone on the list MUST vote (or abstain from voting). Usernames ARE recorded. Vote tallies are NOT shown until the vote closes.<br><br>
<input type="submit" value="Select type">
</form>
</body>
</html>