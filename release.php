<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['rid']) && isset($_GET['quid']) && isset($_GET['ques'])))
	die('Hacking attempt - wrong parameters');
$uid = $_GET['user'];
if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));
$rid=$_GET['rid']
$quid = $_GET['quid'];
$ques = $_GET['ques'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');
dbQuery('START TRANSACTION;');
$result = dbQuery('SELECT rid, name, type FROM rooms WHERE rid = '.dbMakeSafe($rid).';');
if(mysql_num_rows($result) == 0) {
	dbQuery('ROLLBACK;');
	die('Release Message - Invalid Room id');
}
$room = mysql_fetch_assoc($result);
mysql_free_result($result);
$result = dbQuery('SELECT uid, name, title, role FROM users WHERE uid = '.dbMakeSafe($quid).';');
if(mysql_num_rows($result) == 0) {
	dbQuery('ROLLBACK;');
	die('Release Message - Invalid User id');
}
$user = mysql_fetch_assoc($result);
mysql_free_result($result);
dbQuery('UPDATE users SET question = NULL , time = NOW() WHERE uid = '.dbMakeSafe($quid).';');

dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
				dbMakeSafe($user['quid']).','.dbMakeSafe($user['name']).','.dbMakeSafe($user['role']).
				', "ME" ,'.dbMakeSafe($rid).','.dmMakeSafe($ques).');');

dbQuery('COMMIT ;');

echo '{ "lastid" : '.mysql_insert_id().' }';

?>