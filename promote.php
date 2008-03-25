<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['puid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_GET['user'];
if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));
$puid = $_GET['puid'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');
dbQuery('START TRANSACTION;');
$result = dbQuery('SELECT uid, name, role, rid, moderator, question  FROM users WHERE uid = '.dbMakeSafe($puid).';');
if(mysql_num_rows($result) == 0) {
	dbQuery('ROLLBACK;');
	die('Release Message - Invalid User id');
}
$user = mysql_fetch_assoc($result);
mysql_free_result($result);

if ($user['role'] == 'M' || $user['role'] == 'S') {
	//already someone special 
	$mod =$user['moderator'];
} else {
	$mod = $user['role'];
}
dbQuery('UPDATE users SET role = "M", moderator = '.dbMakeSafe($mod).', time = NOW() , question = NULL WHERE uid = '.dbMakeSafe($puid).';');
dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
				dbMakeSafe($puid).','.dbMakeSafe($user['name']).', "M" , "RM" ,'.dbMakeSafe($user['rid']).');');
if ($user['question'] != '' ) {
	dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('. 
				dbMakeSafe($puid).','.dbMakeSafe($user['name']).
				', "M" , "ME" ,'.dbMakeSafe($user['rid']).','.dbMakeSafe($user['question']).');');
}

dbQuery('COMMIT ;');
include('poll.php');  //Get an immediate reply to messages
?>