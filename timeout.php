<?php
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
	}
};
mysql_free_result($result);
?>