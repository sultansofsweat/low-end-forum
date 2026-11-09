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
	
	if($user_level < 5)
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$mode="";
	$sel="";
	if(!empty($_GET['mode']))
	{
		$mode=preg_replace("/[^a-z]/","",$_GET['mode']);
	}
	if(!empty($_GET['sel']))
	{
		$sel=preg_replace("/[^a-z]/","",$_GET['sel']);
	}
	if($mode == "mod" && $user_level < 8)
	{
		$mode="";
	}
	if($mode == "admin" && $user_level < 9)
	{
		$mode="";
	}
	
	if(!empty($_POST['s']))
	{
		switch(preg_replace("/[^a-z\-]/","",$_POST['s']))
		{
			case "user-details":
			$age=preg_replace("/[^0-9]/","",$_POST['age']);
			$location=preg_replace("/[^A-Za-z0-9 ]/","",$_POST['location']);
			$bio=htmlspecialchars($_POST['bio']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("UPDATE users SET age = ?,location = ?,bio = ? WHERE id = ?");
			$query->bindValue(1,$age,SQLITE3_INTEGER);
			$query->bindValue(2,$location,SQLITE3_TEXT);
			$query->bindValue(3,$bio,SQLITE3_TEXT);
			$query->bindValue(4,$_SESSION['user_id'],SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "user-avatar":
			if(!empty($_FILES['avatar']) && file_exists($_FILES['avatar']['tmp_name']) && empty($_POST['delete_avatar']))
			{
				$filename="/var/www/lef-db/avatars/avatar_" . $_SESSION['user_id'] . "." . pathinfo($_FILES['avatar']['name'],PATHINFO_EXTENSION);
				
				rename($_FILES['avatar']['tmp_name'],$filename);
				
				$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
				$query=$db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
				$query->bindValue(1,str_replace("/var/www/lef-db/","",$filename),SQLITE3_TEXT);
				$query->bindValue(2,$_SESSION['user_id'],SQLITE3_INTEGER);
				$query->execute();
				$query->close();
				$db->close();
			}
			elseif(!empty($_POST['delete_avatar']))
			{
				$filename=get_user_avatar($_SESSION['user_id']);
				
				unlink($filename);
				
				$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
				$query=$db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
				$query->bindValue(1,"",SQLITE3_TEXT);
				$query->bindValue(2,$_SESSION['user_id'],SQLITE3_INTEGER);
				$query->execute();
				$query->close();
				$db->close();
			}
			break;
			
			case "user-signature":
			$signature=htmlspecialchars($_POST['signature']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("UPDATE users SET signature = ? WHERE id = ?");
			$query->bindValue(1,$signature,SQLITE3_TEXT);
			$query->bindValue(2,$_SESSION['user_id'],SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "user-settings":
			$tzoffset=preg_replace("/[^0-9\-]/","",$_POST['tzoffset']);
			$theme=preg_replace("/[^a-z\.]/","",$_POST['theme']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("UPDATE users SET tzoffset = ?,theme = ? WHERE id = ?");
			$query->bindValue(1,$tzoffset,SQLITE3_TEXT);
			$query->bindValue(2,$theme,SQLITE3_TEXT);
			$query->bindValue(3,$_SESSION['user_id'],SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "user-login":
			$valid=false;
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
			$query=$db->prepare("SELECT password FROM users WHERE id = ?");
			$query->bindValue(1,$_SESSION['user_id'],SQLITE3_INTEGER);
			$results=$query->execute();
			while($result=$results->fetchArray(SQLITE3_ASSOC))
			{
				if(password_verify($_POST['current'],$result["Password"]) === true)
				{
					$valid=true;
				}
			}
			$results->finalize();
			$query->close();
			$db->close();
			if($valid === true)
			{
				if(!empty($_POST['change_username']))
				{
					$username=strtolower(preg_replace("/[^A-Za-z0-9]/","",$_POST['username']));
					if(!in_array($username,get_all_usernames()))
					{
						$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
						$query=$db->prepare("UPDATE users SET username = ? WHERE id = ?");
						$query->bindValue(1,$username,SQLITE3_TEXT);
						$query->bindValue(2,$_SESSION['user_id'],SQLITE3_INTEGER);
						$query->execute();
						$query->close();
						$db->close();
					}
					else
					{
						echo("<p>Username already exists! Skipping...</p>");
					}
				}
				if(!empty($_POST['change_password']) && $_POST['newpass'] == $_POST['newconfirm'])
				{
					$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
					$query=$db->prepare("UPDATE users SET password = ? WHERE id = ?");
					$query->bindValue(1,password_hash($_POST['newpass'],PASSWORD_DEFAULT),SQLITE3_TEXT);
					$query->bindValue(2,$_SESSION['user_id'],SQLITE3_INTEGER);
					$query->execute();
					$query->close();
					$db->close();
				}
				elseif(!empty($_POST['change_password']))
				{
					echo("<p>Passwords do not match! Skipping...</p>");
				}
			}
			else
			{
				echo("<p>Password check failed! Refusing to update settings.</p>");
			}
			break;
			
			case "mod-ban":
			echo("<p>Not implemented! Use the ban page!</p>");
			break;
			
			case "mod-unban":
			$user_id=preg_replace("/[^0-9]/","",$_POST['user']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("UPDATE users SET level = 5 WHERE id = ?");
			$query->bindValue(1,$user_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "mod-moderate":
			$user_id=preg_replace("/[^0-9]/","",$_POST['user']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("UPDATE users SET level = 5 WHERE id = ?");
			$query->bindValue(1,$user_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "mod-unmoderate":
			$user_id=preg_replace("/[^0-9]/","",$_POST['user']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("UPDATE users SET level = 6 WHERE id = ?");
			$query->bindValue(1,$user_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "admin-newgroup":
			$name=htmlspecialchars($_POST['name']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("INSERT INTO forumgroups(name) VALUES (?)");
			$query->bindValue(1,$name,SQLITE3_TEXT);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "admin-editgroup":
			$group_id=preg_replace("/[^0-9]/","",$_POST['group']);
			$name=htmlspecialchars($_POST['name']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("UPDATE forumgroups SET name = ? WHERE id = ?");
			$query->bindValue(1,$name,SQLITE3_TEXT);
			$query->bindValue(2,$group_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "admin-remgroup":
			$group_id=preg_replace("/[^0-9]/","",$_POST['group']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$db->exec("PRAGMA foreign_keys = On");
			$query=$db->prepare("DELETE FROM forumgroups WHERE id = ?");
			$query->bindValue(1,$group_id,SQLITE3_INTEGER);
			$result=$query->execute();
			if($result === false)
			{
				echo("<p>Removal failed! Is the forum group empty? Skipping...</p>");
			}
			$query->close();
			$db->close();
			break;
			
			case "admin-newforum":
			$name=htmlspecialchars($_POST['name']);
			$description=htmlspecialchars($_POST['description']);
			$group_id=intval(preg_replace("/[^0-9]/","",$_POST['group']));
			$viewlevel=intval(preg_replace("/[^0-9]/","",$_POST['viewlevel']));
			$rawviewoverride=explode(",",intval(preg_replace("/[^0-9\,]/","",$_POST['viewoverride'])));
			$postlevel=intval(preg_replace("/[^0-9]/","",$_POST['postlevel']));
			$rawpostoverride=explode(",",intval(preg_replace("/[^0-9\,]/","",$_POST['postoverride'])));
			$modlevel=intval(preg_replace("/[^0-9]/","",$_POST['modlevel']));
			$rawmodoverride=explode(",",intval(preg_replace("/[^0-9\,]/","",$_POST['modoverride'])));
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
			$query=$db->prepare("INSERT INTO forums(name,description,forumgroup,viewlevel,viewoverride,postlevel,postoverride,modlevel,modoverride) VALUES (?,?,?,?,?,?,?,?,?)");
			$query->bindValue(1,$name,SQLITE3_TEXT);
			$query->bindValue(2,$description,SQLITE3_TEXT);
			$query->bindValue(3,$group_id,SQLITE3_INTEGER);
			$query->bindValue(4,$viewlevel,SQLITE3_INTEGER);
			$query->bindValue(5,serialize($viewoverride),SQLITE3_TEXT);
			$query->bindValue(6,$postlevel,SQLITE3_INTEGER);
			$query->bindValue(7,serialize($postoverride),SQLITE3_TEXT);
			$query->bindValue(8,$modlevel,SQLITE3_INTEGER);
			$query->bindValue(9,serialize($modoverride),SQLITE3_TEXT);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			default:
			echo("<p>This feature is not implemented yet! Skipping...");
			break;
			
			case "admin-xfertopics":
			$source=preg_replace("/[^0-9]/","",$_POST['source']);
			$destination=preg_replace("/[^0-9]/","",$_POST['destination']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$db->exec("PRAGMA foreign_keys = On");
			$query=$db->prepare("UPDATE topics SET forum = ? WHERE forum = ?");
			$query->bindValue(1,$destination,SQLITE3_INTEGER);
			$query->bindValue(2,$source,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "admin-remforum":
			$forum_id=preg_replace("/[^0-9]/","",$_POST['forum']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$db->exec("PRAGMA foreign_keys = On");
			$query=$db->prepare("DELETE FROM forums WHERE id = ?");
			$query->bindValue(1,$forum_id,SQLITE3_INTEGER);
			$result=$query->execute();
			if($result === false)
			{
				echo("<p>Removal failed! Is the forum empty? Skipping...</p>");
			}
			$query->close();
			$db->close();
			break;
			
			case "admin-newtag":
			$name=htmlspecialchars($_POST['name']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("INSERT INTO tags(name) VALUES (?)");
			$query->bindValue(1,$name,SQLITE3_TEXT);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "admin-edittag":
			$tag_id=preg_replace("/[^0-9]/","",$_POST['tag']);
			$name=htmlspecialchars($_POST['name']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("UPDATE tags SET name = ? WHERE id = ?");
			$query->bindValue(1,$name,SQLITE3_TEXT);
			$query->bindValue(2,$tag_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "admin-deltag":
			$tag_id=preg_replace("/[^0-9]/","",$_POST['tag']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$db->exec("PRAGMA foreign_keys = On");
			$query=$db->prepare("DELETE FROM tags WHERE id = ?");
			$query->bindValue(1,$tag_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "admin-settag":
			$user_id=preg_replace("/[^0-9]/","",$_POST['user']);
			$tag_id=preg_replace("/[^0-9]/","",$_POST['tag']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$db->exec("PRAGMA foreign_keys = On");
			$query=$db->prepare("UPDATE users SET tag = ? WHERE id = ?");
			$query->bindValue(1,$tag_id,SQLITE3_INTEGER);
			$query->bindValue(2,$user_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "admin-setlevel":
			$user_id=preg_replace("/[^0-9]/","",$_POST['user']);
			$user_level=preg_replace("/[^0-9]/","",$_POST['level']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("UPDATE users SET level = ? WHERE id = ?");
			$query->bindValue(1,$user_level,SQLITE3_INTEGER);
			$query->bindValue(2,$user_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "admin-updatename":
			$forum_name=preg_replace("/[^A-Za-z0-9 ]/","",$_POST['name']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("UPDATE settings SET value = ? WHERE name = ?");
			$query->bindValue(1,$forum_name,SQLITE3_TEXT);
			$query->bindValue(2,"forum_name",SQLITE3_TEXT);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "admin-defaulttheme":
			$forum_themes=unserialize(get_setting("forum_themes"));
			$theme_id=intval(preg_replace("/[^0-9]/","",$_POST['theme']));
			if(!empty($forum_themes[$theme_id]))
			{
				$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
				$query=$db->prepare("UPDATE settings SET value = ? WHERE name = ?");
				$query->bindValue(1,$forum_themes[$theme_id],SQLITE3_TEXT);
				$query->bindValue(2,"default_theme",SQLITE3_TEXT);
				$query->execute();
				$query->close();
				$db->close();
			}
			else
			{
				echo("<p>Invalid theme specified! Skipping...</p>");
			}
			break;
			
			case "admin-registrationpwd":
			if(!empty($_POST['enablepwd']))
			{
				if(!empty($_POST['regpasswd']) && $_POST['regpasswd'] == $_POST['regpwdconf'])
				{
					$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
					$query=$db->prepare("UPDATE settings SET value = ? WHERE name = ?");
					$query->bindValue(1,serialize(array("Enabled"=>true,"Password"=>password_hash($_POST['regpasswd'],PASSWORD_DEFAULT))),SQLITE3_TEXT);
					$query->bindValue(2,"new_user_password",SQLITE3_TEXT);
					$query->execute();
					$query->close();
					$db->close();
				}
				else
				{
					echo("<p>Passwords do not match! Skipping...</p>");
				}
			}
			else
			{
				$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
				$query=$db->prepare("UPDATE settings SET value = ? WHERE name = ?");
				$query->bindValue(1,serialize(array("Enabled"=>false,"Password"=>"")),SQLITE3_TEXT);
				$query->bindValue(2,"new_user_password",SQLITE3_TEXT);
				$query->execute();
				$query->close();
				$db->close();
			}
			break;
			
			case "admin-registration":
			if(!empty($_POST['enablereg']))
			{
				$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
				$query=$db->prepare("UPDATE settings SET value = ? WHERE name = ?");
				$query->bindValue(1,"YES",SQLITE3_TEXT);
				$query->bindValue(2,"registration_allowed",SQLITE3_TEXT);
				$query->execute();
				$query->close();
				$db->close();
			}
			else
			{
				$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
				$query=$db->prepare("UPDATE settings SET value = ? WHERE name = ?");
				$query->bindValue(1,"NO",SQLITE3_TEXT);
				$query->bindValue(2,"registration_allowed",SQLITE3_TEXT);
				$query->execute();
				$query->close();
				$db->close();
			}
			break;
			
			case "admin-updateavatar":
			if(!empty($_FILES['avatar']) && file_exists($_FILES['avatar']['tmp_name']))
			{
				$filename="/var/www/lef-db/avatars/default_avatar_." . pathinfo($_FILES['avatar']['name'],PATHINFO_EXTENSION);
				
				rename($_FILES['avatar']['tmp_name'],$filename);
				
				$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
				$query=$db->prepare("UPDATE settings SET value = ? WHERE name = ?");
				$query->bindValue(1,str_replace("/var/www/lef-db/","",$filename),SQLITE3_TEXT);
				$query->bindValue(2,"avatar_file",SQLITE3_TEXT);
				$query->execute();
				$query->close();
				$db->close();
			}
			break;
			
			case "admin-delavatar":
			unlink(get_setting("avatar_file"));
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("UPDATE settings SET value = ? WHERE name = ?");
			$query->bindValue(1,"",SQLITE3_TEXT);
			$query->bindValue(2,"avatar_file",SQLITE3_TEXT);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "admin-updatelogo":
			if(!empty($_FILES['logo']) && file_exists($_FILES['logo']['tmp_name']))
			{
				$filename="/var/www/lef-db/avatars/forum_logo_." . pathinfo($_FILES['logo']['name'],PATHINFO_EXTENSION);
				
				rename($_FILES['logo']['tmp_name'],$filename);
				
				$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
				$query=$db->prepare("UPDATE settings SET value = ? WHERE name = ?");
				$query->bindValue(1,str_replace("/var/www/lef-db/avatars/","",$filename),SQLITE3_TEXT);
				$query->bindValue(2,"forum_logo",SQLITE3_TEXT);
				$query->execute();
				$query->close();
				$db->close();
			}
			break;
			
			case "admin-dellogo":
			unlink(get_setting("forum_logo"));
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("UPDATE settings SET value = ? WHERE name = ?");
			$query->bindValue(1,"",SQLITE3_TEXT);
			$query->bindValue(2,"forum_logo",SQLITE3_TEXT);
			$query->execute();
			$query->close();
			$db->close();
			break;
			
			case "admin-validate":
			$user_id=preg_replace("/[^0-9]/","",$_POST['user']);
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("UPDATE users SET level = 5 WHERE id = ?");
			$query->bindValue(1,$user_id,SQLITE3_INTEGER);
			$query->execute();
			$query->close();
			$db->close();
			break;
		}
		die("<p>Settings have been updated. <a href=\"control_panel.php\">Click here</a> to proceed.</p>");
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Index</title>
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
<h2 style="text-align:center;">The Control Panel</h2>
<?php
	echo("<table>");
	echo("<tr>");
	if(!empty($sel))
	{
		echo("<td colspan=\"2\" style=\"text-align:center;\">");
		if($mode != "user")
		{
			echo("<a href=\"control_panel.php?mode=user\">User Controls</a>");
		}
		else
		{
			echo("<b>User Controls</b>");
		}
		if($user_level >= 7)
		{
			if($mode != "mod")
			{
				echo(" | <a href=\"control_panel.php?mode=mod\">Moderator Controls</a>");
			}
			else
			{
				echo(" | <b>Moderator Controls</b>");
			}
		}
		if($user_level >= 9)
		{
			if($mode != "admin")
			{
				echo(" | <a href=\"control_panel.php?mode=admin\">Administrator Controls</a>");
			}
			else
			{
				echo(" | <b>Administrator Controls</b>");
			}
		}
		echo("</td>");
	}
	else
	{
		echo("<td style=\"text-align:center;\">");
		if($mode != "user")
		{
			echo("<a href=\"control_panel.php?mode=user\">User Controls</a>");
		}
		else
		{
			echo("<b>User Controls</b>");
		}
		if($user_level >= 7)
		{
			if($mode != "mod")
			{
				echo(" | <a href=\"control_panel.php?mode=mod\">Moderator Controls</a>");
			}
			else
			{
				echo(" | <b>Moderator Controls</b>");
			}
		}
		if($user_level >= 9)
		{
			if($mode != "admin")
			{
				echo(" | <a href=\"control_panel.php?mode=admin\">Administrator Controls</a>");
			}
			else
			{
				echo(" | <b>Administrator Controls</b>");
			}
		}
		echo("</td>");
	}
	echo("</tr>");
	if(!empty($mode))
	{
		switch($mode)
		{
			case "user":
			$options=array("details"=>"User page details","avatar"=>"Avatar","signature"=>"User signature","settings"=>"System settings","login"=>"Login details");
			break;
			
			case "mod":
			$options=array("queue"=>"Moderation queue","ban"=>"Banning features","elevate"=>"Moderated users");
			break;
			
			case "admin":
			$options=array("forumgroups"=>"Edit Forum Groups","forums"=>"Edit Forums","users"=>"User manager","globalsettings"=>"Forum settings","defaultavatar"=>"Default avatar","logo"=>"Forum logo","themes"=>"Themes");
			break;
			
			default:
			$options=array();
			break;
		}
		echo("<tr>");
		if(!empty($sel))
		{
			echo("<td colspan=\"2\" style=\"text-align:center;\">");
			$output=array();
			foreach($options as $id=>$name)
			{
				if($sel != $id)
				{
					$output[]="<a href=\"control_panel.php?mode=$mode&sel=$id\">$name</a>";
				}
				else
				{
					$output[]="<b>$name</b>";
				}
			}
			echo implode(" | ",$output);
			echo("</td>");
		}
		else
		{
			echo("<td style=\"text-align:center;\">");
			$output=array();
			foreach($options as $id=>$name)
			{
				if($sel != $id)
				{
					$output[]="<a href=\"control_panel.php?mode=$mode&sel=$id\">$name</a>";
				}
				else
				{
					$output[]="<b>$name</b>";
				}
			}
			echo implode(" | ",$output);
			echo("</td>");
		}
		echo("</tr>");
	}
	if(!empty($sel))
	{
		echo("<tr>");
		switch($sel)
		{
			case "details":
			$user_details=get_user($_SESSION['user_id']);
			echo("<th style=\"text-align:right;\">User page details</th>");
			echo("<td><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"user-details\">Age: <input type=\"number\" name=\"age\" value=\"" . $user_details["Age"] . "\" min=\"0\"> (enter 0 to hide this information)<br><br>Location: <input type=\"text\" name=\"location\" value=\"" . $user_details["Location"] . "\"> (letters numbers and spaces ONLY)<br><br>Bio:<br>Enter a short description of yourself. Text only, no code!<br><textarea name=\"bio\" rows=\"10\" cols=\"50\">" . $user_details["Bio"] . "</textarea><br><br><input type=\"submit\" value=\"Save\"></form></td>");
			break;
			
			case "avatar":
			$current_avatar=get_user_avatar($_SESSION['user_id']);
			echo("<th style=\"text-align:right;\">User avatar</th>");
			echo("<td>The avatar is shown on your user page, and in all forum posts that you make.<br>If you do not set one here, the forum may be configured to use some default avatar instead, or it may display nothing.<br><br>Avatars will be forced to 100 pixels in width; make sure the image you choose looks OK in this format!<br>Avatars may be JPG or GIF, and can be animated, although please do show compassion for your fellow board members in this regard.<br><br>Current avatar: ");
			if(!empty($current_avatar) && file_exists($current_avatar))
			{
				echo("<img src=\"$current_avatar\" alt=\"avatar\" style=\"width:100px; border:0px;\">");
			}
			else
			{
				echo("<i>User avatar is not set</i>");
			}
			echo("<br><br><form method=\"post\" action=\"control_panel.php\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"s\" value=\"user-avatar\">New avatar: <input type=\"file\" name=\"avatar\" accept=\"image/jpeg,image/gif\"><br><br><input type=\"checkbox\" name=\"delete_avatar\" value=\"y\"> Delete avatar<br><br><input type=\"submit\" value=\"Upload\"></form></td>");
			break;
			
			case "signature":
			$user_details=get_user($_SESSION['user_id']);
			echo("<th style=\"text-align:right;\">User signature</th>");
			echo("<td>The signature appears underneath all of your posts, and may contain anything that a normal post contains.<br><br>Supported BBCodes: [b]###[/b],[i]###[/i],[u]###[/u],[url]###[/url] OR [url=###]optional text here[/url],[img]###[/img]<br><br>Current signature:<br><div style=\"border:1px solid #000000; padding:5px;\">");
			display_signature($_SESSION['user_id']);
			echo("</div><br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"user-signature\">New signature:<br><textarea name=\"signature\" rows=\"20\" cols=\"150\">" . $user_details["Signature"] . "</textarea><br><br><input type=\"submit\" value=\"Save\"></form></td>");
			break;
			
			case "settings":
			$user_details=get_user($_SESSION['user_id']);
			echo("<th style=\"text-align:right;\">User's system settings</th>");
			echo("<td><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"user-settings\">Timezone offset: <input type=\"number\" name=\"tzoffset\" value=\"" . $user_details["TZOffset"] . "\" min=\"-12\" max=\"12\"> (# hours difference from GMT/UTC)<br><br>Forum theme (note: if you do not select a theme here, the system default theme will be used):<br><select name=\"theme\">");
			echo("<option value=\"\">use system default</option>");
			foreach(get_system_themes() as $file=>$name)
			{
				echo("<option value=\"$file\">$name</option>");
			}
			echo("</select><br><br><input type=\"submit\" value=\"Save\"></form></td>");
			break;
			
			case "login":
			$username=get_username($_SESSION['user_id']);
			echo("<th style=\"text-align:right;\">Login information</th>");
			echo("<td><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"user-login\"><input type=\"checkbox\" name=\"change_username\" value=\"y\"> Change my username<br>New username: <input type=\"text\" name=\"username\" value=\"$username\"><br><br><input type=\"checkbox\" name=\"change_password\" value=\"y\"> Change my password<br>New password: <input type=\"password\" name=\"newpass\"><br>Confirm new password: <input type=\"password\" name=\"newconfirm\"><br><br>Enter current password: <input type=\"password\" name=\"current\"><br><br><input type=\"submit\" value=\"Save\"></form></td>");
			break;
			
			case "queue":
			echo("<td><i>Feature to be implemented...</i></td>");
			break;
			
			case "ban":
			echo("<th style=\"text-align:right;\">Ban User</th>");
			echo("<td><i>Feature to be implemented...use the dedicated ban page for the time being.</i></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Unban user</th>");
			echo("<td>This will move the user to moderated status from either being muted, suspended, or banned.<br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"mod-unban\">Select user to unban: <select name=\"user\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach(get_banned_users() as $id=>$username)
			{
				echo("<option value=\"$id\">$username</option>");
			}
			echo("</select><br><br><input type=\"submit\" value=\"Unban\"></form></td>");
			break;
			
			case "elevate":
			echo("<th style=\"text-align:right;\">Demote To Moderated Status</th>");
			echo("<td>This will move the user to moderated \"new user\" status from normal, un-moderated status.<br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"mod-moderate\">Select user to demote: <select name=\"user\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach(get_normal_users() as $id=>$username)
			{
				echo("<option value=\"$id\">$username</option>");
			}
			echo("</select><br><br><input type=\"submit\" value=\"Demote\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Promote To Unmoderated Status</th>");
			echo("<td>This will move the user to \"normal\", un-moderated status from moderated \"new user\" status.<br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"mod-unmoderate\">Select user to promote: <select name=\"user\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach(get_new_users() as $id=>$username)
			{
				echo("<option value=\"$id\">$username</option>");
			}
			echo("</select><br><br><input type=\"submit\" value=\"Promote\"></form></td>");
			break;
			
			case "forumgroups":
			echo("<th style=\"text-align:right;\">Add New Forum Group</th>");
			echo("<td><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-newgroup\">Group name: <input type=\"text\" name=\"name\" required=\"required\"><br><br><input type=\"submit\" value=\"Add\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Rename Forum Group</th>");
			echo("<td><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-editgroup\">Select group to rename: <select name=\"group\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach(get_forum_groups() as $id=>$name)
			{
				echo("<option value=\"$id\">$name</option>");
			}
			echo("</select><br><br>New group name: <input type=\"text\" name=\"name\" required=\"required\"><br><br><input type=\"submit\" value=\"Edit\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Delete Forum Group</th>");
			echo("<td>Note that you cannot delete a forum group that has forums in it!<br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-remgroup\">Select group to delete: <select name=\"group\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach(get_forum_groups() as $id=>$name)
			{
				echo("<option value=\"$id\">$name</option>");
			}
			echo("</select><br><br><input type=\"submit\" value=\"Delete\"></form></td>");
			break;
			
			case "forums":
			$forum_groups=get_forum_groups();
			$user_levels=array(3=>"Everyone",5=>"Verified users",6=>"Normal users",7=>"Moderators only",9=>"Administrators only",10=>"Custom (use override list)");
			$users=get_all_users();
			echo("<th style=\"text-align:right;\">User list</th>");
			echo("<td>Use this as a reference for the override lists below!<br><br><textarea readonly=\"readonly\" rows=\"10\" cols=\"50\">");
			foreach($users as $id=>$username)
			{
				echo("User $username has ID $id\n");
			}
			echo("</textarea></td>");
			echo("</tr>");
			echo("<th style=\"text-align:right;\">Add New Forum</th>");
			echo("<td><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-newforum\">Forum name: <input type=\"text\" name=\"name\" required=\"required\"><br><br>Forum description (keep it short!):<br><textarea name=\"description\" required=\"required\" rows=\"10\" cols=\"50\"></textarea><br><br>Forum group: <select name=\"group\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach($forum_groups as $id=>$name)
			{
				echo("<option value=\"$id\">$name</option>");
			}
			echo("</select><br><br>Select minimum user type to <b>view</b> forum: <select name=\"viewlevel\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach($user_levels as $level=>$text)
			{
				echo("<option value=\"$level\">$text</option>");
			}
			echo("</select><br>List of users that can <b>view</b> regardless of user level (comma separated list of <b>user IDs</b>): <input type=\"text\" name=\"viewoverride\"><br><br>Select minimum user type to <b>post in</b> forum: <select name=\"postlevel\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach($user_levels as $level=>$text)
			{
				echo("<option value=\"$level\">$text</option>");
			}
			echo("</select><br>List of users that can <b>post</b> regardless of user level (comma separated list of <b>user IDs</b>): <input type=\"text\" name=\"postoverride\"><br><br>Select minimum user type to <b>moderate</b> forum: <select name=\"modlevel\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach($user_levels as $level=>$text)
			{
				echo("<option value=\"$level\">$text</option>");
			}
			echo("</select><br>List of users that can <b>moderate</b> regardless of user level (comma separated list of <b>user IDs</b>): <input type=\"text\" name=\"modoverride\"><br><br><input type=\"submit\" value=\"Add\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Edit Forum</th>");
			echo("<td><i>Not implemented here, please see <a href=\"edit_forum.php\">this page</a> instead.</i></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Transfer Topics</th>");
			echo("<td>This will move all topics currently in one forum to another.<br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-xfertopics\">Select source forum: <select name=\"source\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach(get_forums() as $id=>$name)
			{
				echo("<option value=\"$id\">$name</option>");
			}
			echo("</select><br><br>Select destination forum: <select name=\"destination\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach(get_forums() as $id=>$name)
			{
				echo("<option value=\"$id\">$name</option>");
			}
			echo("</select><br><br><input type=\"submit\" value=\"Move topics\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Delete Forum</th>");
			echo("<td>Note that you cannot delete a forum that has topics in it!<br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-remforum\">Select forum to delete: <select name=\"forum\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach(get_forums() as $id=>$name)
			{
				echo("<option value=\"$id\">$name</option>");
			}
			echo("</select><br><br><input type=\"submit\" value=\"Delete\"></form></td>");
			break;
			
			case "users":
			$forum_groups=get_forum_groups();
			$user_levels=array(0=>"Deleted user",1=>"Banned user",2=>"Suspended user (temp ban)",3=>"Muted user (no posting)",4=>"Unverified user",5=>"New user",6=>"Normal user",7=>"Local moderator (can moderate a forum)",8=>"Global moderator (can moderate all forums)",9=>"Administrator");
			$user_tags=get_user_tags();
			$users=get_all_users();
			echo("<th style=\"text-align:right;\">Add New User Tag</th>");
			echo("<td>User tags are custom text \"ranks\" that will show up instead of the generic ones assigned by the forum software.<br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-newtag\">Tag name: <input type=\"text\" name=\"name\" required=\"required\"><br><br><input type=\"submit\" value=\"Add\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Rename User Tag</th>");
			echo("<td><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-edittag\">Select tag to rename: <select name=\"tag\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach($user_tags as $id=>$name)
			{
				echo("<option value=\"$id\">$name</option>");
			}
			echo("</select><br><br>New tag name: <input type=\"text\" name=\"name\" required=\"required\"><br><br><input type=\"submit\" value=\"Edit\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Delete User Tag</th>");
			echo("<td>Anyone with this tag currently will go back to displaying the default user tag assigned by the forum software<br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-deltag\">Select tag to delete: <select name=\"tag\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach($user_tags as $id=>$name)
			{
				echo("<option value=\"$id\">$name</option>");
			}
			echo("</select><br><br><input type=\"submit\" value=\"Delete\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Set User Tag</th>");
			echo("<td><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-settag\">Select user: <select name=\"user\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach($users as $id=>$name)
			{
				echo("<option value=\"$id\">$name</option>");
			}
			echo("</select><br><br>Select user tag: <select name=\"tag\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach($user_tags as $id=>$name)
			{
				echo("<option value=\"$id\">$name</option>");
			}
			echo("</select><br><br><input type=\"submit\" value=\"Set tag\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Change User Level</th>");
			echo("<td>This will adjust the privilege level of the user. User levels are shown in the dialog in order from 0 to 9. <b>Be warned that some of these (in particular, 0-3 and 7) are NOT considered for manual selection</b>, and that selecting them anyway can result in the summoning of nasal demons!<br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-setlevel\">Select user: <select name=\"user\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach($users as $id=>$name)
			{
				echo("<option value=\"$id\">$name</option>");
			}
			echo("</select><br><br>Select new level: <select name=\"level\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach($user_levels as $level=>$text)
			{
				echo("<option value=\"$level\">$text</option>");
			}
			echo("</select><br><br><input type=\"submit\" value=\"Set level\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Edit User Details</th>");
			echo("<td><i>Not implemented here, please see <a href=\"edit_user.php\">this page</a> instead.</i></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Verify user</th>");
			echo("<td>This will move the user to moderated \"new user\" status from \"unverified\" status. It is a one-way action!<br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-validate\">Select user to validate: <select name=\"user\" required=\"required\">");
			echo("<option value=\"\"></option>");
			foreach(get_unverified_users() as $id=>$username)
			{
				echo("<option value=\"$id\">$username</option>");
			}
			echo("</select><br><br><input type=\"submit\" value=\"Validate\"></form></td>");
			break;
			
			case "globalsettings":
			echo("<th style=\"text-align:right;\">Forum Name</th>");
			echo("<td>This will show up at the top of every page on the forum.<br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-updatename\">Forum name: <input type=\"text\" name=\"name\" required=\"required\" value=\"" . get_forum_name() . "\"> (letters, numbers and spaces ONLY!)<br><br><input type=\"submit\" value=\"Update\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Forum Default Theme</th>");
			echo("<td><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-defaulttheme\">Select default theme: <select name=\"theme\" required=\"required\">");
			foreach(unserialize(get_setting("forum_themes")) as $id=>$name)
			{
				echo("<option value=\"$id\"");
				if($name == get_setting("default_theme"))
				{
					echo(" selected=\"selected\"");
				}
				echo(">" . strtoupper(str_replace(".css","",$name)) . "</option>");
			}
			echo("</select><br><br><input type=\"submit\" value=\"Update\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Registration</th>");
			echo("<td><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-registration\"><input type=\"checkbox\" name=\"enablereg\" value=\"y\"");
			if(get_setting("registration_allowed") == "YES")
			{
				echo(" checked=\"checked\"");
			}
			echo("> Enable registration<br><br><input type=\"submit\" value=\"Update\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Registration Password</th>");
			echo("<td><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-registrationpwd\"><input type=\"checkbox\" name=\"enablepwd\" value=\"y\"");
			if(registration_password_enabled() === true)
			{
				echo(" checked=\"checked\"");
			}
			echo("> Enable registration password<br><br>Registration password: <input type=\"password\" name=\"regpasswd\"><br><br>Confirm password: <input type=\"password\" name=\"regpwdconf\"><br><br><input type=\"submit\" value=\"Update\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Software Version Information</th>");
			echo("<td>Low End Forum software version <b>1.0</b><br>Release date: November 8 2026<br>This software is copyright &copy; 2026 <a target=\"_blank\" href=\"https://github.com/sultansofsweat\">sultansofsweat</a>.</td>");
			echo("</tr>");
			break;
			
			case "defaultavatar":
			$current_avatar=get_setting("avatar_file");
			echo("<th style=\"text-align:right;\">Default Avatar</th>");
			echo("<td>This will show up as the avatar for all users who do not set one. Note that the forum avatar will be forced to 100 pixels wide and should be either a JPG or a GIF image!<br><br>Current avatar: ");
			if(!empty($current_avatar) && file_exists($current_avatar))
			{
				echo("<img src=\"$current_avatar\" alt=\"avatar\" style=\"width:100px; border:0px;\">");
			}
			else
			{
				echo("<i>Default avatar is not set</i>");
			}
			echo("<br><br><form method=\"post\" action=\"control_panel.php\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"s\" value=\"admin-updateavatar\">New avatar: <input type=\"file\" name=\"avatar\" accept=\"image/jpeg,image/gif\"><br><br><input type=\"submit\" value=\"Upload\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Delete Default Avatar</th>");
			echo("<td>This will delete the currently set avatar from the system!<br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-delavatar\"><input type=\"submit\" value=\"Delete\"></form></td>");
			echo("</tr>");
			break;
			
			case "logo":
			$current_logo=get_setting("forum_logo");
			echo("<th style=\"text-align:right;\">Default Avatar</th>");
			echo("<td>This will show up at the very top of the page. There are no set restrictions, but please keep in mind that a large logo may cause problems for some users and some browsers!<br><br>Current logo: ");
			if(!empty($current_logo) && file_exists("avatars/$current_logo"))
			{
				echo("<img src=\"avatars/$current_logo\" alt=\"logo\" style=\"border:0px;\">");
			}
			else
			{
				echo("<i>Forum logo is not set</i>");
			}
			echo("<br><br><form method=\"post\" action=\"control_panel.php\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"s\" value=\"admin-updatelogo\">New logo: <input type=\"file\" name=\"logo\" accept=\"image/jpeg,image/gif\"><br><br><input type=\"submit\" value=\"Upload\"></form></td>");
			echo("</tr>");
			echo("<tr>");
			echo("<th style=\"text-align:right;\">Delete Forum Logo</th>");
			echo("<td>This will delete the currently set logo from the system!<br><br><form method=\"post\" action=\"control_panel.php\"><input type=\"hidden\" name=\"s\" value=\"admin-dellogo\"><input type=\"submit\" value=\"Delete\"></form></td>");
			echo("</tr>");
			break;
			
			case "themes":
			echo("<td colspan=\"2\" style=\"text-align:center;\"><i>This feature is not yet implemented.</i></td>");
			break;
		}
		echo("</tr>");
	}
	echo("</table>");
?>
</body>
</html>