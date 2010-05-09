<?php
/*
 	Copyright (c) 2010 Alan Chandler
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
//The following 2 keys need to be adjusted for each installation.  

define('SERVER_DIR','/home/alan/dev/chat/server/');//Should be outside of web space
define('DATA_DIR','/home/alan/dev/chat/data/');  //Should be outside of web space

define('SERVER_SOCKET',DATA_DIR.'message.sock');

$uid = 0; //This is the number we will use for non UID based calls to the server (such as when chat is populating its page at the beginning


function cs_start_server() {
    exec(SERVER_DIR.'server.php '.DATA_DIR);
}

$socket = false; 
function cs_server($args) {
    global $socket,$uid;
    if(!$socket = socket_create(AF_UNIX,SOCK_STREAM,0)) {
        echo "Failed to get a socket, Error =".socket_strerror(socket_last_error());
        exit;
    }
    if(!@socket_connect($socket,SERVER_SOCKET)) {
         echo "Socket Error = ".socket_strerror(socket_last_error());
         exit;
    }
    $message = '{"cmd":"'.$args[0].'","uid":'.$uid.',"params":'.json_encode(array_slice($args,1))."}\n";
    socket_write($socket,$message);
}

function cs_query() {
    global $socket;
    cs_server(func_get_args());
    if(!$socket) {
        echo "Cannot read reply without having sent a command";
        exit;
    }

    //$read should contain either {"status":true, or {"status":false
    $message = '';
    while($read = socket_read($socket,1024)) {
        $message .= $read;
    }
    if($read === false) {
        echo "$message: Socket Read Failed";
    }
    return json_decode($message,true);
    socket_close($socket);
}

function cs_fetch() {
    global $socket;
    cs_server(func_get_args());
    if(!$socket) {
        echo "Cannot read reply without having sent a command";
        exit;
    }
    while($read = socket_read($socket,1024)) {
        echo $read;
    }
    if($read === false) {
        echo "Socket Read Failed having read:$message";
    }
    socket_close($socket);
}



function cs_forbidden() {
    header('HTTP/1.0 403 Forbidden');
?><html>
    <head>
        <style type="text/css">
            body {
                font-family: Arial;
                color: #345;
            }
            h1 {
                border-bottom: 3px solid #345;
            }
            a {
                color: #666;
            }
        </style>
    </head>
    <body>
        <h1>Forbidden</h1>
        <p>This URL is intended to only be called by authorised applications</p>
    </body>
</html>
<?php
exit;
}

function cs_validate() {
    global $uid;
    if(!(isset($_REQUEST['uid']) && isset($_REQUEST['pass']))) cs_forbidden();
    $return = cs_query('auth');
    $key = $return['key'];
    if(md5("U".$_REQUEST['uid']."P".$key) != $_REQUEST['pass']) cs_forbidden();
    $uid = $_REQUEST['uid'];
}















