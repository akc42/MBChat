<?php
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['wid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$wid = $_POST['wid'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');


$result = dbQuery('SELECT users.uid, name, role, wid FROM users JOIN participant ON users.uid = participant.uid 
		WHERE users.uid = '.dbMakeSafe($uid).' AND wid = '.dbMakeSafe($wid).' ;');
if(mysql_num_rows($result) > 0) {
//Can only delete it if was still there
	$row = mysql_fetch_assoc($result);
	dbQuery('DELETE FROM participant WHERE uid = '.dbmakeSafe($uid).' AND wid = '.dbMakeSafe($wid).' ;');
	dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
		dbMakeSafe($uid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
		', "WL" ,'.dbMakeSafe($wid).');');

}
mysql_free_result($result);

echo '{ "Status" : "OK"}';

?>