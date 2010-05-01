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


require_once('./inc/client.inc');

cs_start_server();

$chatting = cs_query('chats');

if($chatting['chat']['ext_user_auth'] == 'yes') {
    if(!isset($_POST['uid'])) {
        header("Location:".$chatting['chat']['remote_start']); //
        exit;
    }
}


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" type="text/css" href="css/header.css" />
    <title>Hartley Chat</title>
	<link rel="stylesheet" type="text/css" href="css/chat.css" />
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="/css/chat-ie.css"/>
	<![endif]-->
<?php
if(!isset($_REQUEST['lite'])) {
?>	<script src="/js/soundmanager2-nodebug-jsmin.js" type="text/javascript" charset="UTF-8"></script>
<?php
}
?><script src="js/mootools-1.2.4-core-chat-nc.js" type="text/javascript" charset="UTF-8"></script>
	<script src="js/coordinator.js" type="text/javascript" charset="UTF-8"></script>
	<script src="js/mootools-1.2.4.4-more-chat-nc.js" type="text/javascript" charset="UTF-8"></script>
	<script src="js/<?php echo (isset($_REQUEST['lite']))?'mbclite.js':'mbchat.js' ; ?>" type="text/javascript" charset="UTF-8"></script> 
    <script src="js/cipher/packages.js" type="text/javascript" charset="UTF-8"></script>
    <script src="js/cipher/binary.js" type="text/javascript" charset="UTF-8"></script>
	<script src="js/cipher/BigInteger.init1.js" type="text/javascript" charset="UTF-8"></script>
    <script src="js/cipher/RSA.init1.js" type="text/javascript" charset="UTF-8"></script>
    <script src="js/cipher/SecureRandom.js" type="text/javascript" charset="UTF-8"></script>
    <script src="js/cipher/BigInteger.init2.js" type="text/javascript" charset="UTF-8"></script>
    <script src="js/cipher/RSA.init2.js" type="text/javascript" charset="UTF-8"></script> 
    <script src="js/cipher/nonstructured.js" type="text/javascript" charset="UTF-8"></script>
    <script src="js/cipher/BigInteger.init3.js" type="text/javascript" charset="UTF-8"></script>
    <script src="js/cipher/RSA.init3.js" type="text/javascript" charset="UTF-8"></script> 
    <script type="text/javascript">
        var MBChatVersion = "<?php include('./inc/version.inc');?>";
<?php
/*
    login request options will ultimately be passed by mbchat.js to the client/login.php routine.  For external
    authorisation, we need the values that login would have looked up in its local database to be provided as post
    parameters.  Since this is after an digest has been authorized we can assume them to be correctly setup
*/
if($chatting['chat']['ext_user_auth'] == 'yes') { 
    $remote_key = bcpowmod($post['pass'],$chatting['chat']['rsa_private'],$chatting['chat']['rsa_public']);  //decript using private key
    if ($remote_key == $chatting['chat']['remote_key'].$_POST['uid']) {  //we can assume we have a valid user
?>
        var loginRequestOptions = {
            name:<?php echo $_POST['name']; ?>,
            role:<?php echo $_POST['role']; ?>,
            mod:<?php echo $_POST['mod'];?>,
            whi:<?php echo $_POST['whi'];?>,
            cap:<?php echo $_POST['grp'];?>
        }
        login('$$$'.$POST['uid'],<?php echo $remote_key;?>); //We have to do the same calculation as above. to properly authorise
<?php
    } else {
        cs_forbidden();
    }            
} else {
?>
        var loginRequestOptions = {};
<?php
}

