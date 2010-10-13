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

/* gets time boundary - either next 5 minutes (twoup = 0) or further five minutes after that (as 12 char string). */

require_once('./inc/public.inc');
require_once('./inc/client.inc');


$chatting = cs_query('chats');

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" type="text/css" href="css/header.css" />
    <title>MB Chat</title>
	<link rel="stylesheet" type="text/css" href="css/chat.css" />
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="css/chat-ie.css"/>
	<![endif]-->
    <script src="js/mootools-1.2.4-core.js" type="text/javascript" charset="UTF-8"></script>
	<script src="js/coordinator.js" type="text/javascript" charset="UTF-8"></script>
	<script src="js/mootools-1.2.4.4-more-chat.js" type="text/javascript" charset="UTF-8"></script>
<?php if(!(EXTERNAL_AUTHENTICATION)){?>    <script src="js/cipher.js" type="text/javascript" charset="UTF-8"></script><?php } ?>
	<script src="js/mbchat.js" type="text/javascript" charset="UTF-8"></script> 
<?php if(!(EXTERNAL_AUTHENTICATION)){?>    <script src="js/md5.js" type="text/javascript" charset="UTF-8"></script><?php } ?> 
    <script src="js/soundmanager2-nodebug-jsmin.js" type="text/javascript" charset="UTF-8"></script>
    <script src="js/mbcauth.js" type="text/javascript" charset="UTF-8"></script>
<?php
if(!(EXTERNAL_AUTHENTICATION) && $chatting['chat']['des']) {
?>  <script src="js/des.js" type="text/javascript" charset="UTF-8"></script>
<?php
}


?>  <script type="text/javascript">
        var MBChatVersion = "<?php include('./inc/version.inc');?>";
        var remoteError = "<?php echo $chatting['chat']['remote_error'];?>";
        var guestsAllowed = <?php echo (($chatting['chat']['guests_allowed'] == 'yes')?'true':'false'); ?>;
        var rsaExponent ="<?php echo RSA_EXPONENT;?>";
        var rsaModulus="<?php echo RSA_MODULUS;?>";
        var remoteKey="<?php echo md5(REMOTE_KEY); ?>";
        
        var soundcoord = new Coordinator(['sound','chat'],function(activity) {
		    MBchat.sounds.init();		//start sound system
        });
        var loginRequestOptions = {};
        var coordinator = new Coordinator(['rsa','login','dom','verify'],function(activity){
<?php
if(!(EXTERNAL_AUTHENTICATION)){
?>          loginRequestOptions.e = activity.get('rsa').e.toString();
            loginRequestOptions.n = activity.get('rsa').n.toString(10);
<?php
}
?>          loginRequestOptions.msg = 'MBChat version:'+MBChatVersion+' using:'+Browser.Engine.name+Browser.Engine.version;
            loginRequestOptions.msg += ' on:'+Browser.Platform.name;
            MBchat.init(loginRequestOptions,activity.get('rsa'));
            window.addEvent('beforeunload', function() {
                MBchat.logout(); //Will send you back from whence you came (if you are not already on the way)
            });
            soundcoord.done('chat',{});
        });
<?php
if(!(EXTERNAL_AUTHENTICATION)){
?>
        var rsa = new RSA();
        function genResult (key,rsa) {
<?php
} else {
?>		var key = false;
<?php
}
?>            coordinator.done('rsa',key);
<?php
if(!(EXTERNAL_AUTHENTICATION)){
?>        };
        /*
            We are kicking off a process to generate a rsa public/private key pair.  Typically this
            takes about 1.2 seconds or so to run to completion with this key length, so should be done
            before the user has completed his input - which is when we will need the result.  The genResult
            function will be called when complete.  
        */

        rsa.generateAsync(64,65537,genResult);
<?php
}
?>
        MBCAuth(); //Authenticate server and do internal authentication
        soundManager.url = '/js/';
        soundManager.flashVersion = 9; // optional: shiny features (default = 8)

        soundManager.onready (function() {
            if (soundManager.supported()) {

                soundManager.createSound({
	                id : 'whispers',
	                url : "sounds/<?php echo $chatting['sounds']['whisper'] ?>",
	                autoLoad : true ,
	                autoPlay : false 
                });
                soundManager.createSound({
	                id : 'move',
	                url : "sounds/<?php echo $chatting['sounds']['move'] ; ?>",
	                autoLoad : true ,
	                autoPlay : false 
                });
                soundManager.createSound({
	                id : 'speak',
	                url : "sounds/<?php echo $chatting['sounds']['speak'] ; ?>",
	                autoLoad : true ,
	                autoPlay : false 
                });
                soundManager.createSound({
	                id : 'creaky',
	                url : "sounds/<?php echo $chatting['sounds']['creaky'] ; ?>",
	                autoLoad : true ,
	                autoPlay : false
                });
                soundManager.createSound({
	                id : 'music',
	                url : "sounds/<?php echo $chatting['sounds']['music'] ; ?>",
	                autoLoad : true ,
	                autoPlay : false
                });
                soundcoord.done('sound',{});
            }
        });
    </script>
