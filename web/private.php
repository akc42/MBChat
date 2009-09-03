<?php
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['wid']) && isset($_POST['rid'])))
	die('Private - Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Private - Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));

$wid = $_POST['wid'];
$rid = $_POST['rid'];

define ('MBC',1);   //defined so we can control access to some of the files.
include_once('db.php');


if ($wid != 0 ) {
	$result = dbQuery('SELECT participant.uid, users.name, role, wid  FROM participant 
				JOIN users ON users.uid = participant.uid WHERE participant.uid = '.
				dbMakeSafe($uid).' AND wid = '.dbMakeSafe($wid).' ;');

	if(mysql_num_rows($result) != 0) {
		$row=mysql_fetch_assoc($result);
		dbQuery('UPDATE users SET time = NOW() , private = '.dbMakeSafe($wid).' WHERE uid = '.dbMakeSafe($uid).';');
		dbQuery('INSERT INTO log (uid, name, role, type, rid ) VALUES ('.
				dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
				', "PE" ,'.dbMakeSafe($wid).');');
		include_once('send.php');
        send_to_all(mysql_insert_id(),$uid, $row['name'],$row['role'],"PE",$wid,'');	
	}
} else {

	$result = dbQuery('SELECT uid, name, role FROM users WHERE uid = '.dbMakeSafe($uid).';');
	if(mysql_num_rows($result) != 0) {
		$row=mysql_fetch_assoc($result);
		dbQuery('UPDATE users SET time = NOW() , private = 0 WHERE uid = '.dbMakeSafe($uid).';');
		dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
				dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
				', "PX" ,'.dbMakeSafe($rid).');');	
		include_once('send.php');
        send_to_all(mysql_insert_id(),$uid, $row['name'],$row['role'],"PX",$rid,'');	
	}
}
mysql_free_result($result);
echo '{"Status":"OK"}';
?> 
