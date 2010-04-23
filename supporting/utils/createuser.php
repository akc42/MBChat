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
define('DATABASE',DATA_DIR.'chat.db');

error_reporting(E_ALL);

$db = new SQLite3(DATABASE); 

$no = $db->querySingle("SELECT count(*) FROM users WHERE name = '".$_POST['username']."'");

if($no == 0) {
    $db->exec("INSERT INTO users(name,permanent,groups) VALUES('".$_POST['username']."','".md5($_POST['password'])."','12')");
    echo "INSERTED new user with UID = ".$db->lastInsertRowID()."<br/>\n";
} else {
    $db->exec("UPDATE users SET permanent = '".md5($_POST['password'])."' WHERE uid = $no");
    echo "UPDATED user with UID = $no<br/>\n";
}

