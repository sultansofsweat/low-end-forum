<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Selector creation page");
	
	$topic_id=0;
	
	$options=array();
	$multi_select=0;
	
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
		$options=explode("\n",htmlspecialchars($_POST['options']));
		foreach(array_keys($options) as $key)
		{
			$options[$key]=trim($options[$key]);
		}
		if(!empty($_POST['multiselect']))
		{
			$multi_select=1;
		}
		
		if(!empty($options))
		{
			$option_array=array();
			foreach($options as $option)
			{
				$option_array[]=array("Name"=>$option,"User"=>false);
			}
			
			$open_time=time();
			
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			
			$query=$db->prepare("INSERT INTO selection(topic,createdby,opened,multiselect,options) VALUES (?,?,?,?,?)");
			$query->bindValue(1,$topic_id,SQLITE3_INTEGER);
			$query->bindValue(2,$_SESSION['user_id'],SQLITE3_INTEGER);
			$query->bindValue(3,$open_time,SQLITE3_INTEGER);
			$query->bindValue(4,$multi_select,SQLITE3_INTEGER);
			$query->bindValue(5,serialize($option_array),SQLITE3_TEXT);
			$query->execute();
			$query->close();
			
			$select_id=0;
			$query=$db->prepare("SELECT id FROM selection WHERE createdby = ? AND opened = ?");
			$query->bindValue(1,$_SESSION['user_id'],SQLITE3_INTEGER);
			$query->bindValue(2,$open_time,SQLITE3_INTEGER);
			$results=$query->execute();
			while($result=$results->fetchArray(SQLITE3_ASSOC))
			{
				$select_id=$result["ID"];
			}
			$results->finalize();
			$query->close();
			
			$db->close();
			
			if(!empty($select_id))
			{
				die("<p><b>Option selector created <span style=\"color:#00FF00;\">SUCCESSFULLY</span></b>. You can <a href=\"view_topic.php?topic_id=$topic_id\">go back to the topic</a>, or <a href=\"index.php\">go home</a>.</p>");
			}
			else
			{
				echo("<p><b>Option selector creation <span style=\"color:#FF0000;\">FAILED</span></b>. There was an <b>internal error</b>. Please try again later.</p>");
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
<title><?php echo $forum_name; ?> - Create Option Selector</title>
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
<h2>Create Option Selector In Topic "<?php echo $topic_info["Title"]; ?>"</h2>
<p>This is largely intended for things like schedule building. Most things probably would be better off with a <a href="create_poll.php?topic_id=<?php echo $topic_id; ?>">poll</a> instead.</p>
<form method="post" action="create_selection.php">
<input type="hidden" name="s" value="y">
<input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
Enter your selection options, one per line, below:<br>
<textarea name="options" required="required" rows="10" cols="50"><?php echo implode("\n",$options); ?></textarea><br><br>
<input type="checkbox" name="multiselect" value="y" <?php if($multi_select == 1) { echo "checked=\"checked\""; } ?>> Allow multiple selections for users<br><br>
<input type="submit" value="Create selector">
</form>
</body>
</html>