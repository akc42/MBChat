<?php
if(!(isset($_GET['user']) && isset($_GET['password']) && isset($_GET['wuid']) && isset($_GET['wid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_GET['user'];
if ($_GET['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_GET['password'].' expected: '.sha1("Key".$uid));
$wuid = $_GET['wuid'];
$wid = $_GET['wid'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');
dbQuery('START TRANSACTION;');
//Check I am in this whisper group and therefore can add the new person
$result = dbQuery('SELECT * FROM participant WHERE uid = '.dbMakeSafe($uid).' AND wid = '.dbMakeSafe($wid).' ;');
if(mysql_num_rows($result) == 0) {
	dbQuery('ROLLBACK;');
	die('Join Whisper - Invalid Parameters');
}
mysql_free_result($result);
$result = dbQuery('SELECT  uid, name, role FROM users WHERE uid = '.dbMakeSafe($wuid).' ;');
if(mysql_num_rows($result) == 0) {
	dbQuery('ROLLBACK;');
	die('Join Whisper - Invalid Parameters');
}
$row = mysql_fetch_assoc($result);
mysql_free_result($result);
dbQuery('INSERT INTO participant SET wid = '.dbMakeSafe($wid).', uid = '.dbmakeSafe($wuid).' ;');
dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
	dbMakeSafe($wuid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
	', "WJ" ,'.dbMakeSafe($wid).');');


dbQuery('UPDATE users SET time = NOW() WHERE uid = '.dbMakeSafe($uid).';');

dbQuery('COMMIT ;');


echo '{ "Status" : "OK"}';

?>