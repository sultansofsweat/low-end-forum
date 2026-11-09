<?php
	function get_setting($name)
	{
		$value="";
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT value FROM settings WHERE name = ?");
		$query->bindValue(1,$name,SQLITE3_TEXT);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$value=$result["Value"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($value);
	}
	
	function get_user($user_id)
	{
		$user_details=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT username,joined,lastlogin,level,age,location,tzoffset,bio,signature,tag,avatar,theme FROM users WHERE id = ?");
		$query->bindValue(1,$user_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			foreach($result as $key=>$value)
			{
				$user_details[$key]=$value;
			}
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($user_details);
	}
	
	function get_user_stylesheet($user_id)
	{
		$stylesheet="";
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT theme FROM users WHERE id = ?");
		$query->bindValue(1,$user_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$stylesheet=$result["Theme"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($stylesheet);
	}
	
	function get_user_level($user_id)
	{
		$level="";
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT level FROM users WHERE id = ?");
		$query->bindValue(1,$user_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$level=$result["Level"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($level);
	}
	
	function get_username($user_id)
	{
		$username="";
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT username FROM users WHERE id = ?");
		$query->bindValue(1,$user_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$username=$result["Username"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($username);
	}
	
	function get_tz_offset($user_id=NULL)
	{
		if(empty($user_id) && !empty($_SESSION['user_id']))
		{
			$user_id=$_SESSION['user_id'];
		}
		else
		{
			return 0;
		}
		
		$tzoffset=0;
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT tzoffset FROM users WHERE id = ?");
		$query->bindValue(1,$user_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$tzoffset=$result["TZOffset"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($tzoffset);
	}
	
	function get_forum_name()
	{
		return get_setting("forum_name");
	}
	
	function get_stylesheet()
	{
		if(!empty($_SESSION['user_id']) && !empty(get_user_stylesheet($_SESSION['user_id'])))
		{
			return "themes/" . get_user_stylesheet($_SESSION['user_id']);
		}
		else
		{
			return "themes/" . get_setting("default_theme");
		}
	}
	
	function get_forum_logo()
	{
		return get_setting("forum_logo");
	}
	
	function get_initialization_status()
	{
		if(get_setting("initialized") == "NO")
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	function display_header()
	{
		if(!empty($_SESSION['user_id']))
		{
			if(get_user_level($_SESSION['user_id']) >= 5)
			{
				echo("<b>Hello, " . display_username($_SESSION['user_id']) . "!</b> <a href=\"index.php\">Home</a> | <a href=\"logout.php\">Log off</a> | ");
				if(check_new_pm($_SESSION['user_id']) === true)
				{
					echo("<a href=\"private_messages.php\"><b>Private Messages <img src=\"new.gif\" alt=\"new\" style=\"border:0px;\"></b></a>");
				}
				else
				{
					echo("<a href=\"private_messages.php\">Private Messages</a>");
				}
				echo(" | <a href=\"show_users.php\">User List</a> | <a href=\"control_panel.php\">Control Panel</a>");
				if(get_user_level($_SESSION['user_id']) >= 8)
				{
					echo(" | <a href=\"new_topic.php?forum=" . PHP_INT_MAX . "\">Post New Global Topic</a>");
				}
			}
			else
			{
				echo("<b>Hello, " . display_username($_SESSION['user_id']) . "!</b> <a href=\"index.php\">Home</a> | <a href=\"logout.php\">Log off</a>");
			}
		}
		else
		{
			echo("<b>Hello, visitor!</b> <a href=\"index.php\">Home</a> | <a href=\"login.php\">Log on</a>");
			if(get_setting("registration_allowed") == "YES")
			{
				echo(" | <a href=\"register.php\">Register</a>");
			}
		}
		echo(" | The current date is: " . print_date() . ", " . print_tz_offset());
	}
	
	function display_username($user_id)
	{
		$text="";
		
		$username=get_username($user_id);
		
		switch(get_user_level($user_id))
		{
			case 0:
			case 1:
			case 2:
			$text="<span style=\"color:#FFFFFF; background-color:#000000; text-decoration:line-through;\"><b>" . $username . "</b></span>";
			break;
			
			case 3:
			$text="<span style=\"color:#FFFFFF; background-color:#000000; text-decoration:line-through;\">" . $username . "</span>";
			break;
			
			case 4:
			$text="<span style=\"color:#FFFFFF; background-color:#00FFFF;\"><b>" . $username . "</b></span>";
			break;
			
			case 5:
			$text="<span style=\"color:#FFFFFF; background-color:#00FFFF;\">" . $username . "</span>";
			break;
			
			case 7:
			$text="<span style=\"color:#FFFFFF; background-color:#00FF00;\"><b>" . $username . "</b></span>";
			break;
			
			case 8:
			$text="<span style=\"color:#000000; background-color:#00FF00;\"><b>" . $username . "</b></span>";
			break;
			
			case 9:
			$text="<span style=\"color:#000000; background-color:#FF0000;\"><b>" . $username . "</b></span>";
			break;
			
			case 6:
			default:
			$text=$username;
			break;
		}
		
		return($text);
	}
	
	function format_by_user_level($user_level,$input)
	{
		$text="";
		
		switch($user_level)
		{
			case 0:
			case 1:
			case 2:
			$text="<span style=\"text-decoration:line-through;\"><b>" . $input . "</b></span>";
			break;
			
			case 3:
			$text="<span style=\"text-decoration:line-through;\">" . $input . "</span>";
			break;
			
			case 4:
			$text="<span style=\"color:#FFFFFF; background-color:#00FFFF;\"><b>" . $input . "</b></span>";
			break;
			
			case 5:
			$text="<span style=\"color:#FFFFFF; background-color:#00FFFF;\">" . $input . "</span>";
			break;
			
			case 7:
			$text="<span style=\"color:#FFFFFF; background-color:#00FF00;\"><b>" . $input . "</b></span>";
			break;
			
			case 8:
			$text="<span style=\"color:#000000; background-color:#00FF00;\"><b>" . $input . "</b></span>";
			break;
			
			case 9:
			$text="<span style=\"color:#000000; background-color:#FF0000;\"><b>" . $input . "</b></span>";
			break;
			
			case 6:
			default:
			$text=$input;
			break;
		}
		
		return($text);
	}
	
	function display_forums()
	{
		if(!empty($_SESSION['user_id']))
		{
			if(get_user_level($_SESSION['user_id']) > 0)
			{
				$forumgroups=array();
				$forums=array();
				$forum_info=array();
				$topics=array();
				
				$warnings=get_user_warnings($_SESSION['user_id'],true);
				$user_bans=get_active_ban_for_user($_SESSION['user_id']);
				
				if(!empty($user_bans))
				{
					echo("<table>");
					echo("<tr>");
					echo("<td style=\"text-align:center; font-size:40px; color:#FF0000;\"><b>");
					switch(get_user_level($_SESSION['user_id']))
					{
						case 1:
						echo("You have been BANNED from this forum!");
						break;
						
						case 2:
						echo("You have been SUSPENDED from this forum!");
						break;
						
						case 3:
						echo("You have been MUTED on this forum!");
						break;
					}
					echo("</b></td>");
					echo("</tr>");
					echo("<tr>");
					echo("<td style=\"text-align:center;\">There is an active ban on the system for your username. This ban was put in to place on " . print_date($user_bans["BannedOn"]) . " and ");
					if($user_bans["Expires"] == 0)
					{
						echo("is <b>indefinite.</b><br><br>");
					}
					else
					{
						echo("expires on <b>" . print_date($user_bans["Expires"]) . ".</b><br><br>");
					}
					echo("Reason for this ban: " . $user_bans["Reason"] . "<br><br><b>If you believe this ban is unwarranted</b>: if there is an appeals forum available below, you may post an appeal there. Otherwise, you must contact the system administrator.<br><br><span style=\"color:#FF0000;\">Attempting to circumvent this ban is <b>a serious violation of the rules</b> and will be sanctioned accordingly!</span></td>");
					echo("</tr>");
					echo("</table>");
					echo("<p></p>");
				}
				if(!empty($warnings))
				{
					echo("<table>");
					echo("<tr>");
					echo("<td style=\"text-align:center; font-size:40px; color:#FF0000;\"><b>There are WARNINGS on your account!</b></td>");
					echo("</tr>");
					echo("<tr>");
					echo("<td style=\"text-align:center;\">There is at least one currently active warning associated with your username. A list of them is below.<br><br>Warnings are not bans, but may be the pre-cursor to further moderation action if they accumulate! Please take them seriously.<br><br>Active warning(s):<br>");
					foreach($warnings as $warning)
					{
						echo("<b>Warned on " . print_date($warning["WarnedOn"]) . ": " . $warning["External"] . "</b><br>");
					}
					echo("<br>Warnings <b>may NOT be appealed</b> and automatically expire after <b>90 days</b>.<span style=\"color:#FF0000;\">To repeat: <b>accumulation</b> of warnings will be sanctioned accordingly!</span></td>");
					echo("</tr>");
					echo("</table>");
					echo("<p></p>");
				}
				
				$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
			
				$query=$db->prepare("SELECT id,name FROM forumgroups");
				$results=$query->execute();
				while($result=$results->fetchArray(SQLITE3_ASSOC))
				{
					$forumgroups[$result["ID"]]=$result["Name"];
					$forums[$result["ID"]]=array();
				}
				$results->finalize();
				$query->close();
			
				$query=$db->prepare("SELECT id,forumgroup,name,description,viewlevel,viewoverride FROM forums");
				$results=$query->execute();
				while($result=$results->fetchArray(SQLITE3_ASSOC))
				{
					if(get_user_level($_SESSION['user_id']) >= $result["ViewLevel"] || in_array($_SESSION['user_id'],unserialize($result["ViewOverride"])))
					{
						$forums[$result["ForumGroup"]][]=$result["ID"];
						$forum_info[$result["ID"]]=array("Name"=>$result["Name"],"Description"=>$result["Description"],"TopicCount"=>0,"PostCount"=>0,"LastTopic"=>0,"Unread"=>false);
					}
				}
				$results->finalize();
				$query->close();
				
				$db->close();
				
				$all_topics=get_all_topics();
				$last_posted_time=0;
				foreach($all_topics as $topic_id=>$topic_info)
				{
					$forum=$topic_info["Forum"];
					$posts=$topic_info["Posts"];
					
					$forum_info[$forum]["TopicCount"]++;
					$forum_info[$forum]["PostCount"]+=count($posts);
					
					if($topic_info["LastPost"] >= $last_posted_time)
					{
						$forum_info[$forum]["LastTopic"]=$topic_id;
						$last_posted_time=$topic_info["LastPost"];
					}
					
					if($forum_info[$forum]["Unread"] === false)
					{
						foreach($posts as $post)
						{
							if(!in_array($_SESSION['user_id'],$post["ReadBy"]) && (($post["Approved"] == 1 && $post["Deleted"] == 0) || get_user_level($_SESSION['user_id']) >= 7))
							{
								$forum_info[$forum]["Unread"]=true;
								break;
							}
						}
					}
				}
				
				foreach($forumgroups as $group_id=>$group_name)
				{
					echo("<table>");
					echo("<tr>");
					echo("<th colspan=\"5\">$group_name</th>");
					echo("</tr>");
					foreach($forums[$group_id] as $forum_id)
					{
						echo("<tr>");
						echo("<td>");
						if($forum_info[$forum_id]["Unread"] === true)
						{
							echo("<img src=\"new.gif\" alt=\"New\" style=\"border:0px;\">");
						}
						echo("</td>");
						echo("<td><a href=\"view_forum.php?forum_id=$forum_id\"><span style=\"font-size:20px;\"><b>" . $forum_info[$forum_id]["Name"] . "</b></span></a><br>" . $forum_info[$forum_id]["Description"] . "</td>");
						echo("<td># topics:<br><b>" . $forum_info[$forum_id]["TopicCount"] . "</b></td>");
						echo("<td># posts:<br><b>" . $forum_info[$forum_id]["PostCount"] . "</b></td>");
						echo("<td>Most recent post:<br>");
						display_topic_info($forum_info[$forum_id]["LastTopic"]);
						echo("</td>");
						echo("</tr>");
					}
					echo("</table>");
					echo("<p></p>");
				}
			}
			else
			{
				echo("<table>");
				echo("<tr>");
				echo("<th>Your user account is deleted!</th>");
				echo("</tr>");
				echo("<tr>");
				echo("<td style=\"text-align:center;\">Your account has been deleted, either by yourself or by a system administrator. No further actions are permitted. You may sign up for the forum again, but note that any and all activity will not be transferred, nor will your username be reusable.<br><br>Please contact the system administrator for more information.</td>");
				echo("</tr>");
				echo("</table>");
				echo("<p></p>");
			}
		}
		else
		{
			echo("<table>");
			echo("<tr>");
			echo("<td style=\"text-align:center;\"><i>You must be signed in to view the forum.</i></td>");
			echo("</tr>");
			echo("</table>");
			echo("<p></p>");
		}
	}
	
	function display_topic_info($topic_id)
	{
		echo("<i>To be implemented</i>");
	}
	
	function display_statistics()
	{
		$user_count=0;
		$topic_count=0;
		$post_count=0;
		$active_user_count=0;
		$active_guest_count=0;
		
		$newest_user=0;
		
		$active_user_list=array();
		$active_users=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT id FROM users");
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$user_count++;
			$newest_user=$result["ID"];
		}
		$results->finalize();
		$query->close();
		
		$query=$db->prepare("SELECT id,posts FROM topics WHERE deleted = 0");
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$topic_count++;
			foreach(unserialize($result["Posts"]) as $post)
			{
				if($post["Approved"] == 1 && $post["Deleted"] == 0)
				{
					$post_count++;
				}
			}
		}
		$results->finalize();
		$query->close();
		
		$time=time()-(5*60);
		
		$query=$db->prepare("SELECT user FROM lastpage WHERE time >= ?");
		$query->bindValue(1,$time,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$active_user_count++;
			$active_user_list[]=$result["User"];
		}
		$results->finalize();
		$query->close();
		
		foreach($active_user_list as $user)
		{
			$active_users[]="<a href=\"display_user.php?user=$user\">" . display_username($user) . "</a>";
		}
		
		$query=$db->prepare("SELECT ip FROM guestactivity WHERE time >= ?");
		$query->bindValue(1,$time,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$active_guest_count++;
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		echo("<table>");
		echo("<tr>");
		echo("<th colspan=\"2\">FORUM STATISTICS</th>");
		echo("</tr>");
		echo("<tr>");
		echo("<th>Active Users</th>");
		echo("<td>This is based on users with activity within the last 5 minutes.<br>");
		if(count($active_users) > 0)
		{
			echo implode(", ",$active_users);
		}
		else
		{
			echo("<i>There are no active signed-in users at this time.</i>");
		}
		echo("<br>There are a total of <b>" . ($active_user_count+$active_guest_count) . "</b> active users: <b>$active_user_count</b> signed in and <b>$active_guest_count</b> guests.<br><a href=\"show_activity.php\">Show user activity</a></td>");
		echo("</tr>");
		echo("<tr>");
		echo("<th>Counts</th>");
		echo("<td>We have <b>$user_count</b> users and <b>$topic_count</b> topics with <b>$post_count</b> individual posts.");
		echo("</td>");
		echo("</tr>");
		echo("<tr>");
		echo("<th>Newest User</th>");
		echo("<td>Say hello to our newest user, <a href=\"display_user.php?user=$newest_user\">" . display_username($newest_user) . "</a></td>");
		echo("</tr>");
		echo("</table>");
	}
	
	function record_activity($page_name)
	{
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
		
		if(!empty($_SESSION['user_id']))
		{
			$query=$db->prepare("REPLACE INTO lastpage(user,page,time) VALUES (?,?,?)");
			$query->bindValue(1,$_SESSION['user_id'],SQLITE3_INTEGER);
			$query->bindValue(2,$page_name,SQLITE3_TEXT);
			$query->bindValue(3,time(),SQLITE3_INTEGER);
		}
		else
		{
			$query=$db->prepare("REPLACE INTO guestactivity(ip,page,time) VALUES (?,?,?)");
			$query->bindValue(1,$_SERVER['REMOTE_ADDR'],SQLITE3_TEXT);
			$query->bindValue(2,$page_name,SQLITE3_TEXT);
			$query->bindValue(3,time(),SQLITE3_INTEGER);
		}
		$query->execute();
		$query->close();
		
		$db->close();
	}
	
	function get_user_avatar($user_id)
	{
		$avatar="";
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT avatar FROM users WHERE id = ?");
		$query->bindValue(1,$user_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$avatar=$result["Avatar"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($avatar);
	}
	
	function get_avatar($user_id)
	{
		$user_avatar=get_user_avatar($user_id);
		$system_avatar=get_setting("avatar_file");
		
		if(!empty($user_avatar) && file_exists($user_avatar))
		{
			return $user_avatar;
		}
		elseif(!empty($system_avatar) && file_exists($system_avatar))
		{
			return $system_avatar;
		}
		else
		{
			return "";
		}
	}
	
	function display_signature($user_id)
	{
		$signature="";
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT signature FROM users WHERE id = ?");
		$query->bindValue(1,$user_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$signature=$result["Signature"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		$signature=process_bbcode($signature);
		
		echo($signature);
	}
	
	function process_bbcode($text)
	{
		$text=str_replace("[b]","<b>",$text);
		$text=str_replace("[/b]","</b>",$text);
		$text=str_replace("[i]","<i>",$text);
		$text=str_replace("[/i]","</i>",$text);
		$text=str_replace("[u]","<u>",$text);
		$text=str_replace("[/u]","</u>",$text);
		
		$text=preg_replace("/\[url\](.*?)\[\/url\]/","<a href=\"\\1\" target=\"_blank\">\\1</a>",$text);
		$text=preg_replace("/\[url\=(.*?)\](.*?)\[\/url\]/","<a href=\"\\1\" target=\"_blank\">\\2</a>",$text);
		$text=preg_replace("/\[img\](.*?)\[\/img\]/","<a href=\"\\1\" target=\"_blank\"><img src=\"\\1\" alt=\"image\" style=\"width:150px; border:0px;\"></a>",$text);
		
		return $text;
	}
	
	function get_system_themes()
	{
		$themes=array();
		
		$rawthemes=get_setting("forum_themes");
		
		foreach(unserialize($rawthemes) as $theme)
		{
			$themes[$theme]=strtoupper(str_replace(".css","",$theme));
		}
		
		return($themes);
	}
	
	function get_all_usernames()
	{
		$usernames=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT username FROM users");
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$usernames[]=$result["Username"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($usernames);
	}
	
	function get_all_users()
	{
		$users=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT id,username FROM users");
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$users[$result["ID"]]=$result["Username"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($users);
	}
	
	function get_banned_users()
	{
		$users=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT id,username FROM users WHERE level <= 3 AND level > 0");
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$users[$result["ID"]]=$result["Username"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($users);
	}
	
	function get_normal_users()
	{
		$users=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT id,username FROM users WHERE level = 6");
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$users[$result["ID"]]=$result["Username"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($users);
	}
	
	function get_new_users()
	{
		$users=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT id,username FROM users WHERE level = 5");
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$users[$result["ID"]]=$result["Username"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($users);
	}
	
	function get_unverified_users()
	{
		$users=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT id,username FROM users WHERE level = 4");
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$users[$result["ID"]]=$result["Username"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($users);
	}
	
	function get_forum_groups()
	{
		$groups=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT id,name FROM forumgroups");
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$groups[$result["ID"]]=$result["Name"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($groups);
	}
	
	function get_forums()
	{
		$forums=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT id,name FROM forums");
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$forums[$result["ID"]]=$result["Name"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($forums);
	}
	
	function get_user_tags()
	{
		$tags=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT id,name FROM tags");
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$tags[$result["ID"]]=$result["Name"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($tags);
	}
	
	function registration_password_enabled()
	{
		$password_info=unserialize(get_setting("new_user_password"));
		
		return($password_info["Enabled"]);
	}
	
	function get_registration_password()
	{
		$password_info=unserialize(get_setting("new_user_password"));
		
		return($password_info["Password"]);
	}
	
	function display_avatar($user_id)
	{
		$avatar=get_avatar($user_id);
		if(!empty($avatar) && file_exists($avatar))
		{
			echo("<img src=\"$avatar\" alt=\"avatar\" style=\"width:100px; border:0px;\">");
		}
		else
		{
			echo("");
		}
	}
	
	function display_user_avatar($user_id)
	{
		$avatar=get_user_avatar($user_id);
		if(!empty($avatar) && file_exists($avatar))
		{
			echo("<img src=\"$avatar\" alt=\"avatar\" style=\"width:100px; border:0px;\">");
		}
		else
		{
			echo("");
		}
	}
	
	function adjust_timestamp($input)
	{
		$output=0;
		
		$offset=get_tz_offset();
		
		return($input+($offset*60*60));
	}
	
	function get_all_topics()
	{
		$topics=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT id,forum,creator,created,lastpost,deleted,posts FROM topics");
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$topic=array();
			foreach($result as $key=>$value)
			{
				$topic[$key]=$value;
			}
			$topic["Posts"]=unserialize($result["Posts"]);
			$topic["Title"]=$topic["Posts"][0]["Title"];
			$topics[$result["ID"]]=$topic;
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($topics);
	}
	
	function get_topics_for_forum($forum_id)
	{
		$topics=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT id,creator,created,lastpost,deleted,posts,approved,locked FROM topics WHERE forum = ?");
		$query->bindValue(1,$forum_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$topic=array();
			foreach($result as $key=>$value)
			{
				$topic[$key]=$value;
			}
			$topic["Posts"]=unserialize($result["Posts"]);
			$topic["Title"]=$topic["Posts"][0]["Title"];
			$topics[$result["ID"]]=$topic;
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($topics);
	}
	
	function get_topic($topic_id,$global_topic=false)
	{
		$topic=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		if($global_topic === true)
		{
			$query=$db->prepare("SELECT creator,created,lastpost,deleted,posts FROM globaltopics WHERE id = ?");
			$topic["Locked"]=0;
			$topic["Approved"]=1;
		}
		else
		{
			$query=$db->prepare("SELECT forum,creator,created,lastpost,deleted,posts,approved,locked FROM topics WHERE id = ?");
		}
		$query->bindValue(1,$topic_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			foreach($result as $key=>$value)
			{
				$topic[$key]=$value;
			}
			$topic["Posts"]=unserialize($result["Posts"]);
			$topic["Title"]=$topic["Posts"][0]["Title"];
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($topic);
	}
	
	function print_date($timestamp=NULL)
	{
		if($timestamp === NULL)
		{
			$timestamp=time();
		}
		return date("D, d F Y @ h:i A",adjust_timestamp($timestamp));
	}
	
	function print_tz_offset()
	{
		$output="UTC";
		$offset=get_tz_offset();
		if($offset > 0)
		{
			$output.="+" . $offset;
		}
		elseif($offset < 0)
		{
			$output.=$offset;
		}
		return $output;
	}
	
	function get_user_bans($user_id)
	{
		$ban_details=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT id,bannedby,bannedon,internal,external,read,expires FROM bans WHERE user = ?");
		$query->bindValue(1,$user_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$ban=array();
			foreach($result as $key=>$value)
			{
				$ban[$key]=$value;
			}
			$ban_details[$result["ID"]]=$ban;
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($ban_details);
	}
	
	function get_active_ban_for_user($user_id)
	{
		$ban_details=array("BannedOn"=>-1,"Reason"=>"","Expires"=>-1);
		$current_expiry=-1;
		$active_ban=false;
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT bannedon,external,expires FROM bans WHERE user = ?");
		$query->bindValue(1,$user_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			if($result["Expires"] > time() || $result["Expires"] == 0)
			{
				$active_ban=true;
				if(($result["Expires"] >= $current_expiry && $current_expiry != 0) || $result["Expires"] == 0)
				{
					$current_expiry=$result["Expires"];
					$ban_details["BannedOn"]=$result["BannedOn"];
					$ban_details["Reason"]=$result["External"];
					$ban_details["Expires"]=$result["Expires"];
				}
			}
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		if($active_ban === true)
		{
			return $ban_details;
		}
		else
		{
			return array();
		}
	}
	
	function get_max_day($month,$year)
	{
		if($month == "February")
		{
			$time=strtotime($month . " 1, " . $year);
			if(date("L",$time) == 1)
			{
				return 29;
			}
			else
			{
				return 28;
			}
		}
		elseif(in_array($month,array("September","April","June","November")))
		{
			return 30;
		}
		else
		{
			return 31;
		}
	}
	
	function get_user_warnings($user_id,$active_only)
	{
		$warnings=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT id,warnedby,warnedon,internal,external FROM warnings WHERE user = ?");
		$query->bindValue(1,$user_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$warning=array();
			foreach($result as $key=>$value)
			{
				$warning[$key]=$value;
			}
			if($active_only === false || ($result["WarnedOn"]+90*24*60*60) > time())
			{
				$warnings[$result["ID"]]=$warning;
			}
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($warnings);
	}
	
	function display_last_post($topic,$global_topic=false)
	{
		$user_level=get_user_level($_SESSION['user_id']);
		$posts=array();
		$post_id=-1;
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		if($global_topic === true)
		{
			$query=$db->prepare("SELECT posts FROM globaltopics WHERE id = ?");
		}
		else
		{
			$query=$db->prepare("SELECT posts FROM topics WHERE id = ?");
		}
		$query->bindValue(1,$topic,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			$posts=array_reverse(unserialize($result["Posts"]),true);
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		if(count($posts) > 0)
		{
			foreach($posts as $id=>$content)
			{
				if(($content["Approved"] == 1 && $content["Deleted"] == 0) || $user_level >= 8)
				{
					$post_id=$id;
					break;
				}
			}
		}
		
		if($post_id > -1)
		{
			$post=$posts[$post_id];
			echo("<b>" . $post["Title"] . "</b><br>Posted by " . display_username($post["User"]) . " on " . print_date($post["Date"]));
		}
		else
		{
			echo("<i>No visible posts in this topic</i>");
		}
	}
	
	function mark_posts_as_read($topic,$posts)
	{
		$topic_info=get_topic($topic);
		$good=false;
		
		foreach($posts as $post_id)
		{
			if(!in_array($_SESSION['user_id'],$topic_info["Posts"][$post_id]["ReadBy"]))
			{
				$topic_info["Posts"][$post_id]["ReadBy"][]=$_SESSION['user_id'];
			}
		}
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			
		$query=$db->prepare("UPDATE topics SET posts = ? WHERE id = ?");
		$query->bindValue(1,serialize($topic_info["Posts"]),SQLITE3_TEXT);
		$query->bindValue(2,$topic,SQLITE3_INTEGER);
		$result=$query->execute();
		if($result !== false)
		{
			$good=true;
		}
		$query->close();
		
		$db->close();
		
		return $good;
	}
	
	function mark_global_posts_as_read($topic,$posts)
	{
		$topic_info=get_topic($topic,true);
		$good=false;
		
		foreach($posts as $post_id)
		{
			if(!in_array($_SESSION['user_id'],$topic_info["Posts"][$post_id]["ReadBy"]))
			{
				$topic_info["Posts"][$post_id]["ReadBy"][]=$_SESSION['user_id'];
			}
		}
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			
		$query=$db->prepare("UPDATE globaltopics SET posts = ? WHERE id = ?");
		$query->bindValue(1,serialize($topic_info["Posts"]),SQLITE3_TEXT);
		$query->bindValue(2,$topic,SQLITE3_INTEGER);
		$result=$query->execute();
		if($result !== false)
		{
			$good=true;
		}
		$query->close();
		
		$db->close();
		
		return $good;
	}
	
	function get_private_messages($user_id)
	{
		$pms=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT threadid,user1,user2,created,lastpost,posts,deleted,title FROM privatemessages WHERE user1 = ? OR user2 = ?");
		$query->bindValue(1,$user_id,SQLITE3_INTEGER);
		$query->bindValue(2,$user_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			if($result["User1"] != $_SESSION['user_id'] && $result["Deleted"] == 1)
			{
				continue;
			}
			$pm=array();
			foreach($result as $key=>$value)
			{
				$pm[$key]=$value;
			}
			$pm["Posts"]=unserialize($result["Posts"]);
			$pm["New"]=0;
			$pm["PostCount"]=count($pm["Posts"]);
			foreach($pm["Posts"] as $post)
			{
				if($post["User"] != $_SESSION['user_id'] && $post["ReadBy"] === false && $post["Deleted"] == 0)
				{
					$pm["New"]=1;
					break;
				}
			}
			$pms[$result["ThreadID"]]=$pm;
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($pms);
	}
	
	function get_private_message($thread_id)
	{
		$pm=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		$query=$db->prepare("SELECT user1,user2,created,lastpost,posts,deleted,title FROM privatemessages WHERE threadid = ?");
		$query->bindValue(1,$thread_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			if($result["User1"] != $_SESSION['user_id'] && $result["Deleted"] == 1)
			{
				continue;
			}
			foreach($result as $key=>$value)
			{
				$pm[$key]=$value;
			}
			$pm["Posts"]=unserialize($result["Posts"]);
			$pm["New"]=0;
			$pm["PostCount"]=count($pm["Posts"]);
			foreach($pm["Posts"] as $post)
			{
				if($post["User"] != $_SESSION['user_id'] && $post["ReadBy"] === false && $post["Deleted"] == 0)
				{
					$pm["New"]=1;
					break;
				}
			}
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($pm);
	}
	
	function mark_messages_as_read($thread,$posts)
	{
		$thread_info=get_private_message($thread);
		$good=false;
		
		foreach($posts as $post_id)
		{
			if($thread_info["Posts"][$post_id]["User"] != $_SESSION['user_id'] && $thread_info["Posts"][$post_id]["ReadBy"] === false)
			{
				$thread_info["Posts"][$post_id]["ReadBy"]=true;
			}
		}
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READWRITE);
			
		$query=$db->prepare("UPDATE privatemessages SET posts = ? WHERE threadid = ?");
		$query->bindValue(1,serialize($thread_info["Posts"]),SQLITE3_TEXT);
		$query->bindValue(2,$thread,SQLITE3_INTEGER);
		$result=$query->execute();
		if($result !== false)
		{
			$good=true;
		}
		$query->close();
		
		$db->close();
		
		return $good;
	}
	
	function check_new_pm($user_id)
	{
		foreach(get_private_messages($user_id) as $pm)
		{
			if($pm["New"] == 1)
			{
				return true;
			}
		}
		
		return false;
	}
	
	function get_polls($topic,$formal)
	{
		$polls=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		if($formal === true)
		{
			$query=$db->prepare("SELECT id,createdby,opened,closed,options,deleted,voters,votes FROM formalpolls WHERE topic = ?");
		}
		else
		{
			$query=$db->prepare("SELECT id,createdby,opened,closed,options,deleted FROM informalpolls WHERE topic = ?");
		}
		$query->bindValue(1,$topic,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			if(get_user_level($_SESSION['user_id']) < 8 && $result["Deleted"] == 1)
			{
				continue;
			}
			$poll=array();
			foreach($result as $key=>$value)
			{
				$poll[$key]=$value;
			}
			$poll["Options"]=unserialize($result["Options"]);
			if($formal === true)
			{
				$poll["Voters"]=unserialize($result["Voters"]);
				$poll["Votes"]=unserialize($result["Votes"]);
			}
			$polls[$result["ID"]]=$poll;
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($polls);
	}
	
	function get_poll($poll_id,$formal)
	{
		$poll=array();
		
		$db=new SQLite3("/var/www/lef-db/database.sqlite",SQLITE3_OPEN_READONLY);
		
		if($formal === true)
		{
			$query=$db->prepare("SELECT topic,createdby,opened,closed,options,deleted,voters,votes FROM formalpolls WHERE id = ?");
		}
		else
		{
			$query=$db->prepare("SELECT topic,createdby,opened,closed,options,deleted FROM informalpolls WHERE id = ?");
		}
		$query->bindValue(1,$poll_id,SQLITE3_INTEGER);
		$results=$query->execute();
		while($result=$results->fetchArray(SQLITE3_ASSOC))
		{
			if(get_user_level($_SESSION['user_id']) < 8 && $result["Deleted"] == 1)
			{
				continue;
			}
			foreach($result as $key=>$value)
			{
				$poll[$key]=$value;
			}
			$poll["Options"]=unserialize($result["Options"]);
			if($formal === true)
			{
				$poll["Voters"]=unserialize($result["Voters"]);
				$poll["Votes"]=unserialize($result["Votes"]);
			}
		}
		$results->finalize();
		$query->close();
		
		$db->close();
		
		return($poll);
	}
?>