if(!isset($_REQUEST['lite'])) {
?>
        var soundcoord = new Coordinator(['sound','chat'],function(activity) {
		    MBchat.sounds.init();		//start sound system
        });
<?php
}
?>
        var coordinator = new Coordinator(['dom','rsa','login'],function(activity){
            loginRequestOptions.e = activity.get('rsa').e.toString();
            loginRequestOptions.n = activity.get('rsa').n.toString(10);
            loginRequestOptions.msg = 'MBChat version:'+MBChatVersion+' using:'+Browser.Engine.name+Browser.Engine.version;
            loginRequestOptions.msg += ' on:'+Browser.Platform.name;
            MBchat.init(activity.get('login').user,activity.get('login').pass,loginRequestOptions,activity.get('rsa'));
            window.addEvent('beforeunload', function() {
	            MBchat.logout(); //Will send you back from whence you came (if you are not already on the way)
            });
<?php
if(!isset($_REQUEST['lite'])) {
?>
            soundcoord.done('chat',{});
<?php
}
?>
        });


        var rsa = new RSA();

        
        var genResult = function (key,rsa) {
            coordinator.done('rsa',key);
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
        var login = function(user,pass) {
            var me = {};
            me.user = user;
            me.pass = pass;
            coordinator.done('login',me);    
        };


        window.addEvent('domready', function() {
            coordinator.done('dom',{});

        });
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
    <style type="text/css">
    
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
<table id="header" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" >
<tbody>
	<tr>
	<td align="left" width="30" class="topbg_l" height="70">&nbsp;</td>
	<td align="left" colspan="2" class="topbg_r" valign="top"><a href="/" alt="Main Site Home Page">
		<img  style="margin-top: 24px;" src="images/chandlerfamily_logo.png" alt="Chandler's Zen" border="0" /></a>	
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
	</tr>
</tbody>
</table>
<div id="roomNameContainer"></div>
<div id="content">
<?php
if($chatting['chat']['ext_user_auth'] != 'yes') { 
?>  <div id="rsa_generator" class="hide loading"></div> 
    <div id="authblock">
<?php
    if(!isset($_REQUEST['lite']) ){
?>  <p>If you would like to use the lite version of chat please click <a href="index.php?lite=yes">here</a> and the page will reload with
    that option selected</p>
<?php
    }
    if($chatting['chat']['guests_allowed'] == 'yes') {
     
?>
    <p>Enter a username that you want to be known of in chat.  You only need to enter a  password <strong>if you are already
    registered as a user</strong> as this will be used to check your credentials in the database and will log you in as a normal
    user.</p>

    <p>If you are already logged in your connection will be <strong>refused</strong>.</p>

    <p>Guest users should just enter the name they wish to be known as in chat and leave the password field <strong>empty</strong>.  There will
    be no check for whether you are already connected.  Please <strong>note</strong> that $ characters will <strong>not</strong> be allowed in user
    names.</p>
<?php
    } else {
?>
    <p>Please Enter your username and password, but note, if you are already logged in 
    in another window your connection will be <strong>refused</strong>.</p>
<?php
    }
?>
    <script type="text/javascript">
        var login_step1 = function() {
            var user = document.id('login').username.value;
            var pass = document.id('login').password.value;
            if(user.contains('$')) {
                loginError(false);
                return false;
            }

<?php
    if($chatting['chat']['guests_allowed'] == 'yes') {
?>
            if(pass == '') {
                pass = 'guest'
                user = '$$G'+user;
            }
<?php
    } else {
?>
            if(pass == '') {
                loginError(false);
                return false
            }
<?php
    }
?>
            document.id('rsa_generator').removeClass('hide');
            document.id('authblock').addClass('hide');
            document.id('login_error').addClass('hide');
            document.id('login').username.removeClass('error');
            document.id('login').password.removeClass('error');
            login(user,pass);
            return false;
        }
        var loginError = function() { 
            document.id('login_error').removeClass('hide');
            if(usernameError) {
                document.id('login').username.addClass('error');
                document.id('login').password.addClass('error');
            } else {
                document.id('login').password.addClass('error');
            }
        }
    </script>
        <div id="login_error" class="hide">Incorrect Credentials</div>
        <form id="login" action="#" method="post" onsubmit="javascript:return login_step1()">
            <table>
                <tr><td>Username:</td><td><input type="text" name="username" value="" /></td></tr>
                <tr><td>Password:</td><td><input type="password" name="password" value="" /></td></tr>
                <tr><td><input type="submit" name="submit" value="Sign In"/></td><td></td></tr>
            </table>
        </form>
    </div>

<?php
} else {
?>  <div id="rsa_generator" class="loading"></div>
<?php
}
?><script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-6767755-1']);
  _gaq.push(['_trackPageview']);
</script>

<div id="chatblock" class="hide">
    <div id="exit" class="exit-f"></div>
<?php if(!isset($_REQUEST['lite'])) {
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
<?php 
}
?><div id="entranceHall"></div>
<div id="onlineListContainer">
	<h4>Users Online</h4>
	<div id="onlineList" class="loading"></div>
</div>
<div id="chatList" class="whisper"></div>	

<div id="inputContainer">
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
<?php
if(!isset($_REQUEST['lite'])) {
?><div id="emoticonContainer">
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
?><div id="userOptions">
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
?>
</div>
</div>
<div id="copyright">MB Chat <span id="version"><?php include('./inc/version.inc');?></span> &copy; 2008-2010
    <a href="http://www.chandlerfamily.org.uk">Alan Chandler</a></div>
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

