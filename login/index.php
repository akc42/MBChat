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


define ('MBC',1);   //defined so we can control access to some of the files.
require_once('./client.php');

$c = new ChatServer();
$c->start_server(SERVER_KEY); //Start Server if not already going.

$template_url = $c->getParam('template_url');
$template = $c->getParam('template_dir');


function head_content() {
?><title>Melinda's Backups Chat - Sign In Page</title>
	<link rel="stylesheet" type="text/css" href="chat.css" title="mbstyle"/>
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="chat-ie.css"/>
	<![endif]-->
	<style type="text/css">
	    #content, #content td {
	        color:#ffffff;
	   }
	</style>
<?php
}

function content() {
?>
<div id="content">
<h1>MB Chat Login</h1>
<p>This is a dummy front end to MB Chat.  You can enter a username with which you will be known in chat.  You only need to enter a 
password <strong>if you are already registered as a user</strong> as this will be used to check your credentials in the database.  Guest
users should just enter the name they wish to be known as in chat</p>
<p>The accessibility version (see checkbox at bottom of form) is for users of the Jaws screen reading system for blind users.  This version removes
some of the graphics in exchange for an interface designed specifically to enable Jaws to provide access.</p>
<p></p>
<form action="signin.php" method="post">
    <table>
        <tr><td>Username:</td><td><input type="text" name="username" value="" /></td></tr>
        <tr><td>Password:</td><td><input type="password" name="password" value="" /></td></tr>
        <tr><td><input type="submit" name="submit" value="Sign In"/></td><td>Use "Accessibilty" version: <input type="checkbox" name="lite" /></td></tr>
    </table>
<form>
</div>
<?php
}


function menu_items() {
//Noop
}

include($template.'/template.php');

?>
