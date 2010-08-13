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
//The following 5 keys need to be adjusted for each installation.  


define('GOOGLE_ACCOUNT','UA-6767400-1');

define('SERVER_DIR','/home/alan/dev/mb/com/chatserve/server/');//Should be outside of web space
define('DATA_DIR','/home/alan/dev/mb/com/chatserve/data/');  //Should be outside of web space

define('EXTERNAL_AUTHENTICATION',true);
define('EXTERNAL_AUTHENTICATOR','http://mb.home/chat/index.php'); //Only needed if EXTERNAL_AUTHENTICATION is true

define('SERVER_SOCKET',DATA_DIR.'message.sock');
define('MAX_CMD_LENGTH',2000);

$uid = 0; //This is the number we will use for non UID based calls to the server (such as when chat is populating its page at the beginning


function cs_is_server_running() {
    return file_exists(SERVER_SOCKET);
}

$socket = false; 
function cs_server($args) {
    global $socket,$uid;
    if(!file_exists(SERVER_SOCKET)) exec(SERVER_DIR.'chatter.php '.DATA_DIR); //start server if its not running
    if(!$socket = socket_create(AF_UNIX,SOCK_STREAM,0)) {
        echo "Failed to get a socket, Error =".socket_strerror(socket_last_error());
        exit;
    }
    if(!@socket_connect($socket,SERVER_SOCKET)) {
         echo "Socket Error = ".socket_strerror(socket_last_error());
         exit;
    }
    $message = '{"cmd":"'.$args[0].'","uid":'.$uid.',"params":'.json_encode(array_slice($args,1))."}\n";
    if(strlen($message) > MAX_CMD_LENGTH) {
        $socket = false;
    } else {
        socket_write($socket,$message);
    }
}

function cs_query() {
    global $socket;
    cs_server(func_get_args());
    if(!$socket) {
        echo "Cannot read reply without having sent a command";
        exit;
    }

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
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '<?php echo GOOGLE_ACCOUNT;?>']);
  _gaq.push(['_trackPageview']);
</script>        
<h1>Forbidden</h1>
        <p>This URL is intended to only be called by authorised applications</p>
<!-- Google Analytics Tracking Code -->
  <script type="text/javascript">
    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(ga);
    })();
  </script>

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

function cs_tcheck($key,$pass) {
    return ($pass == md5($key));
}
















