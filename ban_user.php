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
	$existing_bans=array();
	$active_ban=false;
	
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
		$existing_bans=get_user_bans($user_id);
		foreach($existing_bans as $id=>$ban_details)
		{
			if($ban_details["Expires"] > time() || $ban_details["Expires"] == 0)
			{
				$active_ban=$id;
			}
		}
	}
	
	if(!empty($_POST['s']))
	{
		$banned_user_level=get_user_level($user_id);
		if($banned_user_level < $user_level && $user_id > 1)
		{
			if(!empty($_POST['reuse_ban']))
			{
			}
			else
			{
				$mute=0;
				if(!empty($_POST['mute']))
				{
					$mute=1;
				}
				$internal=htmlspecialchars($_POST['internal']);
				$external=htmlspecialchars($_POST['external']);
				if(!empty($_POST['permanent']))
				{
					$expires=0;
				}
				else
				{
					$month=preg_replace("/[^A-Za-z]/","",$_POST['expire_month']);
					$day=preg_replace("/[^0-9]/","",$_POST['expire_day']);
					$year=preg_replace("/[^0-9]/","",$_POST['expire_year']);
					
					$maximum_day=get_max_day($month,$year);
					
					$day=min($day,$maximum_day);
					
					$expires=strtotime($month . " " . $day . ", " . $year);
				}
				
				$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
				
				$query=$db->prepare("INSERT INTO bans(user,bannedby,bannedon,internal,external,read,expires) VALUES (?,?,?,?,?,?,?)");
				$query->bindValue(1,$user_id,SQLITE3_INTEGER);
				$query->bindValue(2,$_SESSION['user_id'],SQLITE3_INTEGER);
				$query->bindValue(3,time(),SQLITE3_INTEGER);
				$query->bindValue(4,$internal,SQLITE3_TEXT);
				$query->bindValue(5,$external,SQLITE3_TEXT);
				$query->bindValue(6,$mute,SQLITE3_INTEGER);
				$query->bindValue(7,$expires,SQLITE3_INTEGER);
				$query->execute();
				$query->close();
				
				$query=$db->prepare("UPDATE users SET level = ? WHERE id = ?");
				if($mute == 1)
				{
					$query->bindValue(1,3,SQLITE3_INTEGER);
				}
				elseif($expires == 0)
				{
					$query->bindValue(1,1,SQLITE3_INTEGER);
				}
				else
				{
					$query->bindValue(1,2,SQLITE3_INTEGER);
				}
				$query->bindValue(2,$user_id,SQLITE3_INTEGER);
				$query->execute();
				$query->close();
				
				$db->close();
			}
			echo("<p>Finished banning user.</p>");
		}
		else
		{
			echo("<p>You do not have permission to ban this user!</p>");
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Ban User</title>
<link rel="stylesheet" href="<?php echo $stylesheet; ?>">
<script type="text/javascript">
function reuseBan() {
	if(document.getElementById('reuse_ban').checked === true) {
		document.getElementById('mute_checkbox').disabled=true;
		document.getElementById('internal_reason').disabled=true;
		document.getElementById('internal_reason').required=false;
		document.getElementById('external_reason').disabled=true;
		document.getElementById('external_reason').required=false;
		document.getElementById('expire_month').disabled=true;
		document.getElementById('expire_month').required=false;
		document.getElementById('expire_day').disabled=true;
		document.getElementById('expire_day').required=false;
		document.getElementById('expire_year').disabled=true;
		document.getElementById('expire_year').required=false;
		document.getElementById('permaban').disabled=true;
	} else {
		document.getElementById('mute_checkbox').disabled=false;
		document.getElementById('internal_reason').disabled=false;
		document.getElementById('internal_reason').required=true;
		document.getElementById('external_reason').disabled=false;
		document.getElementById('external_reason').required=true;
		document.getElementById('expire_month').disabled=false;
		document.getElementById('expire_month').required=true;
		document.getElementById('expire_day').disabled=false;
		document.getElementById('expire_day').required=true;
		document.getElementById('expire_year').disabled=false;
		document.getElementById('expire_year').required=true;
		document.getElementById('permaban').disabled=false;
	}
}

function permaBan() {
	if(document.getElementById('permaban').checked === true) {
		document.getElementById('expire_month').disabled=true;
		document.getElementById('expire_month').required=false;
		document.getElementById('expire_day').disabled=true;
		document.getElementById('expire_day').required=false;
		document.getElementById('expire_year').disabled=true;
		document.getElementById('expire_year').required=false;
	} else {
		document.getElementById('expire_month').disabled=false;
		document.getElementById('expire_month').required=true;
		document.getElementById('expire_day').disabled=false;
		document.getElementById('expire_day').required=true;
		document.getElementById('expire_year').disabled=false;
		document.getElementById('expire_year').required=true;
	}
}
</script>
</head>
<body>
<?php if(!empty($forum_logo)) { echo("<p><img src=\"avatars/$forum_logo\" alt=\"Low End Forum Logo\" style=\"border:0px;\"></p>"); } ?>
<h1 style="text-decoration:underline;"><?php echo $forum_name; ?></h1>
<table>
<tr>
<td><?php display_header(); ?></td>
</tr>
</table>
<h2>User banning</h2>
<form method="get" action="ban_user.php">
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
<h3>Existing Bans</h3>
<p>Existing bans are shown in chronological order, oldest to newest.</p>
<table>
<tr>
<th>Type</th>
<th>Banned On</th>
<th>Banned By</th>
<th>Reason (internal)</th>
<th>Reason (external)</th>
<th>Ban Expires</th>
</tr>
<?php
	if(count($existing_bans) > 0)
	{
		foreach($existing_bans as $ban)
		{
			echo("<tr>");
			echo("<td>");
			if($ban["Read"] == 1)
			{
				echo("MUTE");
			}
			else
			{
				if($ban["Expires"] == 0)
				{
					echo("BAN");
				}
				else
				{
					echo("SUSPENSION");
				}
			}
			echo("</td>");
			echo("<td>" . print_date($ban["BannedOn"]) . "</td>");
			echo("<td>" . display_username($ban["BannedBy"]) . "</td>");
			echo("<td>" . $ban["Internal"] . "</td>");
			echo("<td>" . $ban["External"] . "</td>");
			echo("<td>");
			if($ban["Expires"] == 0)
			{
				echo("INDEFINITE");
			}
			else
			{
				echo print_date($ban["Expires"]);
			}
			echo("</td>");
			echo("</tr>");
		}
	}
	else
	{
		echo("<tr>");
		echo("<td colspan=\"6\"><i>No prior bans exist on file for this user.</i></td>");
		echo("</tr>");
	}
?>
</table>
<hr>
<h3>Ban This Person</h3>
<form method="post" action="ban_user.php">
<input type="hidden" name="s" value="y">
<input type="hidden" name="id" value="<?php echo $user_id; ?>">
<input type="checkbox" id="reuse_ban" name="reuse_ban" value="y" <?php if($active_ban === false) { echo "disabled=\"disabled\""; } else { echo "onchange=\"reuseBan()\""; } ?>> Re-apply the currently active ban<br><br>
<input type="checkbox" id="mute_checkbox" name="mute" value="y"> User can read forums (i.e. mute, don't suspend/ban)<br><br>
Give a reason INTERNALLY (visible to all moderators):<br><textarea name="internal" id="internal_reason" required="required" rows="10" cols="50"></textarea><br><br>
Give a reason EXTERNALLY (visible to the user and all moderators):<br><textarea name="external" id="external_reason" required="required" rows="10" cols="50"></textarea><br><br>
This ban should expire at midnight on:&nbsp;
<select name="expire_month" id="expire_month" required="required">
<option value=""></option>
<option value="January">January</option>
<option value="February">February</option>
<option value="March">March</option>
<option value="April">April</option>
<option value="May">May</option>
<option value="June">June</option>
<option value="July">July</option>
<option value="August">August</option>
<option value="September">September</option>
<option value="October">October</option>
<option value="November">November</option>
<option value="December">December</option>
</select>&nbsp;&nbsp;&nbsp;
<select name="expire_day" id="expire_day" required="required">
<option value=""></option>
<?php
	for($i=1;$i<=31;$i++)
	{
		echo("<option value=\"$i\">$i</option>");
	}
?>
</select>&nbsp;&nbsp;&nbsp;
<input type="text" id="expire_year" name="expire_year" required="required" value="<?php echo date("Y"); ?>" size="4" maxlength="4">&nbsp;&nbsp;&nbsp;
<input type="checkbox" id="permaban" name="permanent" value="y" onchange="permaBan()"> Ban is indefinite<br>
Note that in the event that the date entered is invalid (e.g. February 31), the day will be modified to the maximum possible.<br><br>
<input type="submit" value="Ban user" <?php if(empty($user_id)) { echo "disabled=\"disabled\""; } ?>>
</form>
</body>
</html>