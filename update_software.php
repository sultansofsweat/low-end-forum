<?php
	session_start();
	
	require("functions.php");
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	if(empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	if($user_level < 9)
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Finish Software Update</title>
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
<h2 style="text-align:center;">Finish Software Update</h2>
<p>This will make any final changes to the forum database, and will check to make sure that you have everything in place to complete the upgrade.<br>
Please make sure to read the release notes on Github for further actions required!</p>
<hr>
<?php
	echo("<pre>LOW END FORUM SOFTWARE UPGRADE - VERSION 1.2\n\n");
	echo("Checking for presence of emoticon directory...");
	$emoticon_dir_exists=false;
	if(file_exists("/var/www/lef-db/emoticons") && is_dir("/var/www/lef-db/emoticons"))
	{
		$emoticon_dir_exists=true;
	}
	if(!empty($emoticon_dir_exists))
	{
		echo("done.\n");
	}
	else
	{
		echo("FAILED! Please move the \"emoticons\" directory to \"/var/www/lef-db\"!\n");
	}
	echo("Checking for presence of emoticon symlink...");
	$emoticon_link_exists=false;
	if(file_exists("emoticons"))
	{
		$emoticon_link_exists=true;
	}
	if(!empty($emoticon_link_exists))
	{
		echo("done.\n");
	}
	else
	{
		echo("FAILED! Please create a symlink from \"emoticons\" to \"/var/www/lef-db/emoticons\"!\n");
	}
	echo("Checking for writability of emoticon directory...");
	$emoticon_writable=false;
	$fh=fopen("emoticons/test",'w');
	if(!empty($fh))
	{
		fclose($fh);
		unlink("emoticons/test");
		$emoticon_writable=true;
	}
	if(!empty($emoticon_writable))
	{
		echo("done.\n");
	}
	else
	{
		echo("FAILED! Please make \"/var/www/lef-db/emoticons\" writable by the web server user!\n");
	}
	if(!empty($emoticon_dir_exists) && !empty($emoticon_link_exists) && !empty($emoticon_writable))
	{
		echo("Checking for presence of \"emoticons\" setting in database...");
		$emoticon_present=false;
		if(!empty(get_setting("emoticons")))
		{
			$emoticon_present=true;
		}
		if(!empty($emoticon_present))
		{
			echo("PRESENT!\n");
		}
		else
		{
			echo("NOT PRESENT!\n");
		}
		if($emoticon_present === false)
		{
			echo("Adding \"emoticons\" setting to database...");
			$debug=false;
			$emoticon_list="a:40:{i:0;s:7:\"biggrin\";i:1;s:5:\"happy\";i:2;s:7:\"neutral\";i:3;s:3:\"sad\";i:4;s:5:\"angry\";i:5;s:4:\"razz\";i:6;s:9:\"greengrin\";i:7;s:4:\"wink\";i:8;s:3:\"cry\";i:9;s:8:\"confused\";i:10;s:5:\"crazy\";i:11;s:3:\"eek\";i:12;s:3:\"wtf\";i:13;s:3:\"wut\";i:14;s:7:\"problem\";i:15;s:8:\"question\";i:16;s:11:\"exclamation\";i:17;s:4:\"idea\";i:18;s:7:\"thumbup\";i:19;s:9:\"thumbdown\";i:20;s:5:\"angel\";i:21;s:5:\"arrow\";i:22;s:4:\"clap\";i:23;s:4:\"cool\";i:24;s:11:\"embarrassed\";i:25;s:4:\"evil\";i:26;s:7:\"eyeroll\";i:27;s:5:\"geek1\";i:28;s:5:\"geek2\";i:29;s:3:\"lol\";i:30;s:5:\"lolno\";i:31;s:3:\"shh\";i:32;s:6:\"shifty\";i:33;s:4:\"sick\";i:34;s:6:\"silent\";i:35;s:9:\"surprised\";i:36;s:8:\"thinking\";i:37;s:7:\"twisted\";i:38;s:4:\"wave\";i:40;s:4:\"yawn\";}";
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			$query=$db->prepare("INSERT INTO settings(name,value) VALUES (?,?)");
			$query->bindValue(1,"emoticons",SQLITE3_TEXT);
			$query->bindValue(2,serialize($emoticon_list),SQLITE3_TEXT);
			$debug=$query->execute();
			$query->close();
			$db->close();
			if($debug !== false)
			{
				echo("done.\n");
			}
			else
			{
				echo("FAILED! Internal error. Try again later.\n");
			}
		}
		else
		{
			echo("Adding \"emoticons\" setting to database...SKIPPED\n");
		}
	}
	else
	{
		echo("Checking for presence of \"emoticons\" setting in database...SKIPPED\n");
		echo("Adding \"emoticons\" setting to database...SKIPPED\n");
	}
	echo("\nDONE ALL STEPS. Please check above for further actions needed, and manually delete this \"upgrade_software.php\" file!");
	echo("</pre>");
?>
</body>
</html>