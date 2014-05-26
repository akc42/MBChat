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
define('DB_VERSION',2);  //This should be the latest version of the database


require_once('./inc/public.inc');
require_once('./inc/client.inc');
/*
 * We need to check that the database is created and up to date with the correct version
 * This is the only time we ever check the database directly
 */
define('DATABASE',DATA_DIR.'chat.db');
define('SQL_DIR',realpath(dirname(__FILE__)).'/inc/');
define('UPDATE_PREFIX',SQL_DIR.'update_');
define('UPDATE_SUFFIX','.sql');
if(!file_exists(DATABASE) ) {
	$db = new SQLite3(DATABASE);
	$db->exec(file_get_contents(SQL_DIR.'database.sql'));	
} else {
	$db = new SQLite3(DATABASE);
	$version = $db->querySingle("SELECT value FROM parameters WHERE name = 'db_version'");
	if($version == NULL) $version = 1;
	$version = intval($version);
	while ($version < DB_VERSION) {
		$db->exec(file_get_contents(UPDATE_PREFIX.$version.UPDATE_SUFFIX));
		$version++;
	}
}

$chatting = cs_query('chats');

function page_title() {
	echo "MB Chat";
}

function head_content() {
	global $chatting;
?>	<link rel="stylesheet" type="text/css" href="css/chat.css" />
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="css/chat-ie.css"/>
	<![endif]-->
<?php 
/*
 * See README.md for details of which components to include in mootools-more
 */
	if(defined('DEBUG')) {
?> 	<script src="js/mootools-core-1.5.0-full-nocompat.js" type="text/javascript" charset="UTF-8"></script>
	<script src="js/mootools-more-1.5.0.js" type="text/javascript" charset="UTF-8"></script>
    <script src="js/soundmanager2.js" type="text/javascript" charset="UTF-8"></script>
<?php 
	} else {
?> 	<script src="js/mootools-core-1.5.0-full-nocompat-yc.js" type="text/javascript" charset="UTF-8"></script>
	<script src="js/mootools-more-1.5.0-yc.js" type="text/javascript" charset="UTF-8"></script>
    <script src="js/soundmanager2-nodebug-jsmin.js" type="text/javascript" charset="UTF-8"></script>
<?php 
	}
?>	<script src="js/coordinator.js" type="text/javascript" charset="UTF-8"></script>
<?php 
	if($chatting['chat']['rsa'] == 'yes'){
?>  <script src="js/cipher.js" type="text/javascript" charset="UTF-8"></script>
<?php 
	}
	if (defined('DEBUG')) { 
?>	<script src="js/mbchat.js" type="text/javascript" charset="UTF-8"></script> 
<?php 
	} else {
?>	<script src="js/mbchat-min-<?php include('../inc/version.inc');?>.js" type="text/javascript" charset="UTF-8"></script>
<?php 
	} 
	if($chatting['chat']['rsa'] == 'yes' || $chatting['chat']['external'] == ''){
?>  <script src="js/md5.js" type="text/javascript" charset="UTF-8"></script>
<?php 
	}
	if (defined('DEBUG')) { 
?>	<script src="js/mbcauth.js" type="text/javascript" charset="UTF-8"></script>
<?php
	} else {
?>	<script src="js/mbcauth-min-<?php include('../inc/version.inc');?>.js" type="text/javascript" charset="UTF-8"></script>
<?php
	}
	if($chatting['chat']['des']) {
?>  <script src="js/des.js" type="text/javascript" charset="UTF-8"></script>
<?php
	}


?>  <script type="text/javascript">
<!--
        var MBChatVersion = "<?php include('./inc/version.inc');?>";
        var remoteError = "<?php echo $chatting['chat']['remote_error'];?>";
        var guestsAllowed = <?php echo (($chatting['chat']['guests_allowed'] == 'yes')?'true':'false'); ?>;
        var rsaExponent ="<?php echo RSA_EXPONENT;?>";
        var rsaModulus="<?php echo RSA_MODULUS;?>";
        var remoteKey="<?php echo md5(REMOTE_KEY); ?>";
        var checkNo = <?php $checkkey = rand(1,9000); echo $checkkey; ?> ;
       	var encCheckNo = "<?php echo bcpowmod($checkkey,RSA_EXPONENT,RSA_MODULUS); ?>" ;

        var soundcoord = new Coordinator(['sound','chat'],function(activity) {
		    MBchat.sounds.init();		//start sound system
        });
        var loginRequestOptions = {};
        var coordinator = new Coordinator(['rsa','login','dom','verify'],function(activity){
<?php
if($chatting['chat']['rsa'] == 'yes'){
?>          loginRequestOptions.e = activity['rsa'].e.toString();
            loginRequestOptions.n = activity['rsa'].n.toString(10);
<?php
}
?>          loginRequestOptions.msg = 'MBChat version:'+MBChatVersion+' using:'+Browser.name+Browser.version;
            loginRequestOptions.msg += ' on:'+Browser.Platform;
            MBchat.init(loginRequestOptions,activity['rsa']);
            window.addEvent('beforeunload', function() {
                MBchat.logout(); //Will send you back from whence you came (if you are not already on the way)
            });
            soundcoord.done('chat',{});
        }); //End coordinator complete function
<?php
if($chatting['chat']['rsa'] == 'yes'){
	/*
	 * From a php perspective, if we are doing rsa, then we contruct a javascript function to 
	 * be called by the rsa.generateAsync function as a callback when its complete 
	 */
?>      var rsa = new RSA();
        function genResult (key,rsa) { 
<?php
} else {
	/*
	 * from a php perspective if we are not doing rsa, then key as a blank is what is returned
	 */
?>		var key = {};

<?php
}
	/*
	 *  this next line is within a function if its rsa, or inline with key={}; for the other situation 
	 */
?>            coordinator.done('rsa',key);
<?php
if($chatting['chat']['rsa'] == 'yes'){
?>        }; //end genResult
        /*
            We are kicking off a process to generate a rsa public/private key pair.  Typically this
            takes about 1.2 seconds or so to run to completion with this key length, so should be done
            before the user has completed his input - which is when we will need the result.  The genResult
            function will be called when complete.  
        */

        rsa.generateAsync(64,65537,genResult);
<?php
}
if($chatting['chat']['external'] == '') {
	/*
	 * We are doing internal authentication, so we call MBCAuth with correct parameters to say so
	 * it then does both coordinator.done['verify'] stage and coodinator.done['login'] stage
	 */
?>		MBCAuth(true,<?php echo $chatting['chat']['purge_guest'];?>);
<?php 
} else {
	/*
	 * We are doing external authentication so we call MBACAuth with parameters to say not doing internal
	 * It then does only the coordinator.done['verify'] stage, leaving the login stage to the external authentication script
	 * which will be added below
	 */
?>		MBCAuth(false);
<?php 
}
?>		soundManager.setup({
            url : 'js/',
            flashVersion : 9, // optional: shiny features (default = 8)
	    	debugMode:false,
            onready : function() {
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
            }
        });
//-->
    </script><noscript>This application requires that Javascript is enabled in your browser!</noscript> 
<?php if($chatting['chat']['external'] <> '') {
	/*
	 * We authenticate through a php script which returns javascript as its output
	 */
?>    <script type="text/javascript" src="<?php
        $data = array( 'pass' => md5(REMOTE_KEY));
        echo  $chatting['chat']['external'].'?'.http_build_query($data);       
            ?>"></script>
<?php 
}
?>  <style type="text/css">
<?php
		/*
		 * We iterate through all the role/colours creating the css elements that are needed for each
		 */
		foreach($chatting['colours'] as $role => $colour) {
?>		span.<?php echo $role; ?> {
			padding:0 2px;
			color:#<?php echo $colour;?>;   
        }
        #chatList span.<?php echo $role; ?> {
        	font-weight: bold;
        }
<?php 
		}
