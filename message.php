<?php
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['text']) && isset($_POST['rid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));

$rid = $_POST['rid'];
$text = htmlentities(stripslashes($_POST['text']),ENT_QUOTES);   // we need to get the text in an html pure form as possible

define ('MBC',1);   //defined so we can control access to some of the files.
include_once('db.php');

$result = dbQuery('SELECT uid, users.name, role, question, users.rid, type FROM users LEFT JOIN rooms ON users.rid = rooms.rid WHERE uid = '
	.dbMakeSafe($uid).' ;');
if(mysql_num_rows($result) != 0) {
	
	
	$row=mysql_fetch_assoc($result);
	mysql_free_result($result);
	
	$role = $row['role'];
	$type = $row['type'];
	$mtype = '' ;
	if ($type == 'M' && $role != 'M' && $role != 'H' && $role != 'G' && $role != 'S' ) {
	//we are in a moderated room and not allowed to speak, so we just update the question we want to ask
		if( $text == '') {
			dbQuery('UPDATE users SET time = NOW(), question = NULL, rid = '.dbMakeSafe($rid).
				' WHERE uid = '.dbMakeSafe($uid).';');
			$mtype = "MR";
		} else {
			dbQuery('UPDATE users SET time = NOW(), question = '.dbMakeSafe($text).', rid = '.dbMakeSafe($rid).
				' WHERE uid = '.dbMakeSafe($uid).';');
			$mtype = "MQ";
		}
	} else {
		//just indicate presemce
		dbQuery('UPDATE users SET time = NOW(), question = NULL, rid = '.dbMakeSafe($rid).
			' WHERE uid = '.dbMakeSafe($uid).';');
		if ($text != '') {  //only insert non blank text - ignore other
		    $mtype = "ME";
		}
	}
	include_once('send.php');
	if ($mtype != '') {
		dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
					dbMakeSafe($row['uid']).','.dbMakeSafe($row['name']).','.dbMakeSafe($role).
					','.dbMakeSafe($mtype).','.dbMakeSafe($rid).','.dbMakeSafe($text).');');
        send_to_all(mysql_insert_id(),$uid, $row['name'],$role,$mtype,$rid,$text);	
    }
}
echo '{"Status":"OK"}';
?> 
