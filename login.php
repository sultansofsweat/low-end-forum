<?php
	session_start();
	
	require("functions.php");
	
	record_activity("User logon page");
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	if($init !== true || !empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	if(!empty($_POST['s']))
	{
		$logged_on=false;
		
		$username=strtolower(preg_replace("/[^A-Za-z0-9]/","",$_POST['username']));
		$correct_password="";
		$user_id=0;
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
		
		$query=$db->prepare("SELECT id,password FROM users WHERE username = ?");
		$query->bindValue(1,$username,SQLITE3_TEXT);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$user_id=$result["ID"];
			$correct_password=$result["Password"];
		}
		$results->finalize();
		$query->close();
		
		if(!empty($user_id) && password_verify($_POST['password'],$correct_password) === true)
		{
			$logged_on=true;
			$_SESSION['user_id']=$user_id;
			
			$query=$db->prepare("UPDATE users SET lastlogin = ? WHERE id = ?");
			$query->bindValue(1,time(),SQLITE3_INTEGER);
			$query->bindValue(2,$_SESSION['user_id'],SQLITE3_INTEGER);
			$query->execute();
			$query->close();
		}
		
		$db->close();
		
		if($logged_on === true)
		{
			die("<p><span style=\"color:#00FF00;\">Log on <b>SUCCESSFUL</b></span>: you are now logged in as <b>$username</b>. <a href=\"index.php\">Click here</a> to proceed.</p>");
		}
		else
		{
			echo("<p><span style=\"color:#FF0000;\">Log on <b>FAILED</b></span>: either your password is incorrect or this user doesn't exist. Please correct and try again, or contact the BOFH if problems persist.</p>");
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Log In</title>
<link rel="stylesheet" href="<?php echo $stylesheet; ?>">
</head>
<body>
<?php if(!empty($forum_logo)) { echo("<p><img src=\"avatars/$forum_logo\" alt=\"Low End Forum Logo\" style=\"border:0px;\"></p>"); } ?>
<h1 style="text-decoration:underline;"><?php echo $forum_name; ?></h1>
<h2>Sign On</h2>
<form method="post" action="login.php">
<input type="hidden" name="s" value="y">
Username: <input type="text" name="username" required="required"><br><br>
Password: <input type="password" name="password" required="required"><br><br>
<input type="submit" value="Log in"><br><input type="button" value="Cancel" onclick="window.location.href = 'index.php'">
</form>
</body>
</html>