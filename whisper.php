<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['text']) && isset($_GET['wid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_GET['user'];

if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));

$wid = $_GET['wid'];
$text = htmlentities(stripslashes($_GET['text']),ENT_QUOTES);   // we need to get the text in an html pure form as possible

define ('MBC',1);   //defined so we can control access to some of the files.
include_once('db.php');


dbQuery('START TRANSACTION;');
$result = dbQuery('SELECT participant.uid, users.name, role, wid  FROM participant LEFT JOIN users ON users.uid = participant.uid WHERE participant.uid = '
	.dbMakeSafe($uid).' AND wid = '.dbMakeSafe($wid).' ;');
if(mysql_num_rows($result) > 0) {  //only insert into channel if still there

	$row=mysql_fetch_assoc($result);

	if ($text != '') {  //only insert non blank text - ignore other
		dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
			dbMakeSafe($row['uid']).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
			', "WH" ,'.dbMakeSafe($wid).','.dbMakeSafe($text).');');
	}
}
mysql_free_result($result);
dbQuery('UPDATE users SET time = NOW() WHERE uid = '.dbMakeSafe($uid).';');

dbQuery('COMMIT ;');
include('poll.php');  //by including this we send current messages immediately
?> 
