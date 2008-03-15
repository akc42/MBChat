<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['wid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_GET['user'];

if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));
$wid = $_GET['wid'];

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');




$result = dbQuery('SELECT users.uid,name,role, wid FROM participant JOIN users ON users.uid = participant.uid WHERE wid = '.dbMakeSafe($wid).';');
$whisperers = array();
if(mysql_num_rows($result) != 0) {
	while($row=mysql_fetch_assoc($result)) {
		$user = array();
		$user['uid'] = $row['uid'];
		$user['name'] = $row['name'];
		$user['role'] = $row['role'];
		$whisperers[]= $user;
	}		
};
mysql_free_result($result);
echo '{"whisperers":'.json_encode($whisperers).'}';
?>