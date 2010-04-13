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
if(!(isset($_POST['user']) && isset($_POST['password'])))
	die('Presence-Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');
dbBegin();
dbQuery('UPDATE users SET time = '.time().' WHERE uid ='.dbMakeSafe($uid).';');  //Mark me as being active
$result = dbQuery("SELECT value FROM parameters WHERE name = 'user_timeout' ;");
$row = dbFetch($result);
$usertimeout = $row['value'];
dbFree($result);
$result = dbQuery("SELECT value FROM parameters WHERE name = 'wakeup_interval' ;");
$row = dbFetch($result);
$wakeup = $row['value'];
dbFree($result);

include('timeout.php');		//Timeout inactive users 
dbCommit();
/*
If no one has been sent any messages in the last period then send a null message to wake them up - just to ensure timeouts
do not kick in
*/

if(file_get_contents('./data/time.txt') + $wakeup < time()) {
    if ($dh = opendir('./data/')) {
        while (($file = readdir($dh)) !== false) {
            if (filetype('./data/'.$file) == 'fifo') {
                $writer=fopen ('./data/'.$file,'r+');
                fclose($writer);  //should cause an immediate EOF ON EVERY READ
            }
        }
        closedir($dh);
    }
    file_put_contents('./data/time.txt', ''.time());
}


echo '{"Status" : "OK"}' ;
?>
