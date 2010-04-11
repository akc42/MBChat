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
if (!defined('MBC'))
	die('Hacking attempt...');

$timedout = time() - $usertimeout;
dbBegin();
foreach(dbQuery('SELECT uid, name, role, rid FROM users WHERE time < '.$timedout.' AND present = 1;') as $row) {
    if(is_null($row['permanent'])) {
    	dbQuery('DELETE FROM users WHERE uid = '.dbMakeSafe($row['uid']).' ;');
    } else {
        dbQuery('UPDATE users SET present = 0 WHERE uid = '.dbMakeSafe($row['uid']).' ;');
    }
	dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
			dbMakeSafe($row['uid']).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
			', "LT" ,'.dbMakeSafe($row['rid']).');');

    unlink("./data/msg".$row['uid']); //Loose FIFO
		
	include_once('send.php');
    send_to_all(dbLastId(),$row['uid'], $row['name'],$row['role'],"LT",$row['rid'],'');	

};
dbCommit();
?>
