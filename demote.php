<?php
if(!(isset($_GET['user']) && isset($_GET['password'])))
	die('Hacking attempt - wrong parameters');
$uid = $_GET['user'];
if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');
dbQuery('START TRANSACTION;');
$result = dbQuery('SELECT uid, name, title, role, rid, moderator FROM users WHERE uid = '.dbMakeSafe($uid).';');
if(mysql_num_rows($result) == 0) {
	dbQuery('ROLLBACK;');
	die('Release Message - Invalid User id');
}
$user = mysql_fetch_assoc($result);
mysql_free_result($result);

if ($user['role'] != 'M' ) {
	dbQuery('ROLLBACK;');
	die('Release Message - Invalid User id');
} 

dbQuery('UPDATE users SET role = '.dbMakeSafe($user['moderator']).', moderator = "N", time = NOW() WHERE uid = '.dbMakeSafe($uid).';');

dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
				dbMakeSafe($uid).','.dbMakeSafe($user['name']).', '.dbMakeSafe($user['moderator']).', "RN" ,'.
				dbMakeSafe($user['rid']).');');

dbQuery('COMMIT ;');
include('poll.php');  //Get an immediate reply to messages
?>