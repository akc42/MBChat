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


if ($rid == 99) {
	$room = 'Non Standard';
} else {
	$room = $_GET['room'];
}


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>MB Chat</title>
	<link rel="stylesheet" type="text/css" href="../css/chat-pr.css" title="mbstyle"/>
</head>
<body>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', GOOGLE_ACCOUNT]);
  _gaq.push(['_trackPageview']);
</script>        
<!-- It is important that chat is called without parameters.  If external authorisation is in place it will jump back to that authentication -->
<a id="exitPrint" href="../index.php"><img src="../images/exit.gif"/></a>
<h1>Chat History Log</h1>
<h2><?php echo $room; ?></h2> 
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
	echo '"'.$row['role'].'">'.$row['name'].':</span> <span>'.$txt.'</span><br/>';
	echo "\n";
	$nomessages = false;
}
$nomessages = true;
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
		    umessage('Unknown message type '.$row['type']);
		// Do nothing with these
			break;
		}
}		
if($nomessages) {
	echo '<span class="time">'.date("h:i:s a",$_GET['start']-$tzo).'</span><span class=';
	echo '"C">'.$hephaestus.'</span><span><b>THERE ARE NO MESSAGES TO DISPLAY</b></span><br/>';
	echo "\n";
}
?>
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
