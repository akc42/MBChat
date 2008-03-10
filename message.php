<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_POST['text']) && isset($_POST['room']))
	die('Hacking attempt - wrong parameters');
$uid = $_GET['user'];

if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));

$rid = $_POST['room'];
$text = htmlentities(stripslashes($_POST['text']),ENT_QUOTES);   // we need to get the text in an html pure form as possible

define ('MBC',1);   //defined so we can control access to some of the files.
include_once('db.php');


dbQuery('START TRANSACTION;');
$result = dbQuery('SELECT uid, users.name, role, question, users.rid, type FROM users LEFT JOIN rooms ON users.rid = rooms.rid WHERE uid = '
	.dbMakeSafe($uid).' ;');
if(mysql_num_rows($result) == 0) {
	dbQuery('ROLLBACK;');
	die('Message Send - Invalid User Id');
}


$row=mysql_fetch_assoc($result);
mysql_free_result($result);
if ($row['rid'] != $rid ) { //means we have the same person logged in twice in two rooms, we need to move them together

	dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
				dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
				', "RX" ,'.dbMakeSafe($row['rid']).');');
	dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
				dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
				', "RE" ,'.dbMakeSafe($rid).');');

	$result = dbQuery('SELECT uid, users.name, role, question, users.rid, type FROM users LEFT JOIN rooms ON users.rid = rooms.rid WHERE uid = '
		.dbMakeSafe($uid).' ;');
	$row=mysql_fetch_assoc($result);
	mysql_free_result($result);
}

$role = $row['role'];
$type = $row['type'];

if ($type == 'M' && $role != 'M' && $role != 'H' && $role != 'G' && role != 'S' ) {
//we are in a moderated room and not allowed to speak, so we just update the question we want to ask
	if( $text == '') {
		dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
				dbMakeSafe($row['uid']).','.dbMakeSafe($row['name']).','.dbMakeSafe($role).
				', "MR" ,'.dbMakeSafe($row['rid']).', NULL );');
		dbQuery('UPDATE users SET time = NOW(), question = NULL WHERE uid = '.dbMakeSafe($uid).';');
	} else {
		dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
				dbMakeSafe($row['uid']).','.dbMakeSafe($row['name']).','.dbMakeSafe($role).
				', "MQ" ,'.dbMakeSafe($row['rid']).','.dbMakeSafe($text).');');
		dbQuery('UPDATE users SET time = NOW(), question = '.dbMakeSafe($text).' WHERE uid = '.dbMakeSafe($uid).';');
	}
} else {
	if ($text != '') {  //only insert non blank text - ignore other
		dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
				dbMakeSafe($row['uid']).','.dbMakeSafe($row['name']).','.dbMakeSafe($role).
				', "ME" ,'.dbMakeSafe($row['rid']).','.dbMakeSafe($text).');');
	}
	dbQuery('UPDATE users SET time = NOW() WHERE uid = '.dbMakeSafe($uid).';');
}
dbQuery('COMMIT ;');
echo '{"lastid":'.mysql_insert_id().'}';
?> 
