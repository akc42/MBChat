<?php
if(!(isset($_GET['user']) && isset($_GET['password']) ))
	die('{"error" : "Hacking attempt - wrong parameters" }');
$uid = $_GET['user'];
if ($_GET['password'] != sha1("Key".$uid))
	die('{"error" :"Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid).'"}');


define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

	dbQuery('START TRANSACTION ;');
	$result=dbQuery('SELECT uid, name, role, rid FROM users WHERE uid = '.dbMakeSafe($uid).';');
	if(mysql_num_rows($result) != 0) {
		$row=mysql_fetch_assoc($result);
		dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
				dbMakeSafe($row['uid']).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
				', "LO" ,'.dbMakeSafe($row['rid']).');');
$lid = mysql_insert_id();
		dbQuery('DELETE FROM users WHERE uid = '.dbMakeSafe($row['uid']).' ;');
		
	};
	mysql_free_result($result);
	dbQuery('COMMIT');


echo '{"lastid" : '.$lid.'}' ;
?> 
