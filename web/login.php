<?php
if(!(isset($_POST['user']) && isset($_POST['password']) ))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));

define('MBCHAT_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/');

$txt = 'MBchat version - '.$_POST['mbchat'].', Mootools_Version - '.$_POST['version'].' - build - '.$_POST['build'] ;
$txt .=' Browser - '.$_POST['browser'].' on Platform - '.$_POST['platform'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

$result=dbQuery('SELECT uid, name, role, rid FROM users WHERE uid = '.dbMakeSafe($uid).';');
if(mysql_num_rows($result) != 0) {
	$row=mysql_fetch_assoc($result);
	dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
			dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
			', "LI" ,'.dbMakeSafe($row['rid']).','.dbMakeSafe($txt).');');
    $lid = mysql_insert_id();
    
//If FIFO doesn't exists (when trying to login after timeout for instance) create it
	if(!file_exists(MBCHAT_PATH."pipes/msg".$uid)) {
	    $old_umask = umask(0007);
    	posix_mkfifo(MBCHAT_PATH."pipes/msg".$uid,0660);
        umask($old_umask);
    }
	
	include_once('send.php');
    send_to_all($lid,$uid, $row['name'],$row['role'],"LI",$row['rid'],'');	
		
};
mysql_free_result($result);

echo '{"Login" : '.$uid.', "lastid" : '.$lid.' }' ;
?> 
