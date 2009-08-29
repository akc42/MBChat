<?php
if(!(isset($_POST['user']) && isset($_POST['password']) ))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));

$txt = 'MBchat version: '.$_POST['mbchat'].', Mootools Version : '.$_POST['version'].' build '.$_POST['build'] ;
$txt .=' Browser : '.$_POST['browser'].' on Platform : '.$_POST['platform'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

define('MBCHAT_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/');
unlink(MBCHAT_PATH."pipes/msg".$uid); //Loose FIFO

$result=dbQuery('SELECT uid, name, role, rid FROM users WHERE uid = '.dbMakeSafe($uid).';');
if(mysql_num_rows($result) != 0) {
	$row=mysql_fetch_assoc($result);
	dbQuery('DELETE FROM users WHERE uid = '.dbMakeSafe($uid).' ;');
	dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
			dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
			', "LO" ,'.dbMakeSafe($row['rid']).','.dbMakeSafe($txt).');');
	include_once('send.php');
    send_to_all(mysql_insert_id(),$uid, $row['name'],$row['role'],"LO",$row['rid'],'');	
		
};
mysql_free_result($result);

echo '{"Logout" : '.$txt.'}' ;
?> 
