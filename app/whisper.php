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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['text']) && isset($_POST['wid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));

$wid = $_POST['wid'];
$text = htmlentities(stripslashes($_POST['text']),ENT_QUOTES);   // we need to get the text in an html pure form as possible

define ('MBC',1);   //defined so we can control access to some of the files.
include_once('db.php');
$result = dbQuery('SELECT participant.uid, users.name, role, wid  FROM participant LEFT JOIN users ON users.uid = participant.uid WHERE participant.uid = '
	.dbMakeSafe($uid).' AND wid = '.dbMakeSafe($wid).' ;');
if(mysql_num_rows($result) > 0) {  //only insert into channel if still there

	$row=mysql_fetch_assoc($result);

	dbQuery('UPDATE users SET time = NOW() WHERE uid = '.dbMakeSafe($uid).';');

	if ($text != '') {  //only insert non blank text - ignore other
		dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
			dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
			', "WH" ,'.dbMakeSafe($wid).','.dbMakeSafe($text).');');
		include_once('send.php');
        send_to_all(mysql_insert_id(),$uid, $row['name'],$role,"WH",$wid,$text);	

	}
}
mysql_free_result($result);
echo '{"Status":"OK"}';
?> 
