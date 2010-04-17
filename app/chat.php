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

/* check we are called with all the right parameters.  If not, we need to call our initialisation routine */

if(!(isset($_POST['uid']) && isset($_POST['pass'])  && isset($_POST['name'])  && isset($_POST['mod']) && isset($_POST['role']) && isset($_POST['whi']) && isset($_POST['gp']) && isset($_POST['ctype']))) {
 header('Location: index.php');
 exit;
}
$uid = $_POST['uid'];
$password = $_POST['pass'];
if ($password != sha1("Key".$uid))
   die('Hacking attempt got: '.$password.' expected: '.sha1("Key".$uid));


error_reporting(E_ALL);
//Can't start if we haven't setup a database
if (!file_exists('./data/chat.db')) {
    $db = new SQLite3('./data/chat.db');  //This will create it
    if(!$db->exec(file_get_contents('./database.sql'))) die("Database Setup Failed: ".$db->lastErrorMsg());
    unset($db); //I don't want problems since db.php uses it too.
    file_put_contents('./data/time.txt', ''.time()); //make a time file
}

//Make a pipe for this user - but before doing so kill any other using this userID.  We can only have one chat at once.
$old_umask = umask(0007);
if(file_exists("./data/msg".$uid)) {
// we have to kill other chat, in case it was stuck
    $sendpipe=fopen("./data/msg".$uid,'r+');
    fwrite($sendpipe,'<LX>');
    fclose($sendpipe);
// Now sleep long enough for the other instance to go away
    sleep(2);
}
posix_mkfifo("./data/msg".$uid,0660);
umask($old_umask);


define ('MBC',1);   //defined so we can control access to some of the files.
require_once('./timeout.php');

class Chat extends Timeout {

    function __construct() {
        parent::__construct(Array(
            'user' => "REPLACE INTO users (uid,name,role,moderator, present) VALUES (:uid, :name , :role, :mod, 1);",
            'rooms' => "SELECT * FROM rooms ORDER BY rid ASC;",
            'purge' => "DELETE FROM log WHERE time < :interval ;"));
    }

    function doWork() {

    //Just adds user to database
        $this->bindInt('user','uid',$_POST['uid']);
        $this->bindText('user','name',$_POST['name']);
        $this->bindChars('user','role',$_POST['role']);
        $this->bindChars('user','mod',$_POST['mod']);
        $this->post('user');

    //Purge old messages
        $this->bindInt('purge','interval',time() - $this->getParam('purge_message_interval')*86400);
        $this->post('purge');
        
    //Add timeout any other users who should not have been there
        $this->doTimeout();
    }
}

$c = new Chat();
$c->transact();


