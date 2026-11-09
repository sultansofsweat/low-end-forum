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
	
	if($user_level < 7)
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$users=get_all_users();
	$user_id=0;
	$warnings=array();
	
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
	
	if(!empty($user_id))
	{
		$warnings=get_user_warnings($user_id,false);
	}
	
	if(!empty($_POST['s']))
	{
		$warned_user_level=get_user_level($user_id);
		if($warned_user_level < $user_level && $user_id > 1)
		{
			$internal=htmlspecialchars($_POST['internal']);
			$external=htmlspecialchars($_POST['external']);
			
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			
			$query=$db->prepare("INSERT INTO warnings(user,warnedby,warnedon,internal,external) VALUES (?,?,?,?,?)");
			$query->bindValue(1,$user_id,SQLITE3_INTEGER);
			$query->bindValue(2,$_SESSION['user_id'],SQLITE3_INTEGER);
			$query->bindValue(3,time(),SQLITE3_INTEGER);
			$query->bindValue(4,$internal,SQLITE3_TEXT);
			$query->bindValue(5,$external,SQLITE3_TEXT);
			$query->execute();
			$query->close();
			
			$db->close();
			echo("<p>Finished warning user.</p>");
		}
		else
		{
			echo("<p>You do not have permission to warn this user!</p>");
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Warn User</title>
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
<h2>User warning</h2>
<form method="get" action="warn_user.php">
Select a user: <select name="id" required="required">
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
<h3>Existing Warnings</h3>
<p>Existing warnings are shown in chronological order, oldest to newest. Warnings expire automatically after 90 days; this is shown on the table as well.</p>
<table>
<tr>
<th></th>
<th>Warned On</th>
<th>Warned By</th>
<th>Reason (internal)</th>
<th>Reason (external)</th>
<th>Warning Expires</th>
</tr>
<?php
	if(count($warnings) > 0)
	{
		foreach($warnings as $id=>$warning)
		{
			echo("<tr>");
			echo("<td><a href=\"warn_user.php?delete_warning=$id\">Remove warning</a></td>");
			echo("<td>" . print_date($warning["WarnedOn"]) . "</td>");
			echo("<td>" . display_username($warning["WarnedBy"]) . "</td>");
			echo("<td>" . $warning["Internal"] . "</td>");
			echo("<td>" . $warning["External"] . "</td>");
			echo("<td>" . print_date($warning["WarnedOn"]+90*24*60*60) . "</td>");
			echo("</tr>");
		}
	}
	else
	{
		echo("<tr>");
		echo("<td colspan=\"6\"><i>No prior warnings exist on file for this user.</i></td>");
		echo("</tr>");
	}
?>
</table>
<hr>
<h3>Warn This Person</h3>
<form method="post" action="warn_user.php">
<input type="hidden" name="s" value="y">
<input type="hidden" name="id" value="<?php echo $user_id; ?>">
Give a reason INTERNALLY (visible to all moderators):<br><textarea name="internal" id="internal_reason" required="required" rows="10" cols="50"></textarea><br><br>
Give a reason EXTERNALLY (visible to the user and all moderators):<br><textarea name="external" id="external_reason" required="required" rows="10" cols="50"></textarea><br><br>
<input type="submit" value="Warn user" <?php if(empty($user_id)) { echo "disabled=\"disabled\""; } ?>>
</form>
</body>
</html>