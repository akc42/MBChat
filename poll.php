<?php
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['lid'])))
	die('Poll-Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$lid = $_POST['lid'];

define ('MBC',1);
require_once('db.php');
if(isset($_POST['presence'])) {
	dbQuery('UPDATE users SET time = NOW() WHERE uid ='.dbMakeSafe($uid).';'); 
	include('timeout.php');		//Timeout inactive users
}
$result = dbQuery('SELECT lid, UNIX_TIMESTAMP(time) AS time, type, rid, uid , name, role, text  FROM log WHERE lid > '.dbMakeSafe($lid).' ORDER BY lid;');
$messages = array();
if(mysql_num_rows($result) != 0) {
	while($row=mysql_fetch_assoc($result)) {
		$user = array();
		$item = array();
		$item['lid'] = $row['lid'];
		$item['type'] = $row['type'];
		$item['rid'] = $row['rid'];
		$user['uid'] = $row['uid'];
		$user['name'] = $row['name'];
		$user['role'] = $row['role'];
		$item['user'] = $user;
		$item['time'] = $row['time'];
		$item['message'] = $row['text'];
		$messages[]= $item;
	}		
};
mysql_free_result($result);
echo '{"messages":'.json_encode($messages).'}';
?>