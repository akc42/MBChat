<?php
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['wuid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$wuid = $_POST['wuid'];

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

$wid = 0;
$result = dbQuery('SELECT uid, name, role, moderator FROM users WHERE uid = '.dbMakeSafe($uid).' OR uid = '.dbMakeSafe($wuid).';');
if(mysql_num_rows($result) == 2) {
	$user = mysql_fetch_assoc($result);
	if ($user['uid'] == $uid ) {
		$wuser = mysql_fetch_assoc($result);
	} else {
		$wuser = $user;
		$user = mysql_fetch_assoc($result);
	}
	mysql_free_result($result);
	dbQuery('LOCK TABLE whisper WRITE;');
	$result = dbQuery('SELECT wid FROM whisper;');
	$whisper = mysql_fetch_assoc($result);
	mysql_free_result($result);
	$wid = $whisper['wid'];
	dbQuery('UPDATE whisper SET wid = '.($wid + 1).' ;');
	dbQuery('UNLOCK TABLES;');
	dbQuery('INSERT INTO participant SET wid = '.dbMakeSafe($wid).', uid = '.dbmakeSafe($wuid).' ;');
	dbQuery('INSERT INTO participant SET wid = '.dbMakeSafe($wid).', uid = '.dbmakeSafe($uid).' ;');
	dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
		dbMakeSafe($wuid).','.dbMakeSafe($wuser['name']).','.dbMakeSafe($wuser['role']).
		', "WJ" ,'.dbMakeSafe($wid).');');
	include_once('send.php');
    send_to_all(mysql_insert_id(),$wuid, $wuser['name'],$wuser,"WJ",$wid,'');	
	dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
		dbMakeSafe($uid).','.dbMakeSafe($user['name']).','.dbMakeSafe($user['role']).
		', "WJ" ,'.dbMakeSafe($wid).');');
	send_to_all(mysql_insert_id(),$uid, $user['name'],$user['role'],"WJ",$wid,'');	
}	



echo '{ "wid" :'.$wid.', "user" :'.json_encode($wuser).'}';

?>
