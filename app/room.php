<?php
/*
 	Copyright (c) 2009 Alan Chandler
    This file is part of MBChat.

    MBChat is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    MBChat is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with MBChat (file COPYING.txt).  If not, see <http://www.gnu.org/licenses/>.
*/
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['rid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$rid = $_POST['rid'];

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');


$messages = array();

$result = dbQuery('SELECT rid, name, type FROM rooms WHERE rid = '.dbMakeSafe($rid).';');
if($room = dbFetch($result)) {
	dbFree($result);
	$result = dbQuery('SELECT uid, name, role, moderator,question FROM users WHERE uid = '.dbMakeSafe($uid).';');
	if($user = dbFetch($result)) {
		if ($room['type'] == 'M'  && $user['moderator'] != 'N') {
		//This is a moderated room, and this person is not normal - so swap them into moderated room role
			$role = $user['moderator'];
			$mod = $user['role'];
		} else {
			$role = $user['role'];
			$mod = $user['moderator'];
		}
		dbFree($result);
        $params = Array();
        foreach(dbQuery("SELECT name, value FROM parameters WHERE name = 'max_time' OR name = 'max_messages' ;") as $row) {
            $params[$row['name']] = $row['value'];
        }
		
		dbQuery('UPDATE users SET rid = '.dbMakeSafe($rid).', time = '.time().', role = '.dbMakeSafe($role)
					.', moderator = '.dbMakeSafe($mod).' WHERE uid = '.dbMakeSafe($uid).';');
		dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
						dbMakeSafe($user['uid']).','.dbMakeSafe($user['name']).','.dbMakeSafe($role).
						', "RE" ,'.dbMakeSafe($rid).','.dbMakeSafe($user['question']).');');
		$didre = true;
		$name = $user['name'];
		$question = $user['question'];
        
		$sql = 'SELECT lid, time, type, rid, log.uid AS uid , name, role, text  FROM log';
		$sql .= ' LEFT JOIN participant ON participant.wid = rid WHERE ( (participant.uid = '.dbMakeSafe($uid).' AND type = "WH" )' ;
		$sql .= ' OR rid = '.dbMakeSafe($rid).') AND log.time > '.(time() - 3600*$params['max_time']);
		$sql .= ' ORDER BY lid DESC LIMIT '.$params['max_messages'].';';
		$first = true;
		foreach(dbQuery($sql) as $row) {
		    if($first) {
		        $lid = $row['lid'];
		        $first = false;
		    }
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
		};
		echo '{ "room" :'.json_encode($room).', "messages" :'.json_encode(array_reverse($messages)).', "lastid" :'.$lid.'}';
        include_once('send.php');
        send_to_all($lid,$uid, $name,$role,"RE",$rid,$question);	
	}	
}
?>
