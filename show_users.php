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
	
	record_activity("User listing");
	
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
	
	$users=array();
	$user_tags=get_user_tags();
	
	foreach(array_keys(get_all_users()) as $user_id)
	{
		$user=get_user($user_id);
		
		if(!empty($user_tags[$user["Tag"]]))
		{
			$rank=$user_tags[$user["Tag"]];
		}
		else
		{
			$rank="";
		}
		
		$users[$user_id]=array("Rank"=>$rank,"Joined"=>$user["Joined"],"LastLogin"=>$user["LastLogin"]);		
	}
	
	uksort($users,"sort_users");
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
<th>Username</th>
<th>Joined</th>
<th>Last Signed In</th>
</tr>
<?php
	foreach($users as $id=>$user)
	{
		echo("<tr>");
		echo("<td style=\"text-align:center;\"><a href=\"display_user.php?user=$id\">" . display_username($id) . "</a>&nbsp;&nbsp;&nbsp;");
		if(!empty($user["Rank"]))
		{
			echo $user["Rank"];
		}
		echo("</td>");
		echo("<td style=\"text-align:center;\">" . print_date($user["Joined"]) . "</td>");
		echo("<td style=\"text-align:center;\">" . print_date($user["LastLogin"]) . "</td>");
		echo("</tr>");
	}
?>
</table>
</body>
</html>