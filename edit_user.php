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
	
	$age=0;
	$location="";
	$bio="";
	$signature="";
	if(!empty($user_id))
	{
		$user_details=get_user($user_id);
		$age=$user_details["Age"];
		$location=$user_details["Location"];
		$bio=$user_details["Bio"];
		$signature=$user_details["Signature"];
	}
	
	if(!empty($_POST['s']))
	{
		$age=preg_replace("/[^0-9]/","",$_POST['age']);
		$location=preg_replace("/[^A-Za-z0-9 ]/","",$_POST['location']);
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
		
		$query=$db->prepare("UPDATE users SET age = ?,location = ? WHERE id = ?");
		$query->bindValue(1,$age,SQLITE3_INTEGER);
		$query->bindValue(2,$location,SQLITE3_TEXT);
		$query->bindValue(3,$user_id,SQLITE3_INTEGER);
		$query->execute();
		$query->close();
		
		if(!empty($_POST['del_avatar']))
		{
			$filename=get_user_avatar($user_id);
				
			unlink($filename);
			
			$query=$db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
			$query->bindValue(1,"",SQLITE3_TEXT);
			$query->bindValue(2,$user_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
		}
		
		if(!empty($_POST['del_bio']))
		{
			$query=$db->prepare("UPDATE users SET bio = ? WHERE id = ?");
			$query->bindValue(1,"Bio deleted by moderator",SQLITE3_TEXT);
			$query->bindValue(2,$user_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
		}
		
		if(!empty($_POST['del_sig']))
		{
			$query=$db->prepare("UPDATE users SET signature = ? WHERE id = ?");
			$query->bindValue(1,"Signature deleted by moderator",SQLITE3_TEXT);
			$query->bindValue(2,$user_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
		}
		
		$db->close();
		
		echo("<p>Done editing user.</p>");
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
<h2>Edit User</h2>
<form method="get" action="edit_user.php">
Select a user to edit: <select name="id" required="required">
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
<form method="post" action="edit_user.php">
<input type="hidden" name="s" value="y">
<input type="hidden" name="id" value="<?php echo $user_id; ?>">
Age: <input type="number" name="age" value="<?php echo $age; ?>" min="0"><br><br>
Location: <input type="text" name="location" value="<?php echo $location; ?>"><br><br>
Current avatar: <?php display_avatar($user_id); ?><br>
Delete avatar: <input type="checkbox" name="del_avatar" value="y"> YES<br><br>
Current bio:<br><textarea readonly="readonly" rows="10" cols="50"><?php echo $bio; ?></textarea><br>
Delete bio: <input type="checkbox" name="del_bio" value="y"> YES<br><br>
Current signature:<br><textarea readonly="readonly" rows="20" cols="150"><?php echo $signature; ?></textarea><br>
Delete signature: <input type="checkbox" name="del_sig" value="y"> YES<br><br>
<input type="submit" value="Edit user">
</form>
</body>
</html>