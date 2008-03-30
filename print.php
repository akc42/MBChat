<?php
/* A new version of chat
	Copyright (c) 2008 Alan Chandler
	Licenced under the GPL
*/
// Link to SMF forum as this is only for logged in members
// Show all errors:
error_reporting(E_ALL);
// Path to the chat directory:

function message($txt) {
	echo '"C">Hephaestus</span><span>'.$row['name'].' '.$txt.'</span></div>\n';
}
function umessage($txt) {
	echo '"'.$row['role'].'">'.$row['name'].'</span><span>'.$txt.'</span></div>\n';
}

if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['rid']) && isset($_GET['room']) && isset($_GET['start'])&& isset($_GET['end'])))
	die('Log - Hacking attempt - wrong parameters');
$uid = $_GET['user'];
if ($_GET['password'] != sha1("Key".$uid))
	die('Log - Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));
$rid = $_GET['rid'];

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');
dbQuery('START TRANSACTION;');
$result = dbQuery('SELECT uid, name, role FROM users WHERE uid = '.dbMakeSafe($uid).';');
if(mysql_num_rows($result) == 0) {
	dbQuery('ROLLBACK;');
	die('Log - Invalid User id');
}
$user = mysql_fetch_assoc($result);
mysql_free_result($result);

dbQuery('UPDATE users SET time = NOW() WHERE uid = '.dbMakeSafe($uid).';');

dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
				dbMakeSafe($uid).','.dbMakeSafe($user['name']).','.dbMakeSafe($user['role']).
				', "LH" ,'.dbMakeSafe($rid).');');


$sql = 'SELECT UNIX_TIMESTAMP(time) AS utime, type, rid, uid , name, role, text  FROM log';
$sql .= ' WHERE UNIX_TIMESTAMP(time) > '.dbMakeSafe($_GET['start']).' AND UNIX_TIMESTAMP(time) < '.dbMakeSafe($_GET['end']).' AND rid ';
if ($rid == 99) {
	$sql .= '> 98 ORDER BY rid,lid ;';
} else {
	$sql .= '= '.dbMakeSafe($rid).' ORDER BY lid ;';
}
$result = dbQuery($sql);
if(mysql_num_rows($result) != 0) {

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Melinda's Backups Chat</title>
	<link rel="stylesheet" type="text/css" href="chat.css" title="mbstyle"/>
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="chat-ie.css"/>
	<![endif]-->
</head>
<body>
<a id="exitPrint" href="/chat">&nbsp;</a>
<h1>Melinda&#8217;s Backups Chat History Log</h1>
<h2></h2> <!-- Room Name to go in here -->
<h3></h3> <!-- Dates to go in here -->
<div id="printContent">
<?php
	while($row=mysql_fetch_assoc($result)) {

		echo '<div><span class="time">'.date("h:i:s a",$row['utime']).'</span><span class=';
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
		default:
		// Do nothing with these
			break;
		}
	}		
};
mysql_free_result($result);
dbQuery('COMMIT ;');
?>
</div>

</body>

</html>