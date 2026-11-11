<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Control Panel");
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	if($init !== true || empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	if($user_level < 9)
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$forum_id=0;
	$forums=get_forums();
	$forum_info=array();
	$user_info=get_all_users();
	$forum_groups=get_forum_groups();
	$user_levels=array(3=>"Everyone",5=>"Verified users",6=>"Normal users",7=>"Moderators only",9=>"Administrators only",10=>"Custom (use override list)");
	
	if(!empty($_GET['forum_id']))
	{
		$forum_id=preg_replace("/[^0-9]/","",$_GET['forum_id']);
	}
	if(!empty($_POST['forum_id']))
	{
		$forum_id=preg_replace("/[^0-9]/","",$_POST['forum_id']);
	}
	
	if(!empty($forum_id))
	{
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
			
		$query=$db->prepare("SELECT name,description,forumgroup,viewlevel,viewoverride,postlevel,postoverride,modlevel,modoverride,showtoall FROM forums WHERE id = ?");
		$query->bindValue(1,$forum_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$forum_info=array("Name"=>$result["Name"],"Description"=>$result["Description"],"ForumGroup"=>$result["ForumGroup"],"ShowToAll"=>$result["ShowToAll"],"ViewLevel"=>$result["ViewLevel"],"ViewOverride"=>unserialize($result["ViewOverride"]),"PostLevel"=>$result["PostLevel"],"PostOverride"=>unserialize($result["PostOverride"]),"ModLevel"=>$result["ModLevel"],"ModOverride"=>unserialize($result["ModOverride"]));
		}
		$results->finalize();
		$query->close();
		
		$db->close();
	}
	
	if(empty($forum_info))
	{
		$forum_info=array("Name"=>"","Description"=>"","ForumGroup"=>-1,"ShowToAll"=>-1,"ViewLevel"=>-1,"ViewOverride"=>array(),"PostLevel"=>-1,"PostOverride"=>array(),"ModLevel"=>-1,"ModOverride"=>array());
	}
	
	if(!empty($_POST['s']))
	{
		$forum_id=intval(preg_replace("/[^0-9]/","",$_POST['forum_id']));
		$name=htmlspecialchars($_POST['name']);
		$description=htmlspecialchars($_POST['description']);
		$group_id=intval(preg_replace("/[^0-9]/","",$_POST['group']));
		$viewlevel=intval(preg_replace("/[^0-9]/","",$_POST['viewlevel']));
		$rawviewoverride=explode(",",preg_replace("/[^0-9\,]/","",$_POST['viewoverride']));
		$postlevel=intval(preg_replace("/[^0-9]/","",$_POST['postlevel']));
		$rawpostoverride=explode(",",preg_replace("/[^0-9\,]/","",$_POST['postoverride']));
		$modlevel=intval(preg_replace("/[^0-9]/","",$_POST['modlevel']));
		$rawmodoverride=explode(",",preg_replace("/[^0-9\,]/","",$_POST['modoverride']));
		$viewoverride=array();
		$postoverride=array();
		$modoverride=array();
		$users=get_all_users();
		foreach($rawviewoverride as $user)
		{
			$user=intval($user);
			if(in_array($user,array_keys($users)))
			{
				$viewoverride[]=$user;
			}
		}
		foreach($rawpostoverride as $user)
		{
			$user=intval($user);
			if(in_array($user,array_keys($users)))
			{
				$postoverride[]=$user;
			}
		}
		foreach($rawmodoverride as $user)
		{
			$user=intval($user);
			if(in_array($user,array_keys($users)))
			{
				$modoverride[]=$user;
			}
		}
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
		
		$query=$db->prepare("UPDATE forums SET name = ?,description = ?,forumgroup = ?,viewlevel = ?,viewoverride = ?,postlevel = ?,postoverride = ?,modlevel = ?,modoverride = ? WHERE id = ?");
		$query->bindValue(1,$name,SQLITE3_TEXT);
		$query->bindValue(2,$description,SQLITE3_TEXT);
		$query->bindValue(3,$group_id,SQLITE3_INTEGER);
		$query->bindValue(4,$viewlevel,SQLITE3_INTEGER);
		$query->bindValue(5,serialize($viewoverride),SQLITE3_TEXT);
		$query->bindValue(6,$postlevel,SQLITE3_INTEGER);
		$query->bindValue(7,serialize($postoverride),SQLITE3_TEXT);
		$query->bindValue(8,$modlevel,SQLITE3_INTEGER);
		$query->bindValue(9,serialize($modoverride),SQLITE3_TEXT);
		$query->bindValue(10,$forum_id,SQLITE3_INTEGER);
		$query->execute();
		$query->close();
		
		$db->close();
		
		die("<p>Forum has been updated. <a href=\"edit_forum.php\">Click here</a> to proceed.</p>");
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Edit Forum</title>
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
<h2 style="text-align:center;">Edit Forum Information</h2>
<table>
<tr>
<th style="text-align:right;">Select forum</th>
<td>
<form method="get" action="edit_forum.php">
Select a forum to edit: <select name="forum_id" required="required">
<option value=""></option>
<?php
	foreach($forums as $id=>$name)
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
<input type="submit" value="Select">
</form>
</td>
<tr>
<th style="text-align:right;">User list</th>
<td>Use this as a reference for the override lists below!<br><br>
<textarea readonly="readonly" rows="10" cols="50">
<?php
	foreach($user_info as $id=>$username)
	{
		echo("User $username has ID $id\n");
	}
?>
</textarea>
</td>
</tr>
<tr>
<th style="text-align:right;">Edit Forum Details</th>
<td>
<form method="post" action="edit_forum.php">
<input type="hidden" name="s" value="y">
<input type="hidden" name="forum_id" value="<?php echo $forum_id; ?>">
Forum name: <input type="text" name="name" value="<?php echo $forum_info["Name"]; ?>" required="required"><br><br>
Forum description (keep it short!):<br>
<textarea name="description" required="required" rows="10" cols="50"><?php echo $forum_info["Description"]; ?></textarea><br><br>
Forum group: <select name="group" required="required">
<option value=""></option>
<?php
	foreach($forum_groups as $id=>$name)
	{
		echo("<option value=\"$id\"");
		if($id == $forum_info["ForumGroup"])
		{
			echo(" selected=\"selected\"");
		}
		echo(">$name</option>");
	}
?>
</select><br><br>
Select minimum user type to <b>view</b> forum: <select name="viewlevel" required="required">
<option value=""></option>
<?php
	foreach($user_levels as $level=>$text)
	{
		echo("<option value=\"$level\"");
		if($level == $forum_info["ViewLevel"])
		{
			echo(" selected=\"selected\"");
		}
		echo(">$text</option>");
	}
?>
</select><br>
List of users that can <b>view</b> regardless of user level (comma separated list of <b>user IDs</b>): <input type="text" name="viewoverride" value="<?php echo implode(",",$forum_info["ViewOverride"]); ?>"><br><br>
Select minimum user type to <b>post in</b> forum: <select name="postlevel" required="required">
<option value=""></option>
<?php
	foreach($user_levels as $level=>$text)
	{
		echo("<option value=\"$level\"");
		if($level == $forum_info["PostLevel"])
		{
			echo(" selected=\"selected\"");
		}
		echo(">$text</option>");
	}
?>
</select><br>
List of users that can <b>post</b> regardless of user level (comma separated list of <b>user IDs</b>): <input type="text" name="postoverride" value="<?php echo implode(",",$forum_info["PostOverride"]); ?>"><br><br>
Select minimum user type to <b>moderate</b> forum: <select name="modlevel" required="required">
<option value=""></option>
<?php
	foreach($user_levels as $level=>$text)
	{
		echo("<option value=\"$level\"");
		if($level == $forum_info["ModLevel"])
		{
			echo(" selected=\"selected\"");
		}
		echo(">$text</option>");
	}
?>
</select><br>
List of users that can <b>moderate</b> regardless of user level (comma separated list of <b>user IDs</b>): <input type="text" name="modoverride" value="<?php echo implode(",",$forum_info["ModOverride"]); ?>"><br><br>
<input type="submit" value="Edit forum">
</form>
</td>
</tr>
</table>
</body>
</html>