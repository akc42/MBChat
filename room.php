<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['rid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_GET['user'];
if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));
$rid = $_GET['rid'];

define('MBCHAT_MAX_TIME',	3);		//Max hours of message to display in a room
define('MBCHAT_MAX_MESSAGES',	100);		//Max message to display in room initially

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');
$result = dbQuery('SELECT rid, name, type FROM rooms WHERE rid = '.dbMakeSafe($rid).';');
if(mysql_num_rows($result) != 0) {
	$room = mysql_fetch_assoc($result);
	mysql_free_result($result);
	$result = dbQuery('SELECT uid, name, role, moderator,question FROM users WHERE uid = '.dbMakeSafe($uid).';');
	if(mysql_num_rows($result) != 0) {
		$user = mysql_fetch_assoc($result);
		mysql_free_result($result);
		
		if ($room['type'] == 'M'  && $user['moderator'] != 'N') {
		//This is a moderated room, and this person is not normal - so swap them into moderated room role
			$role = $user['moderator'];
			$mod = $user['role'];
		} else {
			$role = $user['role'];
			$mod = $user['moderator'];
		}
		
		dbQuery('UPDATE users SET rid = '.dbMakeSafe($rid).', time = NOW(), role = '.dbMakeSafe($role)
					.', moderator = '.dbMakeSafe($mod).' WHERE uid = '.dbMakeSafe($uid).';');
		dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
						dbMakeSafe($user['uid']).','.dbMakeSafe($user['name']).','.dbMakeSafe($role).
						', "RE" ,'.dbMakeSafe($rid).','.dbMakeSafe($user['question']).');');
		$sql = 'SELECT lid, UNIX_TIMESTAMP(time) AS time, type, rid, log.uid AS uid , name, role, text  FROM log';
		$sql .= ' LEFT JOIN participant ON participant.wid = rid WHERE ( (participant.uid = '.dbMakeSafe($uid).' AND type = "WH" )' ;
		$sql .= 'OR rid = '.dbMakeSafe($rid).') AND NOW() < DATE_ADD(log.time, INTERVAL '.MBCHAT_MAX_TIME.' HOUR) ';
		$sql .= 'ORDER BY lid DESC LIMIT '.MBCHAT_MAX_MESSAGES.';';
		$result = dbQuery($sql);
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
		$result = dbQuery('SELECT max(lid) AS lid FROM log;');
		$row = mysql_fetch_assoc($result);
		mysql_free_result($result);
	}	
}
echo '{ "room" :'.json_encode($room).', "messages" :'.json_encode(array_reverse($messages)).', "lastid" :'.$row['lid'].'}';

?>