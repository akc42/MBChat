<?php
/*
 	Copyright (c) 2009,2010 Alan Chandler
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

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: -1"); // Date in the past
if(!(isset($_POST['user']) && isset($_POST['password']) ))
	die('Poll-Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$lid = $_POST['lid'];


$readpipe=fopen("./data/msg".$uid,'r');
while (!feof($readpipe)) {
    $response .= fread($readpipe,8192);
}
fclose($readpipe);

if (strlen($response) > 0 ) {
    echo '{"messages":[' ;
    $messages =  explode('>',$response);
    foreach($messages as $message) {
            echo substr($message,1) ;
    }
    echo ']}';
} else {
 echo '{"status":"time"}';
}
?>
