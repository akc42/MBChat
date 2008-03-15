<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['wuid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_GET['user'];
if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));
$wuid = $_GET['wuid'];

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');
dbQuery('START TRANSACTION;');

$result = dbQuery('SELECT uid, name, role, moderator FROM users WHERE uid = '.dbMakeSafe($uid).' OR uid = '.dbMakeSafe($wuid).';');
if(mysql_num_rows($result) != 2) {
	dbQuery('ROLLBACK;');
	die('New Whisper failed - Invalid User id');
}
$user = mysql_fetch_assoc($result);
if ($user['uid'] == $uid ) {
	$wuser = mysql_fetch_assoc($result);
} else {
	$wuser = $user;
	$user = mysql_fetch_assoc($result);
}
mysql_free_result($result);
$result = dbQuery('SELECT wid FROM whisper;');
$whisper = mysql_fetch_assoc($result);
mysql_free_result($result);
$wid = $whisper['wid'];
dbQuery('UPDATE whisper SET wid = '.($wid + 1).' ;');

dbQuery('INSERT INTO participant SET wid = '.dbMakeSafe($wid).', uid = '.dbmakeSafe($wuid).' ;');
dbQuery('INSERT INTO participant SET wid = '.dbMakeSafe($wid).', uid = '.dbmakeSafe($uid).' ;');
dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
	dbMakeSafe($wuid).','.dbMakeSafe($wuser['name']).','.dbMakeSafe($wuser['role']).
	', "WJ" ,'.dbMakeSafe($wid).');');
dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
	dbMakeSafe($uid).','.dbMakeSafe($user['name']).','.dbMakeSafe($user['role']).
	', "WJ" ,'.dbMakeSafe($wid).');');

dbQuery('UPDATE users SET time = NOW() WHERE uid = '.dbMakeSafe($uid).';');

dbQuery('COMMIT ;');


echo '{ "wid" :'.$wid.', "user" :'.json_encode($wuser).'}';

?>