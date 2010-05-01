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

error_reporting(E_ALL);


require_once('../inc/client.inc');

cs_start_server();
$auth = cs_query('auth');
function get_password($username) {
    global $auth,$db;
    if(substr($username,0,3) == '$$$') {
        //An externally authorised client with where we checked the password already

        return md5($username.":".$auth['realm'].":".$auth['remote_key'].substr($username,3)); 
    }
    $db = new PDO('sqlite:../data/users.db');
    $db->exec("DELETE FROM users WHERE time < ".(time() - $auth['purge_message_interval']*86400)." AND isguest = 1"); //purge old guests
    if (substr($username,0,3) == '$$G') {
        //A guest
        return md5($username.":".$auth['realm'].":guest");
    }
    $result = $db->query("SELECT password FROM users WHERE isguest = 0 AND name = '$username'");
    $pass = $result->fetchColumn();
    $result->closeCursor();
    return $pass; 
}

if(!$header = d_get_header()) {
    d_send($auth['realm'],'login/');
    exit;
}
if(!$username = d_authenticate($header,'get_password')) {
    if(is_null($username)) {
        d_send_bad_request(); //this will let us know we made an error with the password
        exit;
    } else {
        d_refresh($auth['realm'],'login/');
        exit;
    }
}
?><p>Probe</p>



