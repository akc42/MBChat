<?php
/*
 	Copyright (c) 2009,2010 Alan Chandler
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

define('MBCHAT_MAX_TIME',	3);		//Max hours of message to display in a room
define('MBCHAT_MAX_MESSAGES',	100);		//Max message to display in room initially

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');


if ($rid != 0) {
	$result = dbQuery('SELECT rid, name, type FROM rooms WHERE rid = '.dbMakeSafe($rid).';');
	if ($room = dbFetch($result) ) {
    	dbFree($result);


	    $result = dbQuery('SELECT uid, name, role, moderator FROM users WHERE uid = '.dbMakeSafe($uid).';');
        if($user = dbFetch($result)) {
    	    dbFree($result);
	
	        if ($room['type'] == 'M'  && $user['moderator'] != 'N') {
	        //This is a moderated room, and this person is not normal - so swap them out of moderated room role
		        $role = $user['moderator'];
		        $mod = $user['role'];
	        } else {
		        $role = $user['role'];
		        $mod = $user['moderator'];
	        }
	
	        dbQuery('UPDATE users SET rid = 0, time = '.time().', role = '.dbMakeSafe($role)
				        .', moderator = '.dbMakeSafe($mod).' WHERE uid = '.dbMakeSafe($uid).';');
	
	
	        include_once('./send.php');
            send_to_all($uid, $user['name'],$role,"RX",$rid,'');	
        }
    }
}

$params = Array();
foreach(dbQuery("SELECT name, value FROM parameters WHERE name = 'max_time' OR name = 'max_messages' ;") as $row) {
    $params[$row['name']] = $row['value'];
}


//should only return the whispers
$sql = 'SELECT lid, time AS time, type, rid, log.uid AS uid , name, role, text  FROM log';
$sql .= ' LEFT JOIN participant ON participant.wid = rid WHERE participant.uid = '.dbMakeSafe($uid) ;
$sql .= ' AND type = "WH" AND log.time > '.(time() - 3600*$params['max_time']);
$sql .= ' ORDER BY lid DESC LIMIT '.$params['max_messages'].';';
$result = dbQuery($sql);
$messages = array();
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
echo '{"messages" :'.json_encode(array_reverse($messages)).', "lastid" :'.$lid.'}';
?>
