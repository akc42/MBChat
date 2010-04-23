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
//The following 3 keys need to be adjusted for each installation.  
define('SERVER_KEY','hometest'); 
define('SERVER_DIR','/home/alan/dev/chat/server/');//Should be outside of web space
define('DATA_DIR','/home/alan/dev/chat/data/');  //Should be outside of web space


define('SERVER_NAME',DATA_DIR.'server.name');
define('SERVER_SOCKET',DATA_DIR.'message.sock');



if (!defined('MBC'))
	die('Hacking attempt...');


//This is a convenient place to force everything we output to not be cached 
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past


class ChatServer {

    private $socket;
    private $debug;
    private $error;
    function __construct($debug=false) {
        $this->debug = $debug;
    }    
    
    private function server($args) {
        if(!$this->socket = socket_create(AF_UNIX,SOCK_STREAM,0)) {
            echo "Failed to get a socket, Error =".socket_strerror(socket_last_error());
            sleep(1);
            exit;
        }
        if($this->debug) var_dump($args);
        $cmd = $args[0];
      
        if(!@socket_connect($this->socket,SERVER_SOCKET)) {
             $message = '{"status":false,"reason":"Socket Error = '.socket_strerror(socket_last_error()).'"}';
             sleep(2);
             $this->error = $message;
             return false;
        }
        $message = '{"cmd":"'.$cmd.'","params":'.json_encode(array_slice($args,1))."}\n";
        socket_write($this->socket,$message);
        return true;
    }

    private function decode() {
        $message = '';
        while($read = socket_read($this->socket,1024)) {
            $message .= $read;
        }
        if($this->debug) echo $message;
        socket_close($this->socket);
        if($read === false) die("Socket Read Failed\n");
        return json_decode($message,true);
    }

    function getParam($name) {
        if($this->server(Array('param',$name))){
            $reply = $this->decode();
            return $reply['value'];
        }
        return null;
    }

    function cmd() {
        if($this->server(func_get_args()) ){
            $reply = $this->decode();
            return $reply['status'];
        }
        return false;
    }

    function query() {
        if($this->server(func_get_args())){
            $reply = $this->decode();
            return $reply['rows'];
        }
        return $this->error;
    }

    function fetch() {
        if($this->server(func_get_args())){
            while($read = socket_read($this->socket,1024)) {
                echo $read;
            }
            socket_close($this->socket);
            if($read === false) die("Socket Read Failed\n");
        } else {
            echo $this->error;
        }
    }

    function start_server($key) {
        if(strstr(file_get_contents(SERVER_NAME),$key) !== false) {
            exec(SERVER_DIR.'server.php '.DATA_DIR);
        }
    }
            
}






