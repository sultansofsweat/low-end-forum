<?php
	session_start();
	
	require("functions.php");
	
	record_activity("User logoff page");
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	if($init !== true || empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	if(!empty($_POST['s']))
	{
		$_SESSION['user_id']=PHP_INT_MAX;
		unset($_SESSION['user_id']);
		session_destroy();
		die("<p><span style=\"color:#00FF00;\">Log off <b>SUCCESSFUL</b></span>. <a href=\"index.php\">Click here</a> to proceed.</p>");
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Log Off</title>
<link rel="stylesheet" href="<?php echo $stylesheet; ?>">
</head>
<body>
<?php if(!empty($forum_logo)) { echo("<p><img src=\"avatars/$forum_logo\" alt=\"Low End Forum Logo\" style=\"border:0px;\"></p>"); } ?>
<h1 style="text-decoration:underline;"><?php echo $forum_name; ?></h1>
<h2>Sign Off</h2>
<form method="post" action="logout.php">
<input type="hidden" name="s" value="y">
<input type="submit" value="Confirm log out"><br><input type="button" value="Cancel" onclick="window.location.href = 'index.php'">
</form>
</body>
</html>