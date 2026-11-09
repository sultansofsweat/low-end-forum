<?php
	session_start();
	
	require("functions.php");
	
	record_activity("User registration page");
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	$password_required=registration_password_enabled();
	
	if($init !== true || !empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	if(!empty($_POST['s']))
	{
		$registered=false;
		$registrable=true;
		
		$username=strtolower(preg_replace("/[^A-Za-z0-9]/","",$_POST['username']));
		$tzoffset=intval(preg_replace("/[^0-9\-]/","",$_POST['tzoffset']));
		$age=intval(preg_replace("/[^0-9]/","",$_POST['age']));
		$location=preg_replace("/[^A-Za-z0-9 ]/","",$_POST['location']);
		$bio=htmlspecialchars($_POST['bio']);
		if($password_required === true)
		{
			$new_user_password=false;
			if(!empty($_POST['nup']))
			{
				$new_user_password=password_verify($_POST['nup'],get_registration_password());
			}
		}
		else
		{
			$new_user_password=true;
		}
		
		if(empty($username) || in_array($username,get_all_usernames()))
		{
			echo("<p><b><span style=\"color:#FF0000;\">ERROR</span>: username is invalid or already taken!</b> Registration will fail.</p>");
			$registrable=false;
		}
		if($tzoffset < -12 || $tzoffset > 12)
		{
			echo("<p><b><span style=\"color:#FF0000;\">ERROR</span>: timezone offset is invalid!</b> Registration will fail.</p>");
			$registrable=false;
		}
		if(empty($_POST['password']) || empty($_POST['confirm']) || $_POST['password'] != $_POST['confirm'])
		{
			echo("<p><b><span style=\"color:#FF0000;\">ERROR</span>: password is invalid!</b> Registration will fail.</p>");
			$registrable=false;
		}
		if($new_user_password === false)
		{
			echo("<p><b><span style=\"color:#FF0000;\">ERROR</span>: new user password validation failed!</b> Registration will fail.</p>");
			$registrable=false;
		}
		
		if($registrable === true)
		{
			$user_id=0;
			
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
		
			$query=$db->prepare("INSERT INTO users(username,password,joined,lastlogin,level,age,location,tzoffset,bio) VALUES (?,?,?,?,?,?,?,?,?)");
			$query->bindValue(1,$username,SQLITE3_TEXT);
			$query->bindValue(2,password_hash($_POST['password'],PASSWORD_DEFAULT),SQLITE3_TEXT);
			$query->bindValue(3,time(),SQLITE3_INTEGER);
			$query->bindValue(4,0,SQLITE3_INTEGER);
			$query->bindValue(5,4,SQLITE3_INTEGER);
			$query->bindValue(6,$age,SQLITE3_INTEGER);
			$query->bindValue(7,$location,SQLITE3_TEXT);
			$query->bindValue(8,$tzoffset,SQLITE3_INTEGER);
			$query->bindValue(9,$bio,SQLITE3_TEXT);
			$result=$query->execute();
			if($result === false)
			{
				echo("<p><b><span style=\"color:#FF0000;\">ERROR</span>: failed to add user!</b> Internal error. Registration will fail.</p>");
			}
			$query->close();
			
			$query=$db->prepare("SELECT id FROM users WHERE username = ?");
			$query->bindValue(1,$username,SQLITE3_TEXT);
			$results=$query->execute();
			while($result=$results->fetchArray(SQLITE3_ASSOC))
			{
				$user_id=$result["ID"];
			}
			$results->finalize();
			$query->close();
			
			$db->close();
			
			if(!empty($user_id))
			{
				$registered=true;
			}
		}
		
		if($registered === true)
		{
			die("<p><span style=\"color:#00FF00;\">Registration <b>SUCCESSFUL</b></span>: you are now registered as <b>$username</b>. <a href=\"login.php\">Click here</a> to log in.</p>");
		}
		else
		{
			echo("<p><span style=\"color:#FF0000;\">Registration <b>FAILED</b></span>. Please correct any errors you see above and try again, or contact the BOFH if problems persist.</p>");
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Register</title>
<link rel="stylesheet" href="<?php echo $stylesheet; ?>">
</head>
<body>
<?php if(!empty($forum_logo)) { echo("<p><img src=\"avatars/$forum_logo\" alt=\"Low End Forum Logo\" style=\"border:0px;\"></p>"); } ?>
<h1 style="text-decoration:underline;"><?php echo $forum_name; ?></h1>
<h2>Register For An Account</h2>
<p>Please use the form below to create an account on this forum. Please note that all users must be activated by a forum administrator before posting is allowed!<br>
"<span style="color:#FF0000;"><b>*</b></span>" indicates a required field.</p>
<form method="post" action="register.php">
<input type="hidden" name="s" value="y">
Enter your username<span style="color:#FF0000;"><b>*</b></span>: <input type="text" name="username" required="required"> (lowercase letters and numbers ONLY! Will be converted to lowercase!)<br><br>
Enter your password<span style="color:#FF0000;"><b>*</b></span>: <input type="password" name="password" required="required"><br><br>
Confirm your password<span style="color:#FF0000;"><b>*</b></span>: <input type="password" name="confirm" required="required"><br><br>
Enter your age: <input type="number" name="age" value="0" min="0"><br><br>
Enter your location: <input type="text" name="location"> (letters numbers and spaces ONLY!)<br><br>
Time zone offset<span style="color:#FF0000;"><b>*</b></span>: <input type="number" name="tzoffset" value="0" min="-12" max="12"><br><br>
Enter a short description of yourself. Text only, no code!<br><textarea name="bio" rows="10" cols="50"></textarea><br><br>
New user password<?php if($password_required === true) { echo "<span style=\"color:#FF0000;\"><b>*</b></span>"; } ?>: <input type="password" name="nup" <?php if($password_required === true) { echo "required=\"required\""; } else { echo "disabled=\"disabled\""; } ?>><br><br>
<input type="submit" value="Register"><br><input type="button" value="Cancel" onclick="window.location.href = 'index.php'">
</form>
</body>
</html>