<?php
/* A new version of chat
	Copyright (c) 2008 Alan Chandler
	Licenced under the GPL
*/
// Link to SMF forum as this is only for logged in members
// Show all errors:
error_reporting(E_ALL);
// Path to the chat directory:

if(!(isset($_GET['uid']) && isset($_GET['pass'])  && isset($_GET['name'])  && isset($_GET['mod']) && isset($_GET['role']) && isset($_GET['whi'])))
 die('Hacking attempt - wrong parameters');
$uid = $_GET['uid'];
$password = $_GET['pass'];
if ($password != sha1("Key".$uid))
   die('Hacking attempt got: '.$password.' expected: '.sha1("Key".$uid));

$name=$_GET['name'];
$role=$_GET['role'];
$mod=$_GET['mod'];
$whi=$_GET['whi'];

define('MBCHAT_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/');
define('MBCHAT_PIPE_PATH',	MBCHAT_PATH.'pipes/');

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
    $sendpipe=fopen(MBCHAT_PATH."pipes/msg".$uid,'r+');
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
	<title>Melinda's Backups Chat (Lite)</title>
	<link rel="stylesheet" type="text/css" href="chat.css" title="mbstyle"/>
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="chat-ie.css"/>
	<![endif]-->
	<script src="/js/soundmanager2-nodebug-jsmin.js" type="text/javascript" charset="UTF-8"></script>
	<script src="/js/mootools-1.2.3-core-yc.js" type="text/javascript" charset="UTF-8"></script>
	<script src="mootools-1.2.3.1-more.js" type="text/javascript" charset="UTF-8"></script>
	<script src="mbclite.js" type="text/javascript" charset="UTF-8"></script>
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

<div id="roomNameContainer">
	<h1><?php echo MBCHAT_ENTRANCE_HALL ?></h1>
</div>
<div id="content">

<input id=exit type="button" value="exit" onclick="MBchat.exit()" />

<div id="entranceHall">
	<div  id="mainRooms" class="rooms">
	<h3>Main Rooms</h3>
		<input id="R<?php echo MBCHAT_MEMBERS_LOUNGE; ?>" type="button" onclick="MBchat.goToRoom(<?php echo MBCHAT_MEMBERS_LOUNGE; ?>)" value="Members Lounge" /><br/>
<?php if($role != 'B') { ?>
		<input id="R<?php echo MBCHAT_BLUE_ROOM; ?>" type="button" onclick="MBchat.goToRoom(<?php echo MBCHAT_BLUE_ROOM; ?>)" value="Blue Room" /><br/>
<?php } else { ?>
		<input id="R<?php echo MBCHAT_GREEN_ROOM; ?>" type="button" onclick="MBchat.goToRoom(<?php echo MBCHAT_GREEN_ROOM; ?>)" value="Green Room" /><br/>
<?php }; ?>
		<input id="R<?php echo MBCHAT_VAMP_CLUB; ?>" type="button" onclick="MBchat.goToRoom(<?php echo MBCHAT_VAMP_CLUB; ?>)" value="Vamp Club" /><br/>
		<input id="R<?php echo MBCHAT_AUDITORIUM; ?>" type="button" onclick="MBchat.goToRoom(<?php echo MBCHAT_AUDITORIUM; ?>)" value="Auditorium" /><br/>
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
	<input id="R<?php echo $row['rid'];?>" type="button" onclick="MBchat.goToRoom(<?php echo $row['rid'];?>)" value="<?php echo $row['name']; ?>" /><br/>
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


<div id="copyright">MBchat light <span id ="version" ><?php include('version.php') ?></span> &copy; 2008 Alan Chandler.  Licenced under the GPL</div>
</div>
</body>

</html><?php 
	//purge messages that are too old from database
	dbQuery('DELETE FROM log WHERE NOW() > DATE_ADD( time, INTERVAL '.MBCHAT_PURGE_MESSAGE_INTERVAL.' DAY);');
//timeout any users that are too old

	include('timeout.php');

?>
