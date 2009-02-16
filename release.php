<?php
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['quid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$quid = $_POST['quid'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');
$result = dbQuery('SELECT uid, name, role, rid, question FROM users WHERE uid = '.dbMakeSafe($quid).';');
if(mysql_num_rows($result) != 0) {
	$user = mysql_fetch_assoc($result);
	mysql_free_result($result);
	dbQuery('UPDATE users SET question = NULL WHERE uid = '.dbMakeSafe($quid).';');
	
	dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
					dbMakeSafe($quid).','.dbMakeSafe($user['name']).','.dbMakeSafe($user['role']).
					', "ME" ,'.dbMakeSafe($user['rid']).','.dbMakeSafe($user['question']).');');
}
include('poll.php');  //Get an immediate reply to messages
?>