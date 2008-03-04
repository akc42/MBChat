<?php
if(!(isset($_GET['user']) && isset($_GET['password']) ))
	die("Hacking attempt - wrong parameters");
$uid = $_GET['user'];


define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

	dbQuery('START TRANSACTION ;');
	$result=dbQuery('SELECT uid, name, title, role, rid FROM users WHERE id = '.dbMakeSafe($uid).';');
	if(mysql_num_rows($result) != 0) {
		$row=mysql_fetch_assoc($result);
		dbQuery('INSERT INTO log (uid, name, title, role, type, rid) VALUES ('.
				dbMakeSafe($row['uid']).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['title']).','.dbMakeSafe($row['role']).
				', "LO" ,'.dbMakeSafe($row['rid']).');');
		dbQuery('DELETE FROM users WHERE uid = '.dbMakeSafe($row['uid']).' ;');
		
	};
	mysql_free_result($result);
//Delete any whisper channels where there is only one (or less) participant(s)
	dbQuery('DELETE FROM whisper WHERE wid NOT IN (SELECT wid FROM participant GROUP BY wid HAVING count(*) > 1);'); 
	dbQuery('COMMIT');


?> 
		


?> 
