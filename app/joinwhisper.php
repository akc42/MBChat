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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['wuid']) && isset($_POST['wid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$wuid = $_POST['wuid'];
$wid = $_POST['wid'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

//Check I am in this whisper group and therefore can add the new person
$result = dbQuery('SELECT count(*) as num FROM participant WHERE uid = '.dbMakeSafe($uid).' AND wid = '.dbMakeSafe($wid).' ;');
$row = dbFetch($result);
dbFree($result);
if($row['num'] != 0) {
	$result = dbQuery('SELECT  uid, name, role FROM users WHERE uid = '.dbMakeSafe($wuid).' ;');
	if($row = dbFetch($result)) {
		dbQuery('INSERT INTO participant SET wid = '.dbMakeSafe($wid).', uid = '.dbmakeSafe($wuid).' ;');
	    include_once('./send.php');
        send_to_all($wuid, $row['name'],$row['role'],"WJ",$wid,'');	
	}
	dbFree($result);
	
}
echo '{ "Status" : "OK"}';
?>
