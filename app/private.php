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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['wid']) && isset($_POST['rid'])))
	die('Private - Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Private - Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));

$wid = $_POST['wid'];
$rid = $_POST['rid'];

define ('MBC',1);   //defined so we can control access to some of the files.
include_once('db.php');


if ($wid != 0 ) {
	$result = dbQuery('SELECT participant.uid, users.name, role, wid  FROM participant 
				JOIN users ON users.uid = participant.uid WHERE participant.uid = '.
				dbMakeSafe($uid).' AND wid = '.dbMakeSafe($wid).' ;');

	if($row = dbFetch($result)) {
		dbQuery('UPDATE users SET time = '.time().' , private = '.dbMakeSafe($wid).' WHERE uid = '.dbMakeSafe($uid).';');
		dbQuery('INSERT INTO log (uid, name, role, type, rid ) VALUES ('.
				dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
				', "PE" ,'.dbMakeSafe($wid).');');
		include_once('send.php');
        send_to_all(dbLastId(),$uid, $row['name'],$row['role'],"PE",$wid,'');	
	}
} else {

	$result = dbQuery('SELECT uid, name, role FROM users WHERE uid = '.dbMakeSafe($uid).';');
	if($row = dbFetch($result)) {
		dbQuery('UPDATE users SET time = '.time().' , private = 0 WHERE uid = '.dbMakeSafe($uid).';');
		dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
				dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
				', "PX" ,'.dbMakeSafe($rid).');');	
		include_once('send.php');
        send_to_all(dbLastId(),$uid, $row['name'],$row['role'],"PX",$rid,'');	
	}
}
dbFree($result);
echo '{"Status":"OK"}';
?> 
