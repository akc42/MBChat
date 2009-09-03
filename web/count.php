<?php
/*
 	Copyright (c) 2008,2009 Alan Chandler
    This file is part of MBChat

    MBChat is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    MBChat is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with MBChat (file COPYING.txt in the "Supporting" directory).  If not, see <http://www.gnu.org/licenses/>.

*/

// Link to SMF forum as this is only for logged in members
// Show all errors:
error_reporting(E_ALL);
// Path to the chat directory:

define('MBCHAT_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/');


define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

// We want to show colours if Melinda or our Special Guests are in chat

$best = 'R';
$total = 0;
$result = dbQuery('SELECT role, count(*) as chatters, users.rid, rooms.type AS type FROM users LEFT JOIN rooms  ON rooms.rid = users.rid WHERE rooms.type IS null OR rooms.type != "C" GROUP BY role;');
if (mysql_num_rows($result) != 0) {		
	while ($row = mysql_fetch_assoc($result)) {
		$total += $row['chatters'];
		if ($row['role'] == 'H') {
			$best =  'H';
		} else if ($row['role'] == 'G' && $best == 'R' ) {
			$best = 'G' ;
		}
	}
}

mysql_free_result($result);

echo '{ "chatters" : '.$total.' , "best" : "'.$best.'" }' ;

?>
