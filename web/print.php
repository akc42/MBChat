<?php
/* A new version of chat
	Copyright (c) 2008 Alan Chandler
	Licenced under the GPL
*/
// Link to SMF forum as this is only for logged in members
// Show all errors:
error_reporting(E_ALL);
// Path to the chat directory:

if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['rid'])
	&& isset($_GET['room']) && isset($_GET['start'])&& isset($_GET['end']) && isset($_GET['tzo'])))
	die('Log - Hacking attempt - wrong parameters');
$uid = $_GET['user'];
if ($_GET['password'] != sha1("Key".$uid))
	die('Log - Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));
$rid = $_GET['rid'];

$now = new DateTime();
$dtz = new DateTimeZone(date_default_timezone_get());
$tzo = $_GET['tzo']+$dtz->getOffset($now);

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

$sql = 'SELECT UNIX_TIMESTAMP(time) AS utime, type, rid, uid , name, role, text  FROM log';
$sql .= ' WHERE UNIX_TIMESTAMP(time) > '.dbMakeSafe($_GET['start']).' AND UNIX_TIMESTAMP(time) < '.dbMakeSafe($_GET['end']).' AND rid ';
if ($rid == 99) {
	$sql .= '> 98 ORDER BY rid,lid ;';
	$room = 'Non Standard';
} else {
	$sql .= '= '.dbMakeSafe($rid).' ORDER BY lid ;';
	$room = $_GET['room'];
}
$result = dbQuery($sql);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Melinda's Backups Chat</title>
	<link rel="stylesheet" type="text/css" href="chat-pr.css" title="mbstyle"/>
</head>
<body>
<a id="exitPrint" href="forum.php"><img src="exit.gif"/></a>
<h1>Melinda&#8217;s Backups Chat History Log</h1>
<h2><?php echo $room; ?></h2> 
<h3><?php echo date("D h:i:s a",$_GET['start']-$tzo ).' to '.date("D h:i:s a",$_GET['end']-$tzo) ; ?></h3>

<?php
function message($txt) {
global $row;
	echo '"C">Hephaestus</span><span>'.$row['name'].' '.$txt.'</span><br/>';
	echo "\n";
}
function umessage($txt) {
global $row;
	echo '"'.$row['role'].'">'.$row['name'].'</span><span>'.$txt.'</span><br/>';
	echo "\n";
}
if(mysql_num_rows($result) != 0) {
	while($row=mysql_fetch_assoc($result)) {

		echo '<span class="time">'.date("h:i:s a",$row['utime']-$tzo).'</span><span class=';
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
			message('Enters Room');
			break;
		case 'RX':
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
		// Do nothing with these
			break;
		}
	}		
} else {
	echo '<span class="time">'.date("h:i:s a",$_GET['start']-$tzo).'</span><span class=';
	echo '"C">Hephaestus</span><span><b>THERE ARE NO MESSAGES TO DISPLAY</b></span><br/>';
	echo "\n";
}

mysql_free_result($result);
?>


</body>

</html>
