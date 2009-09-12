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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['rid']) && isset($_POST['start'])&& isset($_POST['end'])))
	die('Log - Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Log - Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$rid = $_POST['rid'];

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

echo '{"messages" :[' ;
$result = dbQuery('SELECT uid, name, role FROM users WHERE uid = '.dbMakeSafe($uid).';');
if(mysql_num_rows($result) != 0) {
	$user = mysql_fetch_assoc($result);
	mysql_free_result($result);
	
	dbQuery('UPDATE users SET time = NOW() WHERE uid = '.dbMakeSafe($uid).';');
	
	dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
					dbMakeSafe($uid).','.dbMakeSafe($user['name']).','.dbMakeSafe($user['role']).
					', "LH" ,'.dbMakeSafe($rid).');');
	include_once('send.php');
    send_to_all(mysql_insert_id(),$uid, $user['name'],$user['role'],"LH",$rid,'');	
	
	
	$sql = 'SELECT lid, UNIX_TIMESTAMP(time) AS utime, type, rid, uid , name, role, text  FROM log';
	$sql .= ' WHERE UNIX_TIMESTAMP(time) > '.dbMakeSafe($_POST['start']).' AND UNIX_TIMESTAMP(time) < '.dbMakeSafe($_POST['end']).' AND ';
	$sql .= 'rid = '.dbMakeSafe($rid).' ORDER BY lid ;';
	$result = dbQuery($sql);
	$i = 0 ;
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
			$item['time'] = $row['utime'];
			$item['message'] = $row['text'];
			if ($i != 0) {
				echo ',';
			}
			$i++ ;
			echo json_encode($item) ;
		}		
	};
}
mysql_free_result($result);

echo ']}';
?>
