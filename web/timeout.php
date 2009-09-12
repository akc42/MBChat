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

define('MBCHAT_TIMEOUT_USER',	3); //No of minutes before online user goes offline through lack of activity

$result=dbQuery('SELECT uid, name, role, rid FROM users WHERE NOW() > DATE_ADD(time, INTERVAL '.MBCHAT_TIMEOUT_USER.' MINUTE);');
if(mysql_num_rows($result) != 0) {
	while($row=mysql_fetch_assoc($result)) {
		dbQuery('DELETE FROM users WHERE uid = '.dbMakeSafe($row['uid']).' ;');
		dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
			dbMakeSafe($row['uid']).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
			', "LT" ,'.dbMakeSafe($row['rid']).');');
		
		include_once('send.php');
        send_to_all(mysql_insert_id(),$row['uid'], $row['name'],$row['role'],"LT",$row['rid'],'');	

        unlink(MBCHAT_PIPE_PATH."msg".$row['uid']); //Loose FIFO

        
	}
};
mysql_free_result($result);
?>
