<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['rid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_GET['user'];

if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));
$rid = $_GET['rid'];

define ('MBC',1);   //defined so we can control access to some of the files.
include_once('db.php');


$result = dbQuery('SELECT uid, name, role, question,private FROM users WHERE rid = '.dbMakeSafe($rid).' ;');
$users = array();
if(mysql_num_rows($result) != 0) {
	while($row=mysql_fetch_assoc($result)) {
		$users[] = $row;
	}		
};
mysql_free_result($result);
$result = dbQuery('SELECT max(lid) AS lid FROM log;');
$row = mysql_fetch_assoc($result);
mysql_free_result($result);

echo '{ "lastid":'.$row['lid'].', "online":'.json_encode($users).'}';
?> 
