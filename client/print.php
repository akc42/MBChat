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
// Link to SMF forum as this is only for logged in members
// Show all errors:
error_reporting(E_ALL);

date_default_timezone_set('UTC');
	
$rid = $_GET['rid'];


$tzo = intval($_GET['tzo'])*60;

require_once('../inc/client.inc');

cs_validate();

$print = cs_query('print',$rid,$_GET['start'],$_GET['end']);
$hephaestus = $print['chatbot'];
$nomessages = true;

if ($rid == 99) {
	$room = 'Non Standard';
} else {
	$room = $_GET['room'];
}
function page_title() {
	echo "MB Chat Printing";
}
function head_content() {
	global $print;
	$chatting = cs_query('chats');
?>	<link rel="stylesheet" type="text/css" href="../css/chat-pr.css" title="mbstyle"/>
	<style type="text/css">
	    .textual {
		    margin:20px;
		    border:3px solid black;
		    background-color:#e0e0e0;
		    color:black;
		    padding:20px;
		    font-family:Verdana, Arial, Helvetica, sans-serif;
		    font-size:10pt;
	    }
<?php
		/*
		 * We iterate through all the role/colours creating the css elements that are needed for each
		 */
		foreach($chatting['colours'] as $role => $colour) {
?>		span.<?php echo $role; ?> {
			padding:0 2px;
			color:#<?php echo $colour;?>;   
    	}
<?php 
		}
?>
    </style>

<?php
if($print['des']) {
    /* if we have selected a des key. then the messages we have received are encrypted and we need
        to decrypt them. Therefore we are loading a script to do that.

        Firstly, we have to create a rsa key pair and send the key to the server, where it will encrypt
        the des key and send it back to us.  We send the Request.JSON to 'getdes.php' which does that.  When
        that request completes we then descrypt it back to the proper value before passing it to a routine
        which looks at all the messages we have and takes each one and decrypts them
    */
	if(defined('DEBUG')) {
?>  <script src="../js/mootools-core-1.5.0-full-nocompat.js" type="text/javascript" charset="UTF-8"></script>
<?php 
	} else {
?>  <script src="../js/mootools-core-1.5.0-full-nocompat-yc.js" type="text/javascript" charset="UTF-8"></script>
<?php 
	}
?>	<script src="../js/coordinator.js" type="text/javascript" charset="UTF-8"></script>
    <script src="../js/cipher.js" type="text/javascript" charset="UTF-8"></script>
    <script src="../js/des.js" type="text/javascript" charset="UTF-8"></script>
<?php 
	if(defined('DEBUG')) {
?>  <script src="../js/mbcprint.js" type="text/javascript" charset="UTF-8"></script>
<?php 
	} else {
?>  <script src="../js/mbcprint-min-<?php include('../inc/version.inc');?>.js" type="text/javascript" charset="UTF-8"></script>
<?php 
	}
?>    <script type="text/javascript">
    <!--
        var uid = <?php echo $_GET['uid']; ?>;
        var pass = "<?php echo md5("U".$_GET['uid']."P".sprintf("%010u",ceil(time()/100)*100)); ?>";
        var coord = MBCprint(uid,pass);
        window.addEvent('domready', function() {
            coord.done('dom',{});
        });
    //-->    
    </script><noscript>This application requires that Javascript is enabled in your browser!</noscript>
<?php
}
}
function menu_items() {
?><!-- It is important that chat is called without parameters.  If external authorisation is in place it will jump back to that authentication -->
 <a id="exitPrint" href="../index.php"><img src="../img/exit-forum.gif"/></a><?php 
}
function content_title() {
	echo "Chat History Log";
}

function main_content() {
	global $room,$hephaestus,$print,$tzo,$nomessages;
?><div class="textual"><h2><?php echo $room; ?></h2> 
<h3><?php echo date("D h:i:s a",$_GET['start']-$tzo ).' to '.date("D h:i:s a",$_GET['end']-$tzo) ; ?></h3>

<?php
function message($txt) {
global $row, $i,$hephaestus,$nomessages,$tzo;
	echo '<span class="time">'.date("D h:i:s a",$row['time']-$tzo).'</span> <span class=';
	echo '"C">'.$hephaestus.':</span> <span>'.$row['name'].' '.$txt.'</span><br/>';
	echo "\n";
	$nomessages = false;
}
function umessage($txt) {
global $row,$i,$nomessages,$tzo;
	echo '<span class="time">'.date("D h:i:s a",$row['time']-$tzo).'</span> <span class=';
	echo '"'.$row['role'].'">'.$row['name'].':</span> <span class="dmsg">'.$txt.'</span><br/>';
	echo "\n";
	$nomessages = false;
}

foreach($print['rows'] as $row) {
		switch ($row['type']) {
		case "LI" :
			message('Logs In');
			break;
		case 'LO':
			message('Logs Out');
			break;
		case 'LT':
			message('Logs Out (timeout)');
			break;
		case 'RE':
		case 'PX':
			message('Enters Room');
			break;
		case 'RX':
		case 'PE':
			message('Leaves Room');
			break;
		case 'RM':
			message('Becomes Moderator');
			break;
		case 'RN':
			message('Steps Down from being Moderator');
			break;
		case 'WJ':
			message('Joins whisper no: '.$row['rid']);
			break;
		case 'WL':
			message('Leaves whisper no: '.$row['rid']);
			break;
		case 'ME':
			umessage($row['text']);
			break;
		case 'WH':
			umessage('(whispers to :'.$row['rid'].')'.$row['text']);
			break;
		case 'LH':
			message('Reads Log');
			break;
		default:
		    message('Unknown message type '.$row['type']);
		// Do nothing with these
			break;
		}
}		
if($nomessages) {
	echo '<span class="time">'.date("h:i:s a",$_GET['start']-$tzo).'</span><span class=';
	echo '"C">'.$hephaestus.'</span><span><b>THERE ARE NO MESSAGES TO DISPLAY</b></span><br/>';
	echo "\n";
}
?></div>
<?php 
}

function foot_content() {
 ?>
    <div id="copyright">MB Chat <span id="version"><?php include('../inc/version.inc');?></span> &copy; 2008-2014
        <a href="http://www.chandlerfamily.org.uk">Alan Chandler</a>
    </div>

<?php 
}

require_once($_SERVER['DOCUMENT_ROOT'].'/inc/template.inc');
