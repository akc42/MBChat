<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['rid'])))
	die('{"error": "Hacking attempt - wrong parameters"}');
$uid = $_GET['user'];
if ($_GET['password'] != sha1("Key".$uid))
	die('{"error": "Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid).'"}');
$rid = $_GET['rid'];

define('MBCHAT_MAX_TIME',	3);		//Max hours of message to display in a room
define('MBCHAT_MAX_MESSAGES',	100);		//Max message to display in room initially

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');
dbQuery('START TRANSACTION;');
$result = dbQuery('SELECT rid, name, type FROM rooms WHERE rid = '.dbMakeSafe($rid).';');
if(mysql_num_rows($result) == 0) {
	dbQuery('ROLLBACK;');
	die('{"error":"Invalid Room id"}');
}
$room = mysql_fetch_assoc($result);
mysql_free_result($result);
$result = dbQuery('SELECT uid, name, role, mod FROM users WHERE uid = '.dbMakeSafe($uid).';');
if(mysql_num_rows($result) == 0) {
	dbQuery('ROLLBACK;');
	die('{"error":"Invalid User id"}');
}
$user = mysql_fetch_assoc($result);
mysql_free_result($result);

if ($room['type'] == 'M'  && $user['mod'] != 'N') {
//This is a moderated room, and this person is not normal - so swap them into moderated room role
	$role = $user['mod'];
	$mod = $user['role'];
} else {
	$role = $user['role'];
	$mod = $user['mod'];
}

dbQuery('UPDATE users SET rid = '.dbMakeSafe($rid).', time = NOW(), role = '.dbMakeSafe($role)
			.', mod = '.dbMakeSafe($mod).' WHERE uid = '.dbMakeSafe($uid).';');

dbQuery('INSERT INTO log (uid, name, title, role, type, rid) VALUES ('.
				dbMakeSafe($user['uid']).','.dbMakeSafe($user['name']).','.dbMakeSafe($role).
				', "RE" ,'.dbMakeSafe($rid).');');
$sql = 'SELECT lid, type, uid, name, role, rid, time, text FROM log 
	WHERE NOW() < DATE_ADD(time, INTERVAL '.MBCHAT_MAX_TIME.' HOUR) AND (uid = '.dbMakeSafe($uid).' OR rid = '.dbMakeSafe($rid).' OR rid IN (';
$sql .= 'SELECT wid FROM participants WHERE uid = '.dbMakeSafe($uid).')) ORDER BY lid DESC LIMIT'.MBCHAT_MAX_MESSAGES.';';
$result = dbQuery($sql);
$messages = array();
if(mysql_num_rows($result) != 0) {
	while($row=mysql_fetch_assoc($result)) {
		$message = array();
		$user = array();
		$item = array();
		$item['lid'] = $row['lid'];
		$item['type'] = $row['type'];
		$item['rid'] = $row['rid'];
		$user['uid'] = $row['uid'];
		$user['name'] = $row['name'];
		$user['role'] = $row['role'];
		$item['user'] = $user;
		$message['time'] = $row['time'];
		$message['text'] = $row['text'];
		$item['message'] = $message;
		$messages[]= $item;
	}		
};

dbQuery('COMMIT ;');

echo '{ "room" :'.json_encode($room).', "messages" :'.json_encode(array_reverse($messages)).'}';

?>