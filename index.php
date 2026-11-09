<?php
	session_start();
	
	require("functions.php");
	
	record_activity("Index page");
	
	$forum_name=get_forum_name();
	$stylesheet=get_stylesheet();
	
	$forum_logo=get_forum_logo();
	
	$init=get_initialization_status();
?>
<html>
<head>
<title><?php echo $forum_name; ?> - Index</title>
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
<p></p>
<?php
	if($init === true)
	{
		display_forums();
		display_statistics();
	}
	else
	{
		echo("<table>");
		echo("<tr>");
		echo("<th style=\"color:#FF0000;\">THIS FORUM IS NOT SET UP YET!</th>");
		echo("</tr>");
		echo("<tr>");
		echo("<td>If you are the forum administrator, please run the initial setup script!<br>If you are not the forum administrator, please wait patiently as something good is coming!</td>");
		echo("</tr>");
		echo("</table>");
	}
?>
</body>
</html>