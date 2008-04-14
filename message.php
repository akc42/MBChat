<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['text']) && isset($_GET['rid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_GET['user'];

if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));

$rid = $_GET['rid'];
$text = htmlentities(stripslashes($_GET['text']),ENT_QUOTES);   // we need to get the text in an html pure form as possible

define ('MBC',1);   //defined so we can control access to some of the files.
include_once('db.php');



$result = dbQuery('SELECT uid, users.name, role, question, users.rid, type FROM users LEFT JOIN rooms ON users.rid = rooms.rid WHERE uid = '
	.dbMakeSafe($uid).' ;');
if(mysql_num_rows($result) != 0) {
	
	
	$row=mysql_fetch_assoc($result);
	mysql_free_result($result);
	
	$role = $row['role'];
	$type = $row['type'];
	
	if ($type == 'M' && $role != 'M' && $role != 'H' && $role != 'G' && $role != 'S' ) {
	//we are in a moderated room and not allowed to speak, so we just update the question we want to ask
		if( $text == '') {
			dbQuery('UPDATE users SET time = NOW(), question = NULL, rid = '.dbMakeSafe($rid).
				' WHERE uid = '.dbMakeSafe($uid).';');
			dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
					dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($role).
					', "MR" ,'.dbMakeSafe($rid).', NULL );');
		} else {
			dbQuery('UPDATE users SET time = NOW(), question = '.dbMakeSafe($text).', rid = '.dbMakeSafe($rid).
				' WHERE uid = '.dbMakeSafe($uid).';');
			dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
					dbMakeSafe($row['uid']).','.dbMakeSafe($row['name']).','.dbMakeSafe($role).
					', "MQ" ,'.dbMakeSafe($row['rid']).','.dbMakeSafe($text).');');
		}
	} else {
		//just indicate presemce
		dbQuery('UPDATE users SET time = NOW(), question = NULL, rid = '.dbMakeSafe($rid).
			' WHERE uid = '.dbMakeSafe($uid).';');
		if ($text != '') {  //only insert non blank text - ignore other
			dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
					dbMakeSafe($row['uid']).','.dbMakeSafe($row['name']).','.dbMakeSafe($role).
					', "ME" ,'.dbMakeSafe($rid).','.dbMakeSafe($text).');');
		}
	}
}

include('poll.php');  //Get an immediate reply to messages
?> 
