<?php
/* A new version of chat
	Copyright (c) 2008 Alan Chandler
	Licenced under the GPL
*/
// Link to SMF forum as this is only for logged in members
// Show all errors:
error_reporting(E_ALL);
// Path to the chat directory:

define('MBCHAT_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/');

require_once(MBCHAT_PATH.'../forum/SSI.php');
//If not logged in to the forum, not allowed any further so redirect to page to say so
if($user_info['is_guest']) {
	header( 'Location: http://mb.home/static/Chat.htm' ) ;
	exit;
};

// SMF membergroup IDs for the groups that we have used to define characteristics which control Chat Group
define('SMF_CHAT_BABY',		10);
define('SMF_CHAT_LEAD',		9);
define('SMF_CHAT_MODERATOR',	14);
define('SMF_CHAT_MELINDA',	13);
define('SMF_CHAT_HONORARY',	20);
define('SMF_CHAT_SPECIAL',	19);

define('MBCHAT_ENTRANCE_HALL', 'Entrance Hall');
// These need to match the roomID in the database
define('MBCHAT_MEMBERS_LOUNGE', 1);
define('MBCHAT_BLUE_ROOM',	2);
define('MBCHAT_GREEN_ROOM',	3);
define('MBCHAT_VAMP_CLUB',	4);
define('MBCHAT_AUDITORIUM',	5);

define('MBCHAT_POLL_INTERVAL',	2000);  //Poll interval in milliseconds
define('MBCHAT_POLL_PRESENCE',	10);	//Rate of presence polls (mark online)

define('MBCHAT_EMOTICON_PATH', '/static/images/emoticons/');

define('MBCHAT_PURGE_MESSAGE_INTERVAL',7); //No of days messages kept for
define('MBCHAT_CHATBOT_NAME','Hephaestus');
define('MBCHAT_MAX_MESSAGES',	100);		//Max message to display in chat list


define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');
$groups =& $user_info['groups'];
$uid = $ID_MEMBER;
$name =& $user_info['name'];
$role = (in_array(SMF_CHAT_LEAD, $groups))? (($user_info['is_admin'])? 'A' : 'L') :   // which role 
			((in_array(SMF_CHAT_BABY, $groups))? 'B' :(
			(in_array(SMF_CHAT_MELINDA, $groups))?'H' :(
			(in_array(SMF_CHAT_HONORARY, $groups))? 'G' :'R'))) ;
$mod = (in_array(SMF_CHAT_MODERATOR,$groups)?'M':(in_array(SMF_CHAT_SPECIAL,$groups)?'S':'N'));

dbQuery('START TRANSACTION;');

dbQuery('REPLACE INTO users (uid,name,role,moderator) VALUES ('.dbMakeSafe($uid).','.
				dbMakeSafe($name).','.dbMakeSafe($role).','.dbMakeSafe($mod).') ; ') ;
dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
				dbMakeSafe($uid).','.dbMakeSafe($name)
				.','.dbMakeSafe($role).', "LI" , 0 );');
dbQuery('COMMIT;');
$lid = mysql_insert_id();  // get the ID of this transaction for whisper management		

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Melinda's Backups Chat</title>
	<link rel="stylesheet" type="text/css" href="chat.css" title="mbstyle"/>
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="chat-ie.css"/>
	<![endif]-->
	<script src="/static/scripts/mootools.js" type="text/javascript" charset="UTF-8"></script>
	<script src="/static/scripts/soundmanager2.js" type="text/javascript" charset="UTF-8"></script>
	<script src="mbchat.js" type="text/javascript" charset="UTF-8"></script>
</head>
<body>
<script type="text/javascript">
	<!--
var chat;

window.addEvent('domready', function() {
	MBchat.init({uid: <?php echo $uid;?>, 
				name: '<?php echo $name ; ?>',
				 role: '<?php echo $role; ?>',
				password : '<?php echo sha1("Key".$uid); ?>',
				mod: '<?php echo $mod ; ?>'  }, 
				{poll: <?php echo MBCHAT_POLL_INTERVAL ; ?>,
				presence:<?php echo MBCHAT_POLL_PRESENCE ; ?>,
				lastid: <?php echo $lid ; ?>},
				'<?php echo MBCHAT_CHATBOT_NAME ; ?>',
				'<?php echo MBCHAT_ENTRANCE_HALL ?>',
				<?php echo MBCHAT_MAX_MESSAGES ?>);
});	
window.addEvent('unload', function() {
	MBchat.logout();
	
});


	// -->
</script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" >
<tbody>
	<tr>
	<td align="left" width="30" class="topbg_l" height="70">&nbsp;</td>
	<td align="left" colspan="2" class="topbg_r" valign="top"><a href="/" alt="Main Site Home Page">
		<img  style="margin-top: 24px;" src="/static/images/mb-logo-community.gif" alt="Melinda's Backups Community" border="0" /></a>	
		</td>
	<td align="right" width="400" class="topbg" valign="top">
	<span style="font-family: tahoma, sans-serif; margin-left: 5px;">Melinda's Backups Community</span>
	</td>
		<td align="right" width="25" class="topbg_r2" valign="top">
		<div id="roomNameContainer">
			<h1><?php echo MBCHAT_ENTRANCE_HALL ?></h1>
		</div>
		<!-- blank -->
		</td>
	</tr>  </tbody>
