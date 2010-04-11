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

define ('MBC',1);   //defined so we can control access to some of the files.
include_once('db.php');

$users = array();
foreach(dbQuery('SELECT uid, name, role, question,private AS wid FROM users WHERE rid = '.dbMakeSafe($rid).' AND present = 1 ;') as $row) {
    $users[] = $row;
};

$result = dbQuery('SELECT max(lid) AS lid FROM log;');
$row = dbFetch($result);
echo '{ "lastid":'.$row['lid'].', "online":'.json_encode($users).'}';
dbFree($result);
?> 
