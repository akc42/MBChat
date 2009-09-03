<?php
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['text']) && isset($_POST['wid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));

$wid = $_POST['wid'];
$text = htmlentities(stripslashes($_POST['text']),ENT_QUOTES);   // we need to get the text in an html pure form as possible

define ('MBC',1);   //defined so we can control access to some of the files.
include_once('db.php');
$result = dbQuery('SELECT participant.uid, users.name, role, wid  FROM participant LEFT JOIN users ON users.uid = participant.uid WHERE participant.uid = '
	.dbMakeSafe($uid).' AND wid = '.dbMakeSafe($wid).' ;');
if(mysql_num_rows($result) > 0) {  //only insert into channel if still there

	$row=mysql_fetch_assoc($result);

	dbQuery('UPDATE users SET time = NOW() WHERE uid = '.dbMakeSafe($uid).';');

	if ($text != '') {  //only insert non blank text - ignore other
		dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
			dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
			', "WH" ,'.dbMakeSafe($wid).','.dbMakeSafe($text).');');
		include_once('send.php');
        send_to_all(mysql_insert_id(),$uid, $row['name'],$role,"WH",$wid,$text);	

	}
}
mysql_free_result($result);
echo '{"Status":"OK"}';
?> 
