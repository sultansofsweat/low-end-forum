<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Selecting an option");
	
	$sel_id=0;
	$sel_info=array();
	$topic_info=array();
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
	
	if($init !== true || empty($_SESSION['user_id']))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$user_level=get_user_level($_SESSION['user_id']);
	
	if(!empty($_GET['sel_id']))
	{
		$sel_id=preg_replace("/[^0-9]/","",$_GET['sel_id']);
	}
	if(!empty($_POST['sel_id']))
	{
		$sel_id=preg_replace("/[^0-9]/","",$_POST['sel_id']);
	}
	if(empty($sel_id))
	{
		die("<script type=\"text/javascript\">window.location = 'index.php'</script>");
	}
	
	$sel_info=get_selection($sel_id);
	$topic_info=get_topic($sel_info["Topic"]);
	
	$forum_info=array();
	
	$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
			
	$query=$db->prepare("SELECT postlevel,postoverride FROM forums WHERE id = ?");
	$query->bindValue(1,$topic_info["Forum"],SQLITE3_INTEGER);
	$results=$query->execute();
	while($result=$results->fetchArray(SQLITE3_ASSOC))
	{
		$forum_info=array("PostLevel"=>$result["PostLevel"],"PostOverride"=>unserialize($result["PostOverride"]));
	}
	$results->finalize();
	$query->close();
	
	$db->close();
	
	if(($user_level < $forum_info["PostLevel"] && !in_array($_SESSION['user_id'],$forum_info["PostOverride"])) || $user_level < 6 || $topic_info["Locked"] == 1 || $topic_info["Approved"] == 0 || $topic_info["Deleted"] == 1 || $sel_info["Closed"] == 1)
	{
		die("<script type=\"text/javascript\">window.location = 'view_topic.php?topic_id=" . $sel_info["Topic"] . "'</script>");
	}
	
	if(!empty($_POST['s']))
	{
		$option_array=array();
		$proceed=true;
		foreach($sel_info["Options"] as $id=>$option)
		{
			if($option["User"] == $_SESSION['user_id'])
			{
				$option["User"]=false;
			}
			$option_array[$id]=$option;
		}
		
		if($sel_info["MultiSelect"] == 1)
		{
			foreach($_POST['option'] as $option)
			{
				$option=preg_replace("/[^0-9]/","",$option);
				if(empty($option_array[$option]))
				{
					$proceed=false;
				}
				else
				{
					$option_array[$option]["User"]=$_SESSION['user_id'];
				}
			}
		}
		else
		{
			$option=preg_replace("/[^0-9]/","",$_POST['option']);
			if(empty($option_array[$option]))
			{
				$proceed=false;
			}
			else
			{
				$option_array[$option]["User"]=$_SESSION['user_id'];
			}
		}
		
		if($proceed === true)
		{
			$good=false;
			
			$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
				
			$query=$db->prepare("UPDATE selection SET options = ? WHERE id = ?");
			$query->bindValue(1,serialize($option_array),SQLITE3_TEXT);
			$query->bindValue(2,$sel_id,SQLITE3_INTEGER);
			$result=$query->execute();
			if($result !== false)
			{
				$good=true;
			}
			else
			{
				$good=false;
			}
			$query->close();
			
			$db->close();
			
			if($good === true)
			{
				die("<p><b>Option selection <span style=\"color:#00FF00;\">SUCCESSFUL</span></b>. You can <a href=\"view_topic.php?topic_id=" . $sel_info["Topic"] . "\">go back to the topic</a>, or <a href=\"index.php\">go home</a>.</p>");
			}
			else
			{
				echo("<p><b>Option selection <span style=\"color:#FF0000;\">FAILED</span></b>. There was an <b>internal error</b>. Please try again later.</p>");
			}
		}
		else
		{
			echo("<p><b>Option selection <span style=\"color:#FF0000;\">FAILED</span></b>. Your selection was invalid. Please correct and/or try again later.</p>");
		}
	}
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Make Selection: <?php echo $sel_info["Name"]; ?></title>
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
<h2>Option Selection: <?php echo $sel_info["Name"]; ?></h2>
<p>Topic: <?php echo $topic_info["Title"]; ?><br>
Selector opened by: <?php echo display_username($sel_info["CreatedBy"]); ?> on <?php echo print_date($sel_info["Opened"]); ?><br>
<?php
	if($sel_info["Closed"] == 1)
	{
		echo("<br>This selector is <span style=\"font-color:#FF0000;\"><b>CLOSED</b></span>!");
	}
	if($sel_info["Deleted"] == 1)
	{
		echo("<br>This selector is <span style=\"font-color:#FF0000;\"><b>DELETED</b></span>!");
	}
?>
</p>
<table>
<tr>
<td>
<?php
	echo("<a href=\"view_topic.php?topic_id=" . $sel_info["Topic"] . "\">Back to parent topic</a>");
?>
</td>
</tr>
</table>
<p></p>
<h3>Make Your Selection Below!</h3>
<p>Note that this clears any existing selection you may have!</p>
<form method="post" action="add_selection.php">
<input type="hidden" name="s" value="y">
<input type="hidden" name="sel_id" value="<?php echo $sel_id; ?>">
<?php
	foreach($sel_info["Options"] as $id=>$option)
	{
		if($sel_info["MultiSelect"] == 1)
		{
			if($option["User"] !== false)
			{
				if($option["User"] == $_SESSION['user_id'])
				{
					echo("<input type=\"checkbox\" name=\"option[]\" value=\"$id\" checked=\"checked\"> " . $option["Name"] . "<br><br>");
				}
				else
				{
					echo("<input type=\"checkbox\" name=\"option[]\" value=\"$id\" disabled=\"disabled\"> " . $option["Name"] . " (TAKEN)<br><br>");
				}
			}
			else
			{
				echo("<input type=\"checkbox\" name=\"option[]\" value=\"$id\"> " . $option["Name"] . "<br><br>");
			}
		}
		else
		{
			if($option["User"] !== false)
			{
				if($option["User"] == $_SESSION['user_id'])
				{
					echo("<input type=\"radio\" name=\"option\" value=\"$id\" checked=\"checked\"> " . $option["Name"] . "<br><br>");
				}
				else
				{
					echo("<input type=\"radio\" name=\"option\" value=\"$id\" disabled=\"disabled\"> " . $option["Name"] . " (TAKEN)<br><br>");
				}
			}
			else
			{
				echo("<input type=\"radio\" name=\"option\" value=\"$id\"> " . $option["Name"] . "<br><br>");
			}
		}
	}
	if($sel_info["Closed"] == 0 && $sel_info["Deleted"] == 0)
	{
		echo("<input type=\"submit\" value=\"Make selection\">");
	}
	else
	{
		echo("<input type=\"submit\" disabled=\"disabled\" value=\"Selection DISABLED\">");
	}
?>
</form>
</body>
</html>