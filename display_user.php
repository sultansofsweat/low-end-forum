<?php
	session_start();
	
	require("functions.php");
	
	record_activity("User information page");
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	if($init !== true || empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'login.php'</script>");
	}
	
	if($user_level < 5)
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$user_id=0;
	if(!empty($_GET['user']))
	{
		$user_id=preg_replace("/[^0-9]/","",$_GET['user']);
	}
	
	if(empty($user_id))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$rank="";
	$last_visited_page="";
	$last_visited_time=0;
	$topic_count=0;
	$post_count=0;
	$active_forum=array("ID"=>0,"Posts"=>0);
	$active_topic=array("ID"=>0,"Posts"=>0);
	
	$user_details=get_user($user_id);
	
	$user_levels=array(0=>"Deleted",1=>"Banned",2=>"Suspended",3=>"Muted",4=>"Unverified",5=>"New",6=>"User",7=>"Moderator",8=>"Global Moderator",9=>"Forum Administrator");
	$user_tags=get_user_tags();
	$forums=get_forums();
	$topics=get_all_topics();
	
	$forum_post_count=array();
	
	foreach($topics as $id=>$topic)
	{
		$topic_post_count=0;
		foreach($topic["Posts"] as $post)
		{
			if($post["User"] == $user_id)
			{
				$topic_post_count++;
			}
		}
		if(empty($forum_post_count[$topic["Forum"]]))
		{
			$forum_post_count[$topic["Forum"]]=0;
		}
		$post_count+=$topic_post_count;
		if($topic["Creator"] == $user_id)
		{
			$topic_count++;
		}
		$forum_post_count[$topic["Forum"]]+=$topic_post_count;
		if($topic_post_count > $active_topic["Posts"])
		{
			$active_topic["ID"]=$id;
			$active_topic["Posts"]=$topic_post_count;
		}
	}
	
	foreach($forum_post_count as $forum_id=>$post_count)
	{
		if($post_count > $active_forum["Posts"])
		{
			$active_forum["ID"]=$forum_id;
			$active_forum["Posts"]=$post_count;
		}
	}
	
	if(!empty($user_details["Tag"]) && !empty($user_tags[$user_details["Tag"]]))
	{
		$rank=format_by_user_level($user_details["Level"],$user_tags[$user_details["Tag"]]);
	}
	else
	{
		$rank=format_by_user_level($user_details["Level"],$user_levels[$user_details["Level"]]);
	}
	
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
	$query=$db->prepare("SELECT page,time FROM lastpage WHERE user = ?");
	$query->bindValue(1,$user_id,SQLITE3_INTEGER);
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		$last_visited_page=$result["Page"];
		$last_visited_time=$result["Time"];
	}
	$results->finalize();
	$query->close();
	
	$db->close();
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Show User</title>
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
<h2>User Information</h2>
<?php
	echo("<table>");
	echo("<tr>");
	echo("<th colspan=\"2\" style=\"font-size:20px;\">Showing user: " . $user_details["Username"] . "</th>");
	echo("</tr>");
	echo("<tr>");
	echo("<td style=\"text-align:right;\"><b>User details</b></td>");
	echo("<td>" . display_avatar($user_id) . "<br>Rank: $rank<br>Age: ");
	if($user_details["Age"] > 0)
	{
		echo($user_details["Age"]);
	}
	else
	{
		echo("unspecified");
	}
	echo("<br>Location: " . $user_details["Location"] . "<br>Timezone: UTC");
	if($user_details["TZOffset"] >= 0)
	{
		echo("+");
	}
	echo($user_details["TZOffset"] . "<br>Joined: ");
	if($user_details["Joined"] > 0)
	{
		echo print_date($user_details["Joined"]);
	}
	else
	{
		echo("<i>never</i>");
	}
	echo("<br>Last signed on: ");
	if($user_details["LastLogin"] > 0)
	{
		echo print_date($user_details["LastLogin"]);
	}
	else
	{
		echo("<i>never</i>");
	}
	if($user_level >= 8)
	{
		echo("<br>Last visited: $last_visited_page on " . print_date($last_visited_time));
	}
	echo("</td>");
	echo("</tr>");
	echo("<tr>");
	echo("<td style=\"text-align:right;\"><b>User statistics</b></td>");
	echo("<td>Number of topics started: $topic_count<br>Number of posts: $post_count<br>Most active forum: ");
	if(!empty($active_forum["ID"]))
	{
		echo "<a href=\"view_forum.php?forum_id=" . $active_forum["ID"] . "\">" . $forums[$active_forum["ID"]] . "</a> with " . $active_forum["Posts"] . " posts";
	}
	echo("<br>Most active topic: ");
	if(!empty($active_topic["ID"]))
	{
		echo "<a href=\"view_topic.php?topic_id=" . $active_topic["ID"] . "\">" . $topics[$active_topic["ID"]]["Title"] . "</a> with " . $active_forum["Posts"] . " posts";
	}
	echo("</td>");
	echo("</tr>");
	echo("<tr>");
	echo("<td style=\"text-align:right;\"><b>User biography</b></td>");
	echo("<td>" . nl2br($user_details["Bio"]) . "</td>");
	echo("</tr>");
	echo("<tr>");
	echo("<td style=\"text-align:right;\"><b>Signature</b></td>");
	echo("<td>");
	display_signature($user_id);
	echo("</td>");
	echo("</tr>");
	if($user_level >= 7 || $user_id == $_SESSION['user_id'])
	{
		echo("<tr>");
		echo("<td style=\"text-align:right;\"><b>Warnings</b></td>");
		echo("<td>Warning count: " . count(get_user_warnings($user_id,false)) . "<br>Active warnings: " . count(get_user_warnings($user_id,true)) . "");
		if($user_level >= 7)
		{
			echo("<br><a href=\"warn_user.php?id=$user_id\">Warn this user</a>");
		}
		echo("</td>");
		echo("</tr>");
	}
	echo("<tr>");
	echo("<td style=\"text-align:right;\"><b>Actions</b></td>");
	echo("<td>");
	if($user_level >= 7)
	{
		echo("<a href=\"ban_user.php?id=$user_id\">Ban this user</a>");
		if($user_level >= 9)
		{
			echo("<br><a href=\"delete_user.php?id=$user_id\">Delete this user</a><br><a href=\"edit_user.php?id=$user_id\">Edit this user's details</a>");
		}
	}
	else
	{
		echo("<i>There are no actions that can be performed on this user.</i>");
	}
	echo("</td>");
	echo("</tr>");
	echo("</table>");
?>
</body>
</html>