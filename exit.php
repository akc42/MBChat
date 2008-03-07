<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['rid'])))
	die('{error: "Hacking attempt - wrong parameters"}');
$uid = $_GET['user'];
if ($_GET['password'] != sha1("Key".$uid))
	die('{error: "Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid).'"}');
$rid = $_GET['rid'];

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');
dbQuery('START TRANSACTION;');
$result = dbQuery('SELECT rid, name, type FROM rooms WHERE rid = '.dbMakeSafe($rid).';');
if(mysql_num_rows($result) == 0) {
	dbQuery('ROLLBACK;');
	die('{error: "Invalid Room id"}');
}
$room = mysql_fetch_assoc($result);
mysql_free_result($result);
$result = dbQuery('SELECT uid, name, role FROM users WHERE uid = '.dbMakeSafe($uid).';');
if(mysql_num_rows($result) == 0) {
	dbQuery('ROLLBACK;');
	die('{error: "Invalid User id" }');
}
$user = mysql_fetch_assoc($result);
mysql_free_result($result);

if ($room['type'] == 'M'  && $user['mod'] != 'N') {
//This is a moderated room, and this person is not normal - so swap them out of moderated room role
	$role = $user['mod'];
	$mod = $user['role'];
} else {
	$role = $user['role'];
	$mod = $user['mod'];
}

dbQuery('UPDATE users SET rid = 0, time = NOW(), role = '.dbMakeSafe($role)
			.', mod = '.dbMakeSafe($mod).' WHERE uid = '.dbMakeSafe($uid).';');


dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
				dbMakeSafe($user['uid']).','.dbMakeSafe($user['name']).','.dbMakeSafe($role).
				', "RX" ,'.dbMakeSafe($rid).');');

dbQuery('COMMIT ;');

echo '{ "lastid" : '.mysql_last_id().' }';

?>