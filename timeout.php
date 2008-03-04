<?php
	if (!defined('MBC'))
		die('Hacking attempt...');

	define('MBCHAT_TIMEOUT_USER',	3); //No of minutes before online user goes offline through lack of activity

	dbQuery('START TRANSACTION ;');
	$result=dbQuery('SELECT uid, name, title, role, rid FROM users WHERE NOW() > DATE_ADD(time, INTERVAL '.MBCHAT_TIMEOUT_USER.' MINUTE);');
	if(mysql_num_rows($result) != 0) {
		while($row=mysql_fetch_assoc($result)) {
			dbQuery('INSERT INTO messages (uid, name, title, role, type, rid) VALUES ('.
				dbMakeSafe($row['uid']).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['title']).','.dbMakeSafe($row['role']).
				', "LT" ,'.dbMakeSafe($row['rid']).');');
			dbQuery('DELETE FROM users WHERE uid = '.dbMakeSafe($row['uid']).' ;');
		}
	};
	mysql_free_result($result);
//Delete any whisper channels where there is only one (or less) participant(s)
	dbQuery('DELETE FROM whisper WHERE wid NOT IN (SELECT wid FROM participant GROUP BY wid HAVING count(*) > 1);'); 
	dbQuery('COMMIT');

?> 
