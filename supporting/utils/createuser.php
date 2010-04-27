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
define('DATA_DIR','/home/alan/dev/chat/data/');  //Should be outside of web space
define('DATABASE',DATA_DIR.'users.db');
define('REALM','chat@hartley-consultants.com');

error_reporting(E_ALL);
if(!file_exists(DATABASE) ) {
    $db = new SQLite3(DATABASE);
    $db->exec(file_get_contents('./users.sql'));
} else {
    $db = new SQLite3(DATABASE);
}

$username = $_POST['username'];
$password = md5($username.":".REALM.":".$_POST['password']);
echo "$password<br/>\n";

$no = $db->querySingle("SELECT count(*) FROM users WHERE name = '$username'");

if($no == 0) {
    $db->exec("INSERT INTO users(name,password,capability) VALUES('$username','$password','".$_POST['capabilities']."')");
    echo "INSERTED new user with UID = ".$db->lastInsertRowID()."<br/>\n";
} else {
    $no = $db->querySingle("SELECT uid FROM users WHERE name  = '$username'");
    $db->exec("UPDATE users SET ".(($_POST['password'] != '')?"password = '$password',":"")." capability = '".$_POST['capabilities']."' WHERE uid = $no");
    echo "UPDATED user with UID = $no<br/>\n";
}

