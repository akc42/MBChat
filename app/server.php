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



//This is a convenient place to force everything we output to not be cached 
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

define('SERVER_LOCK','./data/server.lck');
define('SERVER_SOCKET','./data/message.sock');


function chat_cmd($cmd) {
    if(!$socket = socket_create(AF_UNIX,SOCK_STREAM,0)) 
            die("Failed to get a socket, Error =".socket_strerror(socket_last_error()));
    do {
        $handle = fopen(SERVER_LOCK,'r+');
        flock($handle,LOCK_EX);
        if(trim(fread($handle,50)) != 'server') {
            fwrite($handle,'server');
            switch($pid = pcntl_fork()) {
            case -1:
                fseek($handle,0);
                fwrite($handle,'noexist');
                flock($handle, LOCK_UN);
                fclose($handle);
                die("Cannot start Server");
            case 0:
                //I am the child
                fclose($handle);  
                ob_end_clean(); // Discard the output buffer and close

                fclose(STDIN);  // Close all of the standard
                fclose(STDOUT); // file descriptors as we
                fclose(STDERR); // are running as a daemon.

                require_once('./server.inc'); //this does it all
                $handle = fopen(SERVER_LOCK,'r+');
                flock($handle,LOCK_EX);
                socket_shutdown($socket,1);  //stop any writing to the socket
                socket_close(); //this will block until reading has finished               
                fwrite($handle,'noexist');
                flock($handle, LOCK_UN);
                fclose($handle);
                exit();
            default:
            }
        } 
        flock($handle, LOCK_UN);
        fclose($handle);
        if(!$socket = socket_create(AF_UNIX,SOCK_STREAM,SOL_TCP)) 
            die("Failed to get a socket, Error =".socket_strerror(socket_last_error()));
        if(!socket_connect($socket,SERVER_SOCKET)) return false; //most likely problem is we have to much queued.  This allows this to report back
//TODO change following from $_GET to $_POST
        $message = '{"cmd":"'.$cmd.'","params":'.json_encode($_GET).'}';
echo $message."<br/>\n"; //TODO
    } while (!socket_send($socket,$message,strlen($message),MSG_EOF)); //could be that server decides to shut down just after we last checked
    $read = '';
    while($flag = socket_recv($socket,$buf,1024,MSG_WAITALL)) {
        $read .= $buf;
    }
    socket_close($socket);
    if($flag === false) return false;
    return $read;
}

//TODO change following from $_GET to $_POST
$command = $_GET['cmd'];
unset($_GET['cmd']);
$reply = chat_cmd($cmd);
if($reply === false) {
    echo "Problem with command $command \n";
} else {
    echo $reply;
}




