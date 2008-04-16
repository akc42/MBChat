<?php
if(!(isset($_GET['user']) && isset($_GET['password'])))
	die('Presence-Hacking attempt - wrong parameters');
$uid = $_GET['user'];

if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

dbQuery('UPDATE users SET time = NOW() WHERE uid ='.dbMakeSafe($uid).';');  //Mark me as being active
include('timeout.php');		//Timeout inactive users 
echo '{"Status" : "OK"}' ;
?>