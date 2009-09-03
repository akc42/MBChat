<?php
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['rid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$rid = $_POST['rid'];

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