?>	</style>
<?php 
}
function content_title() {
?>	<div id="roomNameContainer"></div>
<?php 
}
function menu_items() {
?><div id="exit" class="exit-f"></div> 
<?php 
}
function main_content() {
	global $chatting;
?>
  <div id="rsa_generator" class="loading"></div> 
    <div id="authblock" class="hide textual">
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
    	<div class="pane_container">
    		<div id="first_left" class="left_pane">
				       
		        <div id="logControls" class="hide">
			        <div id="startTimeBlock">
				        <div id="startTextLog">Log Start Time</div>
				        <div id="minusStartLog"></div><div id="timeShowStartLog" class="timeBlock"></div><div id="plusStartLog"></div>
			        </div>
			        <div id="endTimeBlock">
				        <div id="endTextLog">Log End Time</div>
				        <div id="minusEndLog"></div><div id="timeShowEndLog" class="timeBlock"></div><div id="plusEndLog"></div>
		         	</div>
			        <div id="printLog"></div>
		        </div>
		        <div id="entranceHall"></div>
		    </div>
		    <div id="first_right" class="right_pane">
		        <div id="onlineListContainer">
			        <h4>Users Online</h4>
			        <div id="onlineList" class="loading"></div>
		        </div>
		    </div>
		    <div style="clear:both;"></div>
	    </div>
	    <div class="pain_container">
	    	<div id="second_left" class="left_pane">

	
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
?>		    	</div>
				<div id="chatList" class="whisper"></div>
		    </div>
		    <div id="second_right" class="right_pane textual">
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
			<div style="clear:both;"></div>
        </div>
    </div>

 <?php 
 }
 function foot_content() {
 ?>
    <div id="copyright">MB Chat <span id="version"><?php include('./inc/version.inc');?></span> &copy; 2008-2014
        <a href="http://www.chandlerfamily.org.uk">Alan Chandler</a>
    </div>

<?php 
}

require_once($_SERVER['DOCUMENT_ROOT'].'/inc/template.inc');
