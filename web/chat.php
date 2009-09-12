<?php
/*
 	Copyright (c) 2009 Alan Chandler
    This file is part of MBChat.

    MBChat is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    MBChat is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with MBChat (file COPYING.txt).  If not, see <http://www.gnu.org/licenses/>.
*/
// Link to SMF forum as this is only for logged in members
// Show all errors:
error_reporting(E_ALL);
// Path to the chat directory:

if(!(isset($_GET['uid']) && isset($_GET['pass'])  && isset($_GET['name'])  && isset($_GET['mod']) && isset($_GET['role']) && isset($_GET['whi']) && isset($_GET['gp'])))
 die('Hacking attempt - wrong parameters');
$uid = $_GET['uid'];
$password = $_GET['pass'];
if ($password != sha1("Key".$uid))
   die('Hacking attempt got: '.$password.' expected: '.sha1("Key".$uid));

$name=$_GET['name'];
$role=$_GET['role'];
$mod=$_GET['mod'];
$whi=$_GET['whi'];
$groups = explode(":",$_GET['gp']);


define('MBCHAT_ENTRANCE_HALL', 'Entrance Hall');
// These need to match the roomID in the database
define('MBCHAT_MEMBERS_LOUNGE', 1);
define('MBCHAT_BLUE_ROOM',	2);
define('MBCHAT_GREEN_ROOM',	3);
define('MBCHAT_VAMP_CLUB',	4);
define('MBCHAT_AUDITORIUM',	5);

define('MBCHAT_POLL_INTERVAL',	60000);  //Poll interval in milliseconds
define('MBCHAT_POLL_PRESENCE',	10);	//Rate of presence polls (mark online)

define('MBCHAT_EMOTICON_PATH', '/static/images/emoticons/');

define('MBCHAT_PURGE_MESSAGE_INTERVAL',20); //No of days messages kept for
define('MBCHAT_CHATBOT_NAME','Hephaestus');
define('MBCHAT_MAX_MESSAGES',	100);		//Max message to display in chat list

define('MBCHAT_FETCHLOG_DELAY',	3000);		//Milliseconds of no activity on time section before fetching log
define('MBCHAT_LOG_SPIN_RATE',	500);		//Milliseconds between each step in the timer for log time 
define('MBCHAT_LOG_SECOND_STEPS', 2);		//No of spin steps where clock varies by a second
define('MBCHAT_LOG_MINUTE_STEPS', 4);		//No of spin steps where clock varies by a minute (before going to hour)
define('MBCHAT_LOG_HOUR_STEPS',	12);		//No of spin steps where clock varies by an hour (before going to 6 hour steps)
define('MBCHAT_LOG_6HOUR_STEPS', 6);		//No of spin steps where clock varies by 6 hours (before going to day steps)

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

//Make a pipe for this user - but before doing so kill anyother using this userID.  We can only have one chat at once.
$old_umask = umask(0007);
if(file_exists(MBCHAT_PIPE_PATH."msg".$uid)) {
// we have to kill other chat, in case it was stuck
    $sendpipe=fopen(MBCHAT_PIPE_PATH."msg".$uid,'r+');
    fwrite($sendpipe,'<LX>');
    fclose($sendpipe);
// Now sleep long enough for the other instance to go away
    sleep(2);
}
posix_mkfifo(MBCHAT_PIPE_PATH."msg".$uid,0660);
umask($old_umask);

dbQuery('REPLACE INTO users (uid,name,role,moderator) VALUES ('.dbMakeSafe($uid).','.
				dbMakeSafe($name).','.dbMakeSafe($role).','.dbMakeSafe($mod).') ; ') ;
				
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Melinda's Backups Chat</title>
	<link rel="stylesheet" type="text/css" href="chat.css" title="mbstyle"/>
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="chat-ie.css"/>
	<![endif]-->
	<script src="/js/soundmanager2-nodebug-jsmin.js" type="text/javascript" charset="UTF-8"></script>
	<script src="/js/mootools-1.2.3-core-yc.js" type="text/javascript" charset="UTF-8"></script>
	<script src="mootools-1.2.3.1-more.js" type="text/javascript" charset="UTF-8"></script>
	<script src="mbchat.js" type="text/javascript" charset="UTF-8"></script>
</head>
<body>
<script type="text/javascript">
	<!--

