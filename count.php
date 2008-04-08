<?php
/* A new version of chat
	Copyright (c) 2008 Alan Chandler
	Licenced under the GPL
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