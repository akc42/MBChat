<?php
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['puid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$puid = $_POST['puid'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

$result = dbQuery('SELECT uid, name, role, rid, moderator, question  FROM users WHERE uid = '.dbMakeSafe($puid).';');
if(mysql_num_rows($result) != 0) {
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
		include_once('send.php');
        send_to_all(mysql_insert_id(),$puid, $user['name'],"M","ME",$user['rid'],'');	
	}
}
?>
