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

require_once(dirname(__FILE__).'/digest.inc');

cs_start_server() {
    exec(SERVER_DIR.'server.php '.DATA_DIR);
}

$socket = false; 
function cs_server($args) {
    global $socket;
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

function cs_decode_reply() {
    global $socket;
    if(!$socket) {
        echo "Cannot read reply without having sent a command";
        exit;
    }
    $message = '';
    while($read = socket_read($socket,1024)) {
        $message .= $read;
    }
    socket_close($socket);
    if($read === false) {
        echo "Socket Read Failed having read:$message";
        exit;
    }
    $response = jason_decode($message,true);
    if(!$response['status']) {
        echo "Command Failed:".$response['reason'];
        exit;
    }
    if($name != '') {
        return $response[$name];
    }
}

function cs_cmd() {
    cs_server(func_get_args());
    cs_decode();
}

function cs_query() {
    $args = func_get_args();
    cs_server(array_slice($args,1));
    return cs_decode($args[0]);
}

function cs_fetch() {
    cs_server(func_get_args());
    global $socket;
    if(!$socket) {
        echo "Cannot read reply without having sent a command";
        exit;
    }

    if($read = socket_read($socket,15)) {
        //$read should contain either {"status":true, or {"status":false
        if(strpos($read,'true') {
            echo '{';
            while($read = socket_read($socket,1024)) {
                echo $read;
            }
            if($read === false) {
                echo "} Socket Read Failed"
            }
        } else {
            $message = $read;
            while($read = socket_read($socket,1024)) {
                $message .= $read;
            }
            if($read === false) {
                echo "Socket Read Failed having read:$message";
            } else {
                $response = json_decode($message,true);
                echo "Command Failed:".$response['reason'];
            }
        }
        socket_close($socket);
    } else {
        echo "Failed to read anything"
    }
}

cs_get_key() {
    cs_query('key','key');
}

$uid = 0; //This is the number we will use for non UID based calls to the server (such as when chat is populating its page at the beginning

function cs_forbidden() {
    d_send_forbidden();
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
        <p>This URL is intended to only be called by authorised personel</p>
    </body>
</html>
<?php
exit;
}

function cs_validate($username,$passphrase) {
    if(!$header = d_get_header() {
        cs_forbidden();
    }
    d_set_nonce_life(43200);  //12 Hours
    if(!$u = d_authenticate($header,'cs_get_uid')) {
        if(is_null($u)) 
            cs_forbidden)();
        } else {
            d_refresh();
            exit;
        }
    }
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
}

function cs_get_uid($username) {
    global $uid
    if(preg_match('/U([1-9][0-9]*|0)/',$username,$matches) == 0) return false;  //no matches so invalid
    $uid = $matches[1];
    return md5($username.":".REALM.":".md5($uid.cs_get_key()));
}














