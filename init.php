<?php
	session_start();
	
	require("functions.php");
	
	if(get_initialization_status() === true)
	{
		die("<p>This system is already initialized. You can delete this script page now.</p>");
	}
	
	if(!file_exists("/var/www/lef-db/lef-init-key"))
	{
		$key=bin2hex(random_bytes(8));
		$fh=fopen("/var/www/lef-db/lef-init-key",'w');
		fwrite($fh,$key);
		fclose($fh);
	}
	
	if(!empty($_POST['s']))
	{
		$username=strtolower(preg_replace("/[^A-Za-z0-9]/","",$_POST['username']));
		$password=false;
		if($_POST['password'] == $_POST['confirm'])
		{
			$password=password_hash($_POST['password'],PASSWORD_DEFAULT);
		}
		$valid_key=false;
		if($_POST['key'] == file_get_contents("/var/www/lef-db/lef-init-key"))
		{
			$valid_key=true;
		}
		
		if(!empty($username) && !empty($password) && !empty($valid_key))
		{
			unlink("/var/www/lef-db/lef-init-key");
			
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
		
			$query=$db->prepare("INSERT INTO users(username,password,joined,lastlogin,level) VALUES (?,?,?,?,?)");
			$query->bindValue(1,$username,SQLITE3_TEXT);
			$query->bindValue(2,$password,SQLITE3_TEXT);
			$query->bindValue(3,time(),SQLITE3_INTEGER);
			$query->bindValue(4,0,SQLITE3_INTEGER);
			$query->bindValue(5,9,SQLITE3_INTEGER);
			$result=$query->execute();
			if($result === false)
			{
				echo("<p>FAILED TO ADD USER! Expect problems!</p>");
			}
			$query->close();
		
			$query=$db->prepare("DELETE FROM settings WHERE name = ?");
			$query->bindValue(1,"initialized",SQLITE3_TEXT);
			$query->execute();
			$query->close();
			
			$db->close();
			
			echo("<p>Done. You may delete this script now.</p>");
		}
		else
		{
			echo("<p>FAILED! Did you enter something wrong?</p>");
		}
	}
?>
<html>
<head>
<title>Low End Forum Initialization Script</title>
<link rel="stylesheet" href="themes/white.css">
</head>
<body>
<h1 style="text-decoration:underline;">Low End Forum Software Initialization Script</h1>
<p>This script will create your initial administrator account. It will <b>not</b> allow you to change any settings! You need to do this from the control panel after account setup.<br>
The system defaults to having registrations turned off; no one can register until you turn this on.</p>
<form method="post" action="init.php">
<input type="hidden" name="s" value="y">
Username: <input type="text" name="username" required="required"> (letters and numbers ONLY, will be converted to lowercase!)<br><br>
Password: <input type="password" name="password" required="required"><br><br>
Confirm Password: <input type="password" name="confirm" required="required"><br><br>
Init key: <input type="password" name="key" required="required"> (this is found on your server!)<br><br>
<input type="submit" value="Initialize system">
</form>
</body>
</html>