<?php if(EXTERNAL_AUTHENTICATION) {
?>    <script type="text/javascript" src="<?php
        $data = array( 'pass' => md5(REMOTE_KEY));
        echo  EXTERNAL_AUTHENTICATOR.'?'.http_build_query($data);       
            ?>"></script>
<?php 
}
?>    <style type="text/css">
    
        /* these are the classes related to user types */
        /* admin */
        span.A {
	        color:#<?php echo $chatting['colours']['A'];?>;   
        }

        /*leadership team */
        span.L {
	        color:#<?php echo $chatting['colours']['L'];?>;   
        }
        /* head */
        span.H {
	        color:#<?php echo $chatting['colours']['H'];?>;
        }
        /* special guests */
        span.G {
	        color:#<?php echo $chatting['colours']['G'];?>;
        } 
        /* Special is ordinary members promoted */
        span.S {
	        color:#<?php echo $chatting['colours']['S'];?>;
        }
        /* moderator */
        span.M {
	        color:#<?php echo $chatting['colours']['M'];?>;
        }
        /* guests */
        span.B {	
	        color:#<?php echo $chatting['colours']['B'];?>;
        }
        /* regular members */
        span.R {
	        color:#<?php echo $chatting['colours']['R'];?>;
        }
        /* chatbot */
        span.C {
            color:#<?php echo $chatting['colours']['C'];?>;
        }
    </style>

</head>
<body>
<?php require('./inc/header.inc'); ?>
<div id="roomNameContainer"></div>
<div id="content">
  <div id="rsa_generator" class="loading"></div> 
    <div id="authblock" class="hide">
<?php
if($chatting['chat']['guests_allowed'] == 'yes') {
?>
        <p>Enter a username that you want to be known of in chat. You only need to enter a  password <strong>if you are already
        registered as a user</strong> and wish to use that in chat. <strong>Note</strong>, for regular registered users, if you are already logged 
        in your connection will be <strong>refused</strong>.</p>

        <p>Please <strong>note</strong> that $ characters will <strong>not</strong> be allowed in user
        names.</p>
<?php
} else {
?>
        <p>Please Enter your username and password, but note, if you are already logged in 
        in another window your connection will be <strong>refused</strong>.</p>
<?php
}
?>
        <div id="login_error" class="hide">Incorrect Credentials</div>
        <form id="login" action="/" enctype="application/x-www-form-urlencoded">
            <table>
                <tr><td>Username:</td><td><input type="text" name="username" value="" /></td></tr>
                <tr><td>Password:</td><td><input type="password" name="password" value="" /></td></tr>
                <tr><td><input type="submit" name="signin" value="Sign In"/></td><td></td></tr>
            </table>
        </form>
    </div>

    <div id="chatblock" class="hide">
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
        <div id="entranceHall"></div>
        <div id="onlineListContainer">
	        <h4>Users Online</h4>
	        <div id="onlineList" class="loading"></div>
        </div>
        <div id="chatList" class="whisper"></div>	

        <div id="inputContainer" class="hide">
	        <form id="messageForm" action="/" enctype="application/x-www-form-urlencoded" autocomplete="off" >
		        <input id="messageText" type="text" name="text" />
		        <input type="submit" name="submit" value="Send"/>
            </form>
        </div>
        <div id="whisperBoxTemplate">
	        <div class="private"></div><div class="dragHandle">Whisper Box</div><div class="closeBox"></div>
	        <div class="whisperList"></div>
	        <form action="/" enctype="application/x-www-form-urlencoded" autocomplete="off" >
		        <input type="text" name="text" class="whisperInput" />
		        <input type="submit" name="submit" value="Send" class="whisperSend"/>
	        </form>
        </div>
        <div id="emoticonContainer" class="hide">
<?php
$dir = $chatting['chat']['emoticon_dir'];
$urlbase = $chatting['chat']['emoticon_url'];
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
?>      </div>
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
    <div id="copyright">MB Chat <span id="version"><?php include('./inc/version.inc');?></span> &copy; 2008-2010
        <a href="http://www.chandlerfamily.org.uk">Alan Chandler</a>
    </div>
</div>
<?php require('./inc/footer.inc'); ?>
</body>
</html>

