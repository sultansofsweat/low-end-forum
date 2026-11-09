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
	
	$users=get_all_users();
	$user_id=0;
	
	if(!empty($_GET['id']))
	{
		$user_id=preg_replace("/[^0-9]/","",$_GET['id']);
	}
	if(!empty($_POST['id']))
	{
		$user_id=preg_replace("/[^0-9]/","",$_POST['id']);
	}
	if(empty($users[$user_id]))
	{
		$user_id=0;
	}
	
	if(!empty($_POST['s']))
	{
		$topics_to_update=array();
		
		if(!empty($_POST['delete_posts']))
		{
			$topics=get_all_topics();
			
			foreach($topics as $id=>$topic)
			{
				$update=false;
				foreach(array_keys($topic["Posts"]) as $post_id)
				{
					if($topic["Posts"][$post_id]["User"] == $user_id)
					{
						$topic["Posts"][$post_id]["Deleted"]=true;
						$update=true;
					}
				}
				if($update === true)
				{
					$topics_to_update[$id]=serialize($topic["Posts"]);
				}
			}
		}
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
		
		$query=$db->prepare("UPDATE users SET level = 0 WHERE id = ?");
		$query->bindValue(1,$user_id,SQLITE3_INTEGER);
		$query->execute();
		$query->close();
		
		if(!empty($topics_to_update))
		{
			$topic_id=0;
			$posts="";
			$query=$db->prepare("UPDATE topics SET posts = ? WHERE id = ?");
			$query->bindParam(1,$posts,SQLITE3_TEXT);
			$query->bindParam(2,$topic_id,SQLITE3_INTEGER);
			foreach($topics_to_update as $topic_id=>$posts)
			{
				$query->execute();
				$query->reset();
			}
			$query->close();
		}
		
		$db->close();
		
		echo("<p>Done deleting user.</p>");
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Edit User</title>
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
<h2>Delete User</h2>
<form method="get" action="delete_user.php">
Select a user to delete: <select name="id" required="required">
<option value=""></option>
<?php
	foreach($users as $id=>$username)
	{
		echo("<option value=\"$id\"");
		if($id == $user_id)
		{
			echo(" selected=\"selected\"");
		}
		echo(">$username</option>");
	}
?>
</select><br><br>
<input type="submit" value="Select">
</form>
<hr>
<form method="post" action="delete_user.php">
<input type="hidden" name="s" value="y">
<input type="hidden" name="id" value="<?php echo $user_id; ?>">
<input type="checkbox" name="delete_posts" value="y"> Also delete all of the user's posts (leaves topics intact)<br><br>
<input type="submit" value="Delete user">
</form>
</body>
</html>