</table>

<div id="content">

<div id="exit" class="exit-f"></div>

<div id="entranceHall">
	<div  class="rooms">
	<h3>Main Rooms</h3>
		<ul>
			<li><a id="R<?php echo MBCHAT_MEMBERS_LOUNGE; ?>" class="room" href="#">Members Lounge</a></li>
<?php if($role != 'B') { ?>
			<li><a id="R<?php echo MBCHAT_BLUE_ROOM; ?>" class="room" href="#">Blue Room</a></li>
<?php } else { ?>
			<li><a id="R<?php echo MBCHAT_GREEN_ROOM; ?>" class="room" href="#">Green Room</a></li>
<?php }; ?>
			<li><a id="R<?php echo MBCHAT_VAMP_CLUB; ?>" class="room" href="#">Vamp Club</a></li>
			<li><a id="R<?php echo MBCHAT_AUDITORIUM; ?>" class="room" href="#">Auditorium</a></li>
		</ul>
		<div style="clear:both"></div>
	</div>
	<?php 
	
	$sql='SELECT * FROM rooms WHERE type = "C";' ;
	$result=dbQuery($sql);
	if (mysql_num_rows($result) != 0) {		
		$i = 0;
		while ($row = mysql_fetch_assoc($result)) {
			if(in_array($row['smf_group'],$groups)) {
				if( ($i % 4) == 0 ) {
		?><div class="rooms"> 
	<h3>Committee Rooms</h3>
		<ul>
		<?php		};
				$i++; ?>
	<li><a id="R<?php echo $row['rid'];?>" class="room committee" href="#"><?php echo $row['name']; ?></a></li>
		<?php		if( ($i % 4) == 0 ) {
				?></ul>
		<div style="clear:both"></div>
	</div>
<?php 
				};
			};
		};
		 //If ended loop and hadn't just comleted div we will have to do it here
 		if( ($i % 4) != 0 ) { 
			?>
</ul>
		<div style="clear:both"></div>
	</div>
<?php		}; 
	};
	mysql_free_result($result);
?>
</div>
<div id="onlineListContainer">
	<h4>Users Online</h4>
	<div id="onlineList" class="loading"></div>
</div>
<div id="chatList" class="whisper"></div>	

<div id="inputContainer">
	<form id="messageForm" action="message.php?user=<?php echo $uid;?>&password=<?php echo sha1("Key".$uid); ?>"
	 method="post" enctype="application/x-www-form-urlencoded" autocomplete="off" >
		<input id="messageRoom" type="hidden" value="0" name="room" />
		<input id="messageText" type="text" name="text" />
		<input type="submit" name="submit" value="Send"/>
	</form>
</div>

<div id="whisperBoxTemplate" class="whisperBox">
	<div class="whisperList"></div>
	<form action="whisper.php?user=<?php echo $uid;?>&password=<?php echo sha1("Key".$uid); ?>"
	 method="post" enctype="application/x-www-form-urlencoded" autocomplete="off" >
		<input type="hidden" name="wid" value="0" />
		<input type="text" name="text" />
		<input type="submit" name="submit" value="Send"/>
	</form>
</div>

<div id="emoticonContainer">
<?php
	$result=dbQuery('SELECT * FROM emoticons;');
	while ($row = mysql_fetch_assoc($result)) {
echo '<img class="emoticon" src="'.MBCHAT_EMOTICON_PATH.$row['filename'].'" alt=":'.$row['key'].'" title="'.$row['key'].'" />
' ;
	}
	mysql_free_result($result);
?>
</div>


<div id="userOptions">
	<input id="autoScroll" type="checkbox" checked="checked" />
	<label for="autoScroll">Autoscroll</label>
	<input id="musicEnabled" type="checkbox" />
	<label for="musicEnabled">Enable Music</label><br/>
	<input id="soundEnabled" type="checkbox" checked="checked"/>
	<label for="soundEnabled">Enable Sound</label><br/>
	<input id="soundDelay" type="text" size="1" value="5" />
	<label for="soundDelay">Minutes 'till sound</label>
</div>

<div id="copyright">MBChat &copy; 2008 Alan Chandler.  Licenced under the GPL</div>

</body>

</html><?php 
	//purge messages that are too old from database
	dbQuery('DELETE FROM log WHERE NOW() > DATE_ADD( time, INTERVAL '.MBCHAT_PURGE_MESSAGE_INTERVAL.' DAY);');
//Slightly longer for whispers to ensure log doesn't reference them
	dbQuery('DELETE FROM whisper WHERE NOW() > DATE_ADD(DATE_ADD( time, INTERVAL '.MBCHAT_PURGE_MESSAGE_INTERVAL.' DAY),INTERVAL 5 MINUTE) ;');
//timeout any users that are too old

	include('timeout.php');	
?>