if(!isset($_POST['test'])) {

function head_content() {

?><title>Melinda's Backups Chat</title>
	<link rel="stylesheet" type="text/css" href="chat.css" title="mbstyle"/>
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="chat-ie.css"/>
	<![endif]-->
	<script src="/js/soundmanager2-nodebug-jsmin.js" type="text/javascript" charset="UTF-8"></script>
	<script src="/js/mootools-1.2.4-core-yc.js" type="text/javascript" charset="UTF-8"></script>
	<script src="mootools-1.2.4.4-more-chat-yc.js" type="text/javascript" charset="UTF-8"></script>
	<script src="<?php echo ($_POST['ctype'] == 'lite')?'mbclite.js':'mbchat.js' ; ?>" type="text/javascript" charset="UTF-8"></script>
<?php

}


function menu_items() {
//Noop
}

function content() {
    global $c;

		$uid = $_POST['uid'];		
        $name=$_POST['name'];
        $role=$_POST['role'];
        $mod=$_POST['mod'];
        $whi=$_POST['whi'];
        $groups = explode(":",$_POST['gp']); //A ":" separated list of committees (groups) that the user belongs to.
        $lite = ($_POST['ctype'] == 'lite'); //if we need the special lite version (provides accessibility for blind people)


?><script type="text/javascript">
	<!--

window.addEvent('domready', function() {
	MBchat.init({uid: <?php echo $uid;?>, 
				name: '<?php echo $name ; ?>',
				 role: '<?php echo $role; ?>',
				password : '<?php echo sha1("Key".$uid); ?>',
				mod: <?php echo '"'.$mod.'"' ; ?> ,
				whisperer: <?php echo $whi ; ?>  }, 
				<?php echo $c->getParam('presence_interval') ; ?>,
				{fetchdelay: <?php echo $c->getParam('log_fetch_delay') ; ?>,
				spinrate: <?php echo $c->getParam('log_spin_rate') ; ?>,
				secondstep:<?php echo $c->getParam('log_step_seconds') ; ?>,
				minutestep:<?php echo $c->getParam('log_step_minutes') ; ?>,
				hourstep:<?php echo $c->getParam('log_step_hours') ; ?>,
				sixhourstep:<?php echo $c->getParam('log_step_6hours') ; ?> },
				"<?php echo $c->getParam('chatbot_name') ; ?>",
				"<?php echo $c->getParam('entrance_hall') ?>",
				<?php echo $c->getParam('max_messages') ?>);
});	
window.addEvent('beforeunload', function() {
	MBchat.logout();
	
});

var soundReady = false;

soundManager.url = '/js/';
soundManager.flashVersion = 9; // optional: shiny features (default = 8)

soundManager.onload = function() {
	soundManager.createSound({
		id : 'whispers',
		url : "<?php echo $c->getParam('sound_whisper') ; ?>",
		autoLoad : true ,
		autoPlay : false 
	});
	soundManager.createSound({
		id : 'move',
		url : "<?php echo $c->getParam('sound_move') ; ?>",
		autoLoad : true ,
		autoPlay : false 
	});
	soundManager.createSound({
		id : 'speak',
		url : "<?php echo $c->getParam('sound_speak') ; ?>",
		autoLoad : true ,
		autoPlay : false 
	});
	soundManager.createSound({
		id : 'creaky',
		url : "<?php echo $c->getParam('sound_creaky') ; ?>",
		autoLoad : true ,
		autoPlay : false
	});
	soundManager.createSound({
		id : 'music',
		url : "<?php echo $c->getParam('sound_music') ; ?>",
		autoLoad : true ,
		autoPlay : false
	});

	soundReady=true;
};



	// -->
</script>
<div id="roomNameContainer">
    <h1><?php echo $c->getParam('entrance_hall'); ?></h1>
</div>
<div id="content">
    <div id="exit" class="exit-f"></div>
<?php if(!$lite) {
?>    <div id="logControls" class="hide">
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
<?php }
?><div id="entranceHall">
	<div  id="mainRooms" class="rooms">
	    <h3>Main Rooms</h3>
<?php
$i=0;
$result = false;
do {
    try {
        $result = $c->query('rooms');
        break;
    } catch (DBCheck $e) {
        $c->checkBusy();
    }
} while(true);    
while ( $row = $c->fetch($result)) {
    $rid = $row['rid'];
    if(!(($role == 'B' && $rid == 2) || ($role != 'B' && $rid == 3) || ($row['type'] == 'C' && !in_array($row['smf_group'],$groups)))) {
        if($i > 0 && $i%4 == 0) {
?>  <div class="rooms"> 
    	<h3>Committee Rooms</h3>
<?php   }
        if($lite) {
?>    	<input id="R<?php echo $row['rid']; ?>" 
                type="button" onclick="MBchat.goToRoom(<?php echo $row['rid']; ?>)" 
                value="<?php echo $row['name']; ?>" /><br/>
<?php   }else {
?>		<div id="R<?php echo $row['rid']; ?>" class="room<?php if($row['type'] == 'C') echo ' committee'; ?>"><?php echo $row['name']; ?></div>
<?php   }
        $i++;
	    if( ($i % 4) == 0 ) {
?>  	<div style="clear:both"></div>
	</div>
<?php 
        }
    }    
}
$c->free($result); 
//If ended loop and hadn't just comleted div we will have to do it here
if( ($i % 4) != 0 ) { 
?>      <div style="clear:both"></div>
    </div>
<?php
} 
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
<?php
if (!$lite) {
?><div id="emoticonContainer">
<?php
	$dir = $c->getParam('emoticon_dir');
	$urlbase = $c->getParam('emoticon_url');
	$fns = scandir($dir);
	foreach ($fns as $filename) {

		if(filetype($dir.'/'.$filename) == 'file') {
		
		    $pos = strrpos($filename, '.');
		    if (!($pos === false)) { // dot is found in the filename
			    $basename = substr($filename, 0, $pos);
			    $extension = substr($filename, $pos+1);
			    if($extension == 'gif') {
				    echo '<img class="emoticon" src="'.$urlbase.$filename.'" alt=":'.$basename.'" title="'.$basename.'" />';
				    echo "\n";
			    }
			 }
		}
	}
?></div>

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
<?php
}
?><div id="copyright">MB Chat <span id="version"><?php include('./version.php');?></span> &copy; 2008-2010 Alan Chandler</div>
</div>
<?php

}
$template_url = $c->getParam('template_url');
include($c->getParam('template_dir').'/template.php');


}
unset($c);
?>
