<?php
	session_start();
	
	require("functions.php");
	
	function sort_users($a,$b)
	{
		global $users;
		
		if($users[$a]["LastLogin"] > $users[$b]["LastLogin"])
		{
			return -1;
		}
		elseif($users[$a]["LastLogin"] < $users[$b]["LastLogin"])
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}
	
	record_activity("User activity");
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	if($init !== true || empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'login.php'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	if($user_level < 3)
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$activity=array();
	$user_activity=array();
	$guest_activity=array();
	
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
	
	$query=$db->prepare("SELECT user,page,time FROM lastpage");
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		$user_activity[]=array("User"=>$result["User"],"Page"=>$result["Page"],"Time"=>$result["Time"]);
	}
	$results->finalize();
	$query->close();
	$query=$db->prepare("SELECT ip,page,time FROM guestactivity");
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		$guest_activity[]=array("IPAddress"=>$result["IP"],"Page"=>$result["Page"],"Time"=>$result["Time"]);
	}
	$results->finalize();
	$query->close();
	
	$db->close();
	
	foreach($user_activity as $a)
	{
		if($user_level >= 7 || ($a["Time"]+5*60) > time())
		{
			$activity[]=$a;
		}
	}
	foreach($guest_activity as $a)
	{
		if($user_level >= 7 || ($a["Time"]+5*60) > time())
		{
			if($user_level < 7)
			{
				$a["IPAddress"]="Guest";
			}
			$activity[]=$a;
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - User List</title>
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
<h2>Forum User List</h2>
<p>Users are sorted by <b>last log on time</b>.</p>
<table>
<tr>
<th>User</th>
<th>Timestamp</th>
<th>Accessed Page</th>
</tr>
<?php
	foreach($activity as $a)
	{
		echo("<tr>");
		echo("<td style=\"text-align:center;\">");
		if(!empty($a["User"]))
		{
			echo("<a href=\"display_user.php?user=" . $a["User"] . "\">" . display_username($a["User"]) . "</a>");
		}
		elseif(!empty($a["IPAddress"]))
		{
			echo("<i>" . $a["IPAddress"] . "</a>");
		}
		echo("</td>");
		echo("<td style=\"text-align:center;\">" . print_date($a["Time"]) . "</td>");
		echo("<td style=\"text-align:center;\">" . $a["Page"] . "</td>");
		echo("</tr>");
	}
?>
</table>
</body>
</html>