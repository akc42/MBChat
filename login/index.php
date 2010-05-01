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
    cs_forbidden();
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
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

//We found a user (although of course it might just be a guest (or an externally authenticated user)

$return = Array();

if(substr($username,0,3) == '$$$') {
    $return['uid'] = substr($username,3);
    $return['name'] = $_POST['name'];
    $return['role'] = $_POST['role'];
    $return['mod'] = $_POST['mod'];
    $return['whi'] = ($_POST['whi'] == 'true');
    $return['cap'] = $_POST['cap'];
} elseif(substr($username,0,3) == '$$G') {
    $db->exec("INSERT INTO users (name,password,isGuest) VALUES ('$username','guest',1)");
    $return['uid'] = $db->lastInsertID();
    $return['name'] = substr($username,3).'_(G)';
    $return['role'] = 'B';
    $return['mod'] = 'N';
    $return['whi'] = true;
    $return['cap'] = '';
} else {
    $result = $db->query("SELECT * FROM users WHERE name = '$username'");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $result->closeCursor();
    $return['uid'] = $row['uid'];
    $return['name'] = $row['name'];
    $return['whi'] = true;
    $c = explode(":",$row['capability']);
    $return['role'] = (in_array('10',$c)?'A':(in_array('12',$c)?'L':(in_array('14',$c)?'G':(in_array('16',$c)?'H':'R'))));
    $return['mod'] = (in_array('2',$c)?'M':(in_array('3',$c)?'S':'N'));
    $return['cap'] = $row['capability'];
}
$uid = $return['uid'];
$q = cs_query('login',$return['name'],$return['role'],$return['mod'],$return['cap'],$_POST['msg']); //log on!.
$return = array_merge($return,$q);
$return['key'] = bcpowmod($return['key'],$_POST['e'],$_POST['n']);
if(isset($return['des'])) $return['des'] = bcpowmod($return['des'],$_POST['e'],$_POST['n']);
echo json_encode($return);    

