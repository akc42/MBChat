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
require_once('../inc/private.inc');


if(!isset($_POST['user'])) {
    cs_forbidden();
}
$username = $_POST['user'];
if ($username == '$$$') {
	/*
	 * This is a special username, asking us to verify ourselves regardless of how we are authenticating. so we respond to it
	 */
        if (!cs_tcheck(REMOTE_KEY,$_POST['pass'])) cs_forbidden();

        if(isset($_POST['trial'])) {
            echo '{"status":true,"trial":"'.bcpowmod($_POST['trial'],RSA_PRIVATE_KEY,RSA_MODULUS).'"}';
        }
}else {
	/*
	 * We have already confirmed who we are - internal authentication must be happening and so 
	 */
	if(!isset($_POST['purge'])) cs_forbidden();
	
    if(!file_exists(DATA_DIR.'users.db') ) {
        $db = new SQLite3(DATA_DIR.'users.db');
        $db->exec(file_get_contents(dirname(__FILE__).'/../inc/users.sql'));
    }

    $return = Array();

    $db = new PDO('sqlite:'.DATA_DIR.'users.db');
    $db->exec("DELETE FROM users WHERE time < ".(time() - $_POST['purge'] * 86400)." AND isguest = 1"); //purge old guests

    If(substr($username,0,3)  == '$$G') {
        //A guest
        if (cs_tcheck('guest',$_POST['pass'])) {
            $db->exec("INSERT INTO users (name,password,isGuest) VALUES ('$username','guest',1)");
            $return['login']['uid'] = $db->lastInsertID();
            $return['login']['name'] = substr($username,3).'_(G)';
            $return['login']['role'] = 'B';
            $return['login']['cap'] = 0;
            $return['login']['rooms'] = '';
            $return['status'] = true;
            $return['login']['pass'] = md5('U'.$return['login']['uid']."P".REMOTE_KEY);
        } else 
	        cs_forbidden();
    } else {
        //Normal user
        $result = $db->query("SELECT password FROM users WHERE  name = '".strtolower($username)."' ");
        if($pass = $result->fetchColumn()){
            if (cs_tcheck($pass,$_POST['pass'])) {
                $result->closeCursor();
                $result = $db->query("SELECT uid,role,cap,rooms FROM users WHERE name = '".strtolower($username)."'");
                $return['login'] = $result->fetch(PDO::FETCH_ASSOC);
                $return['login']['name'] = $username; //ensuring case is as we entered it
                $return['status'] = true;
                $return['login']['pass'] = md5('U'.$return['login']['uid']."P".REMOTE_KEY);
            } else {
                $return['status'] = false;
                $return['usererror'] = false;
                $return['comment'] = "wrong user password";
            }
        } else {
            $return['status'] = false;
            $return['usererror'] = true;
            $return['comment'] = "user not found";
        }
        $result->closeCursor();
    }
    echo json_encode($return);    
}

