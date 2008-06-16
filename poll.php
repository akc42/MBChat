<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['lid'])))
	die('Poll-Hacking attempt - wrong parameters');
$uid = $_GET['user'];

if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));
$lid = $_GET['lid'];

define ('MBC',1);
require_once('db.php');
if(isset($_GET['presence'])) {
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