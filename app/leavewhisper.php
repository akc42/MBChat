<?php
/*
 	Copyright (c) 2009, 2010 Alan Chandler
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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['wid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$wid = $_POST['wid'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

dbBegin();
$result = dbQuery('SELECT users.uid, name, role, wid FROM users JOIN participant ON users.uid = participant.uid 
		WHERE users.uid = '.dbMakeSafe($uid).' AND wid = '.dbMakeSafe($wid).' ;');
if($row=dbFetch($result)) {
	dbQuery('DELETE FROM participant WHERE uid = '.dbmakeSafe($uid).' AND wid = '.dbMakeSafe($wid).' ;');
	include_once('./send.php');
    send_to_all($uid, $row['name'],$row['role'],"WL",$wid,'');	

}
dbFree($result);
dbCommit();
echo '{ "Status" : "OK"}';
?>
