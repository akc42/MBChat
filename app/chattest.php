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

/* check we are called with all the right parameters.  If not, we need to call our initialisation routine */

if(!(isset($_POST['uid']) && isset($_POST['pass'])  && isset($_POST['name'])  && isset($_POST['mod']) && isset($_POST['role']) && isset($_POST['whi']) && isset($_POST['gp']) && isset($_POST['ctype']))) {
 header('Location: index.php');
 exit;
}
$uid = $_POST['uid'];
$password = $_POST['pass'];
if ($password != sha1("Key".$uid))
   die('Hacking attempt got: '.$password.' expected: '.sha1("Key".$uid));


error_reporting(E_ALL);

//Can't start if we haven't setup a database
if (!file_exists('./data/chat.db')) {
    try {
        $db = new PDO('sqlite:./data/chat.db');  //This will create it
        $db->exec(file_get_contents('./database.sql'));
    } catch (PDOException $e) {
        die('Database setup failed: ' . $e->getMessage());
    }
    unset($db); //I don't want problems since db.php uses it too.
    file_put_contents('./data/time.txt', ''.time()); //make a time file
}

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('./db.php');

/* Load up the parameters from the database so that they are conveniently accessible */

$params = Array();
foreach(dbQuery("SELECT * FROM parameters;") as $row) {
    $params[$row['name']] = $row['value'];
}

//Make a pipe for this user - but before doing so kill anyother using this userID.  We can only have one chat at once.
$old_umask = umask(0007);
if(file_exists("./data/msg".$uid)) {
// we have to kill other chat, in case it was stuck
    $sendpipe=fopen("./data/msg".$uid,'r+');
    fwrite($sendpipe,'<LX>');
    fclose($sendpipe);
// Now sleep long enough for the other instance to go away
    sleep(2);
}
posix_mkfifo("./data/msg".$uid,0660);
umask($old_umask);


$name=$_POST['name'];
$role=$_POST['role'];
$mod=$_POST['mod'];
$whi=$_POST['whi'];
$groups = explode(":",$_POST['gp']); //A ":" separated list of committees (groups) that the user belongs to.
$lite = ($_POST['ctype'] == 'lite'); //if we need the special lite version (provides accessibility for blind people)

dbBegin();
dbQuery('REPLACE INTO users (uid,name,role,moderator, present) VALUES ('.dbMakeSafe($uid).','.
				dbMakeSafe($name).','.dbMakeSafe($role).','.dbMakeSafe($mod).', 1) ; ') ; //The last one indicates that the user is present
				
	//purge messages that are too old from database
	dbQuery("DELETE FROM log WHERE time < ".(time() - $params['purge_message_interval']*86400).";");
//timeout any users that are too old
$usertimeout = $params['user_timeout'];
	include('./timeout.php');
dbCommit();
?>
