<?php
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['rid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$rid = $_POST['rid'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

$result = dbQuery('SELECT uid, name, role, rid, moderator FROM users WHERE uid = '.dbMakeSafe($uid).';');
if(mysql_num_rows($result) != 0) {
	$user = mysql_fetch_assoc($result);
	mysql_free_result($result);
	
	if ($user['role'] == 'M' && $user['rid'] == $rid ) {

		dbQuery('UPDATE users SET role = '.dbMakeSafe($user['moderator']).
			', moderator = "N", time = NOW() WHERE uid = '.dbMakeSafe($uid).';');

		dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
				dbMakeSafe($uid).','.dbMakeSafe($user['name']).', '.
				dbMakeSafe($user['moderator']).', "RN" ,'.
				dbMakeSafe($rid).');');
		include_once('send.php');
        send_to_all(mysql_insert_id(),$uid, $user['name'],$user['moderator'],"RN",$rid,"");	

	}
}
?>