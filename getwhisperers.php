<?php
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['wid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$wid = $_POST['wid'];

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