<?php
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['wuid']) && isset($_POST['wid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$wuid = $_POST['wuid'];
$wid = $_POST['wid'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');
//Check I am in this whisper group and therefore can add the new person
$result = dbQuery('SELECT * FROM participant WHERE uid = '.dbMakeSafe($uid).' AND wid = '.dbMakeSafe($wid).' ;');
if(mysql_num_rows($result) != 0) {
	mysql_free_result($result);
	$result = dbQuery('SELECT  uid, name, role FROM users WHERE uid = '.dbMakeSafe($wuid).' ;');
	if(mysql_num_rows($result) != 0) {
		$row = mysql_fetch_assoc($result);
		dbQuery('INSERT INTO participant SET wid = '.dbMakeSafe($wid).', uid = '.dbmakeSafe($wuid).' ;');
		dbQuery('INSERT INTO log (uid, name, role, type, rid) VALUES ('.
			dbMakeSafe($wuid).','.dbMakeSafe($row['name']).','.dbMakeSafe($row['role']).
			', "WJ" ,'.dbMakeSafe($wid).');');
	    include_once('send.php');
        send_to_all(mysql_insert_id(),$wuid, $row['name'],$row['role'],"WJ",$wid,'');	
	}
	mysql_free_result($result);
	
}

echo '{ "Status" : "OK"}';

?>
