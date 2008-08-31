<?php
if(!(isset($_GET['user']) && isset($_GET['password']) ))
	die('Hacking attempt - wrong parameters');
$uid = $_GET['user'];
if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));

$txt = 'MBchat version - '.$_GET['mbchat'].', Mootools_Version - '.$_GET['version'].' - build - '.$_GET['build'] ;
$txt .=' Browser - '.$_GET['browser'].' on Platform - '.$_GET['platform'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

$result=dbQuery('SELECT uid, name, role, rid FROM users WHERE uid = '.dbMakeSafe($uid).';');
if(mysql_num_rows($result) != 0) {
	$row=mysql_fetch_assoc($result);
	dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
			dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
			', "LI" ,'.dbMakeSafe($row['rid']).','.dbMakeSafe($txt).');');
$lid = mysql_insert_id();
		
};
mysql_free_result($result);

echo '{"Login" : '.$uid.', "lastid" : '.$lid.' }' ;
?> 