window.addEvent('domready', function() {
	MBchat.init({uid: <?php echo $uid;?>, 
				name: '<?php echo $name ; ?>',
				 role: '<?php echo $role; ?>',
				password : '<?php echo sha1("Key".$uid); ?>',
				mod: <?php echo '"'.$mod.'"' ; ?> ,
				whisperer: <?php echo $whi ; ?>  }, 
				{poll: <?php echo MBCHAT_POLL_INTERVAL ; ?>,
				presence:<?php echo MBCHAT_POLL_PRESENCE ; ?>},
				{fetchdelay: <?php echo MBCHAT_FETCHLOG_DELAY ; ?>,
				spinrate: <?php echo MBCHAT_LOG_SPIN_RATE ; ?>,
				secondstep:<?php echo MBCHAT_LOG_SECOND_STEPS ; ?>,
				minutestep:<?php echo MBCHAT_LOG_MINUTE_STEPS ; ?>,
				hourstep:<?php echo MBCHAT_LOG_HOUR_STEPS ; ?>,
				sixhourstep:<?php echo MBCHAT_LOG_6HOUR_STEPS ; ?> },
				'<?php echo MBCHAT_CHATBOT_NAME ; ?>',
				'<?php echo MBCHAT_ENTRANCE_HALL ?>',
				<?php echo MBCHAT_MAX_MESSAGES ?>);
});	
window.addEvent('beforeunload', function() {
	MBchat.logout();
	
});

var soundReady = false;
soundManager.url = '/js/';
soundManager.onload = function() {
	soundManager.createSound({
		id : 'whispers',
		url : 'ding.mp3',
		autoLoad : true ,
		autoPlay : false 
	});
	soundManager.createSound({
		id : 'move',
		url : 'exit.mp3',
		autoLoad : true ,
		autoPlay : false 
	});
	soundManager.createSound({
		id : 'speak',
		url : 'poptop.mp3',
		autoLoad : true ,
		autoPlay : false 
	});
	soundManager.createSound({
		id : 'creaky',
		url : 'creaky.mp3',
		autoLoad : true ,
		autoPlay : false
	});
	soundManager.createSound({
		id : 'music',
		url : 'iyl.mp3',
		autoLoad : true ,
		autoPlay : false
	});

	soundReady=true;
};



	// -->
</script>

<table id="header" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" >
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
<div id="logControls" class="hide">
	<div id="startTimeBlock">
		<div id="startTextLog">Log Start Time</div>
		<div id="minusStartLog"></div><div id="timeShowStartLog"></div><div id="plusStartLog"></div>
	</div>
	<div id="endTimeBlock">
		<div id="endTextLog">Log End Time</div>
		<div id="minusEndLog"></div><div id="timeShowEndLog"></div><div id="plusEndLog"></div>
 	</div>
	<div id="printLog"></div>
</div>

<div id="entranceHall">
	<div  id="mainRooms" class="rooms">
	<h3>Main Rooms</h3>
		<div id="R<?php echo MBCHAT_MEMBERS_LOUNGE; ?>" class="room">Members Lounge</div>
<?php if($role != 'B') { ?>
		<div id="R<?php echo MBCHAT_BLUE_ROOM; ?>" class="room">Blue Room</div>
<?php } else { ?>
		<div id="R<?php echo MBCHAT_GREEN_ROOM; ?>" class="room">Green Room</div>
<?php }; ?>
		<div id="R<?php echo MBCHAT_VAMP_CLUB; ?>" class="room">Vamp Club</div>
		<div id="R<?php echo MBCHAT_AUDITORIUM; ?>" class="room">Auditorium</div>
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
		<?php		};
				$i++; ?>
	<div id="R<?php echo $row['rid'];?>" class="room committee"><?php echo $row['name']; ?></div>
		<?php		if( ($i % 4) == 0 ) {
				?>
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
	<form id="messageForm" action="/"
	 enctype="application/x-www-form-urlencoded" autocomplete="off" >
		<input id="messageText" type="text" name="text" />
		<input type="submit" name="submit" value="Send"/>
	</form>
</div>

<div id="whisperBoxTemplate">
	<div class="private"></div><div class="dragHandle">Whisper Box</div><div class="closeBox"></div>
	<div class="whisperList"></div>
	<form action="/"
	 	enctype="application/x-www-form-urlencoded" autocomplete="off" >
		<input type="text" name="text" class="whisperInput" />
		<input type="submit" name="submit" value="Send" class="whisperSend"/>
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
<form>
	<input id="autoScroll" type="checkbox" checked="checked" />
	<label for="autoScroll">Autoscroll</label>
	<span id="soundOptions">
		<input id="musicEnabled" type="checkbox" />
		<label for="musicEnabled">Enable Music</label><br/>
		<input id="soundEnabled" type="checkbox" checked="checked"/>
		<label for="soundEnabled">Enable Sound</label><br/>
		<input id="soundDelay" type="text" size="1" value="5" />
		<label for="soundDelay">Minutes 'till sound</label>
	</span>
</form>
</div>

<div id="copyright">MBchat <span id="version"><?php include('version.php');?></span> &copy; 2008 Alan Chandler.  Licenced under the GPL</div>
</div>
</body>

</html><?php 
	//purge messages that are too old from database
	dbQuery('DELETE FROM log WHERE NOW() > DATE_ADD( time, INTERVAL '.MBCHAT_PURGE_MESSAGE_INTERVAL.' DAY);');
//timeout any users that are too old

	include('timeout.php');
?>
