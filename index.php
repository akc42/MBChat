<?php
/*
 	Copyright (c) 2009,2010 Alan Chandler
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

define('EXTERNAL_AUTHORISATION',false); //Do we use the remote modules
define('REMOTE_SERVER','http://mb.home/chat2/'); //If we use remotes, where are they;

require_once('./inc/client.inc');


if(EXTERNAL_AUTHORISATION) {
    if(!(isset($_POST['name']) && isset($_POST['role']) && isset($_POST['mod']) && isset($_POST['




$c = new ChatServer();

$c->start_server(SERVER_KEY); //Start Server if not already going.

$c->cmd('user',$uid,$_POST['name'],$_POST['role'],$_POST['mod']);



?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" type="text/css" href="/css/header.css" />
    <title>Hartley Chat</title>
	<link rel="stylesheet" type="text/css" href="/css/chat.css" />
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="/css/chat-ie.css"/>
	<![endif]-->
	<script src="/js/soundmanager2-nodebug-jsmin.js" type="text/javascript" charset="UTF-8"></script>
	<script src="/js/mootools-1.2.4-core-nc.js" type="text/javascript" charset="UTF-8"></script>
	<script src="mootools-1.2.4.4-more-chat-yc.js" type="text/javascript" charset="UTF-8"></script>
	<script src="<?php echo ($_POST['ctype'] == 'lite')?'mbclite.js':'mbchat.js' ; ?>" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/packages.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/binary.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/isarray.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/elapse.js" type="text/javascript" charset="UTF-8"></script>
	<script src="/js/cipher/BigInteger.init1.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/RSA.init1.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/SecureRandom.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/BigInteger.init2.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/RSA.init2.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/nonstructured.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/BigInteger.init3.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/RSA.init3.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript">
	    __uses( "BigInteger.init1.js" );
	    __uses( "BigInteger.init2.js" );
	    __uses( "RSA.init1.js" );
	    __uses( "RSA.init2.js" );
	    __uses( "RSA.init3.js" );
	    
        var BigInteger = __import( this,"titaniumcore.crypto.BigInteger" ); 
        var RSA = __import( this,"titaniumcore.crypto.RSA" );

        var rsa = new RSA();

        var keyPair = null;  //When complete this object will hold a Public/Private Key Pair.
        
        var genResult = function (key,rsa) {
            keyPair = key;
        };
        var progress = function(count){};
        var done = function(succeeded) {}
        /*
            We are kicking off a process to generate a rsa public/private key pair.  Typically this
            takes about 1.2 seconds or so to run to completion with this key length, so should be done
            before the user has completed his input - which is when we will need the result.  The genResult
            function will be called when complete (as is 'done' but we don't use it).  Instead we will check for 
            keyPair to become non null. 
        */
        var timerId = rsa.generateAsync(64,65537,progress,genResult,done);

        var login = function(){
            document.id('authblock').addClass('hide');
            document.id('alternate').removeClass('hide');
            var timerId;
            var checkKey = function () {
                if(keyPair) {
                    $clear(timerId);
                    proceed();
                }
            }; 
            if(keyPair) {
                //we have a key ready, so now we can use it
                proceed();
            } else {
                timerId = checkKey.periodical(50);
            }
            return false;
        };

        /* this proceed function is reached when both the user has entered a username (and possible password) AND
            the RSA generation has finished
         */
        var proceed = function() {
            var req = new Request.JSON({
                url:'/login/index.php',
                onSuccess: function(r,t) {
                    c = new BigInteger(r.c);
                    m = c.modPow(keyPair.d,keyPair.n);// This decrypts the key we need for the next stage.
  
var i=0;
            
                
                },
                onFailure: function(xhr) {
                    document.id('alternate').addClass('hide')
                    document.id('authblock').removeCLass('hide');
    
                }
            });
            var requestOptions = {};
            requestOptions.e = keyPair.e.toString(); //Add public key
            requestOptions.n = keyPair.n.toString(10);
            var user = document.id('login').username.value;
            var pass = document.id('login').password.value;
            if (pass == '') {
                requestOptions.guest = 'guest';
                pass = 'guest';
            };
            if (document.id('login').lite.checked) {
                requestOptions.lite = 'lite';
            }
            
            req.xhr.open("post",'/login/index.php',true,user,pass);
            req.send(requestOptions);
        }
   </script>

</head>
<body>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-6767755-1']);
  _gaq.push(['_trackPageview']);
</script>

<table id="header" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" >
<tbody>
	<tr>
	<td align="left" width="30" class="topbg_l" height="70">&nbsp;</td>
	<td align="left" colspan="2" class="topbg_r" valign="top"><a href="/" alt="Main Site Home Page">
		<img  style="margin-top: 24px;" src="/template/chandlerfamily_logo.png" alt="Chandler's Zen" border="0" /></a>	
	</td>
	<td align="center" width="300" class="topbg_r" valign="middle">
	MB Chat
	</td>	
	<td align="right" width="400" class="topbg" valign="top">
	<span style="font-family: tahoma, sans-serif; margin-left: 5px;">Chandler&#8217;s Zen Software</span>
	</td>
		<td align="right" width="25" class="topbg_r2" valign="top">
		<!-- blank -->
		</td>
	</tr>  </tbody>
</table>
<?php

		$uid = $_POST['uid'];		
        $name=$_POST['name'];
        $role=$_POST['role'];
        $mod=$_POST['mod'];
        $whi=$_POST['whi'];
        $groups = explode("_",$_POST['gp']); //A "_" separated list of committees (groups) that the user belongs to.
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
	MBchat.logout(); //Will send you back from whence you came (if you are not already on the way)
	
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
    <div id="loginblock">
    <p>Enter a username that you want to be known of in chat.  You only need to enter a  password <strong>if you are already
    registered as a user</strong> as this will be used to check your credentials in the database and will log you in as a normal
    user.</p>

    <p>If you are already logged in your connection will be <strong>refused</strong>.</p>

    <p>Guest users should just enter the name they wish to be known as in chat and leave the password field <strong>empty</strong>.  There will
    be no check for whether you are already connected.</p>

    <p>The accessibility version (see checkbox at bottom of form) is for users of the Jaws screen reading system for blindusers.  This 
    version removes some of the graphics in exchange for an interface designed specifically to enable Jaws to provide access. Only
    guests need select this if they wish to try this function.  Information about regular users is already stored.</p>

    <div id="authblock">
        <form id="login" action="#" method="post" onsubmit="javascript:return login()">
            <table>
                <tr><td>Username:</td><td><input type="text" name="username" value="" /></td></tr>
                <tr><td>Password:</td><td><input type="password" name="password" value="" /></td></tr>
                <tr>
                    <td><input type="submit" name="submit" value="Sign In"/></td>
                    <td>Use "Accessibilty" version: <input type="checkbox" name="lite" /></td>
                </tr>
            </table>
        <form>
    </div>
    <div id="alternate" class="hide"></div>


</div>
<div id="chatblock" class="hide">
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

   
foreach($c->query('rooms') as $row) {
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
</div>
<div id="copyright">MB Chat <span id="version"><?php include('./version.php');?></span> &copy; 2008-2010 Alan Chandler</div>
</div>
<!-- Google Analytics Tracking Code -->
  <script type="text/javascript">
    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(ga);
    })();
  </script>
</body>
</html>


