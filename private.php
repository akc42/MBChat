<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['wid']) && isset($_GET['rid'])))
	die('Private - Hacking attempt - wrong parameters');
$uid = $_GET['user'];

if ($_GET['password'] != sha1("Key".$uid))
	die('Private - Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));

$wid = $_GET['wid'];
$rid = $_GET['rid'];

define ('MBC',1);   //defined so we can control access to some of the files.
include_once('db.php');


dbQuery('START TRANSACTION ;');
if ($wid != 0 ) {
	$result = dbQuery('SELECT participant.uid, users.name, role, wid  FROM participant 
				JOIN users ON users.uid = participant.uid WHERE participant.uid = '.
				dbMakeSafe($uid).' AND wid = '.dbMakeSafe($wid).' ;');

	if(mysql_num_rows($result) == 0) {
		dbQuery('ROLLBACK ;');
		die('Private - invalid wid');
	} 
	$row=mysql_fetch_assoc($result);
	dbQuery('INSERT INTO log (uid, name, role, type, rid ) VALUES ('.
			dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
			', "PE" ,'.dbMakeSafe($wid).');');
} else {

	$result = dbQuery('SELECT uid, name, role FROM users WHERE uid = '.dbMakeSafe($uid).';');
	if(mysql_num_rows($result) == 0) {
		dbQuery('ROLLBACK ;');
		die('Private - invalid uid');
	} 
	$row=mysql_fetch_assoc($result);
	dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
			dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
			', "PX" ,'.dbMakeSafe($rid).');');	
}
mysql_free_result($result);
dbQuery('UPDATE users SET time = NOW() , private = '.dbMakeSafe($wid).' WHERE uid = '.dbMakeSafe($uid).';');

dbQuery('COMMIT ;');
include('poll.php');  //by including this we send current messages immediately
?> 
