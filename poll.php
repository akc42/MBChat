<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['lid'])))
	die('{"error" : "Hacking attempt - wrong parameters"}');
$uid = $_GET['user'];

if ($_GET['password'] != sha1("Key".$uid))
	die('{"error" :"Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid).'"}');
$lid = $_GET['lid'];

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

if (isset($_GET['presence'])) {
	dbQuery('UPDATE users SET time = NOW() WHERE uid ='.dbMakeSafe($uid).';');  //Mark me as being active
	include('timeout.php');		//Timeout inactive users 
}

$sql = 'SELECT * FROM log WHERE lid > '.dbMakeSafe($lid).' AND ( uid = '.dbMakeSafe($uid) ;

if (isset($_GET['rid']) || isset($_GET['wids'] ) ) {
	$sql .= ' OR rid IN (' ;
	if(isset($_GET['rid'])) {
		$sql .= dbMakeSafe($_GET['rid']);
		if (isset($_GET['wids'])) {
			$sql .= ','.dbMakeSafe($_GET['wids']);
		}
	} else {
		$sql .= dbMakeSafe($_GET['wids']);
	}
	$sql .=') ';
}
$sql .= ' ) ;';
$result = dbQuery($sql);
$messages = array();
if(mysql_num_rows($result) != 0) {
	while($row=mysql_fetch_assoc($result)) {
		$message = array();
		$user = array();
		$item = array();
		$item['lid'] = $row['lid'];
		$item['type'] = $row['type'];
		$item['rid'] = $row['rid'];
		$user['uid'] = $row['uid'];
		$user['name'] = $row['name'];
		$user['title'] = $row['title'];
		$user['role'] = $row['role'];
		$item['user'] = $user;
		$message['time'] = $row['time'];
		$message['text'] = $row['text'];
		$item['message'] = $message;
		$messages[]= $item;
	}		
};
mysql_free_result($result);
echo '{"messages":'.json_encode($messages).'}';
?> 
