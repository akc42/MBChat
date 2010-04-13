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
function head_content() {
?><title>Melinda's Backups Chat - Sign In Page</title>
<?php
}

function content() {
?>
<h1>MB Chat Login</h1>
<p>This is a dummy front end to MB Chat.  You can enter a username with which you will be known in chat.  You only need to enter a 
password <strong>if you are already registered as a user</strong> as this will be used to check your credentials in the database.  Guest
users should just enter the name they wish to be known as in chat</p>
<form action="signin.php" method="post">
    <table>
        <tr><td>Username:</td><td><input type="text" name="username" value="" /></td></tr>
        <tr><td>Password:</td><td><input type="password" name="password" value="" /></td></tr>
        <tr><td><input type="submit" name="submit" value="Sign In"/></td><td>Try "lite" version: <input type="checkbox" name="lite" /></td></tr>
    </table>
<form>
<?php
}


function menu_items() {
//Noop
}

include("./template/template.php");

?>
