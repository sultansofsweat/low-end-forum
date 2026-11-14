<?php
	session_start();
	
	require("functions.php");
	
	function sort_users_by_join_oldest($a,$b)
	{
		global $users;
		
		if($users[$a]["Joined"] > $users[$b]["Joined"])
		{
			return 1;
		}
		elseif($users[$a]["Joined"] < $users[$b]["Joined"])
		{
			return -1;
		}
		else
		{
			return 0;
		}
	}
	function sort_users_by_join_newest($a,$b)
	{
		global $users;
		
		if($users[$a]["Joined"] > $users[$b]["Joined"])
		{
			return -1;
		}
		elseif($users[$a]["Joined"] < $users[$b]["Joined"])
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}
	function sort_users_by_login_oldest($a,$b)
	{
		global $users;
		
		if($users[$a]["LastLogin"] > $users[$b]["LastLogin"])
		{
			return 1;
		}
		elseif($users[$a]["LastLogin"] < $users[$b]["LastLogin"])
		{
			return -1;
		}
		else
		{
			return 0;
		}
	}
	function sort_users_by_login_newest($a,$b)
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
	
	$sort_type=0;
	if(!empty($_GET['sort_type']))
	{
		$sort_type=preg_replace("/[^0-9]/","",$_GET['sort_type']);
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
	
	switch($sort_type)
	{
		case 1:
		uksort($users,"sort_users_by_join_newest");
		break;
		
		case 2:
		uksort($users,"sort_users_by_login_oldest");
		break;
		
		case 3:
		uksort($users,"sort_users_by_login_newest");
		break;
		
		case 0:
		default:
		uksort($users,"sort_users_by_join_oldest");
		break;
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
<p>Sort option:&nbsp;
<?php
	if($sort_type == 0)
	{
		echo("<b>JOINED, OLDEST FIRST</b>");
	}
	else
	{
		echo("<a href=\"show_users.php?sort_type=0\">JOINED, OLDEST FIRST</a>");
	}
	echo(" | ");
	if($sort_type == 1)
	{
		echo("<b>JOINED, NEWEST FIRST</b>");
	}
	else
	{
		echo("<a href=\"show_users.php?sort_type=1\">JOINED, NEWEST FIRST</a>");
	}
	echo(" | ");
	if($sort_type == 2)
	{
		echo("<b>LAST SIGN IN, OLDEST FIRST</b>");
	}
	else
	{
		echo("<a href=\"show_users.php?sort_type=2\">LAST SIGN IN, OLDEST FIRST</a>");
	}
	echo(" | ");
	if($sort_type == 3)
	{
		echo("<b>LAST SIGN IN, NEWEST FIRST</b>");
	}
	else
	{
		echo("<a href=\"show_users.php?sort_type=3\">LAST SIGN IN, NEWEST FIRST</a>");
	}
?>
</p>
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
			echo "(" . $user["Rank"] . ")";
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