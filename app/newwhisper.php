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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['wuid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$wuid = $_POST['wuid'];

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

$wid = 0;
$result = dbQuery('SELECT uid, name, role, moderator FROM users WHERE uid = '.dbMakeSafe($uid).' OR uid = '.dbMakeSafe($wuid).';');
if($user = dbFetch($result)) {
	if($wuser = dbFetch($result)) {
    	if ($user['uid'] != $uid ) {
    	    $t = $user;
    	    $user = $wuser;
    	    $wuser = $t;
    	}
	    dbFree($result);
	    dbBegin();
	    dbQuery('UPDATE wid_sequence SET value = value + 1 ;');
	    $result = dbQuery('SELECT value FROM wid_sequence');
	    $row=dbFetch($result);
	    $wid = $row['value'];
	    dbFree($result);
	    dbQuery('INSERT INTO participant (wid,uid) values('.$wid.','.dbmakeSafe($wuid).') ;');
	    dbQuery('INSERT INTO participant (wid,uid) values('.$wid.','.dbmakeSafe($uid).') ;');
        dbCommit();
	    include_once('send.php');
        send_to_all($wuid, $wuser['name'],$wuser,"WJ",$wid,'');	
	    send_to_all($uid, $user['name'],$user['role'],"WJ",$wid,'');	
    }
}	
echo '{ "wid" :'.$wid.', "user" :'.json_encode($wuser).'}';
?>
