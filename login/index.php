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

/* This version is modified from the base to support remote authentication as opposed to local */


error_reporting(E_ALL);
require_once('../inc/client.inc');
require_once('../data/private.inc');


if(!isset($_REQUEST['user'])) {
    cs_forbidden();
}
$username = $_REQUEST['user'];

$t = ceil(time()/300)*300; //This is the 5 minute availablity password
$r1 = md5(REMOTE_KEY.sprintf("%010u",$t));
$r2 = md5(REMOTE_KEY.sprintf("%010u",$t+300));

$return = Array();

switch(substr($username,0,3)) {
case '$$$':
    if ($_POST['pass1'] == $r1 || $_POST['pass1'] == $r2 || $_POST['pass2'] == $r1 || $_POST['pass2'] == $r2) {
        if(isset($_POST['trial'])) {
            echo '{"status":true,"trial":"'.bcpowmod($_POST['trial'],RSA_PRIVATE_KEY,RSA_MODULUS).'"}';
            exit;
        }
    }
    cs_forbidden();
case '$$#':
    if ($_POST['pass1'] == $r1 || $_POST['pass1'] == $r2 || $_POST['pass2'] == $r1 || $_POST['pass2'] == $r2) {
        /*  This particular version of chat assumes the credentials will already have been placed into a cookie
            by a previous call to the '$$R' case below.  If it has then fine, if not we need to declare an error so that 
            the chat can be redirected to go get the credentials checked.
        */
        if(isset($_COOKIE['mbchat'])) {
            $data = json_decode($_COOKIE['mbchat']);
            $key = bcpowmod($data['key'],RSA_PRIVATE_KEY,RSA_MODULUS);
            $msg = base64_decode($data['params');
            $iv = substr($msg, 0, 32);
            $msg = substr($msg, 32);

            $td = mcrypt_module_open('rijndael-256', '', 'ctr', '');
            mcrypt_generic_init($td, $key, $iv);
            $msg = mdecrypt_generic($td, $msg);
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td); 
            
            $msg = unserialize($msg);

             
            $return['status'] = true;
            $return['login'] = $msg;
            
        } else {
            $return['status'] = false;
        }
        echo json_encode($return);    
        exit;
    }
    cs_forbidden();
case '$$R':
    if ($_GET['pass1'] == $r1 || $GET['pass1'] == $r2 || $_GET['pass2'] == $r1 || $_GET['pass2'] == $r2) {
        // We now have a valid requester - so we need to maka a cookie 
        $return['key']=$_GET['key'];
        $return['params']=$_GET['params'];
        // for testing, I want to check cookie contents, so I will keep it - in production we will make it a session cookie
        setcookie('mbchat',json_encode($return),time()+60*60*24);
        //Now return a javascript program that sends us to chat
        echo "window.location = '".CHAT_URL."index.php'\n";
        exit;
    }
    cs_forbidden();
}



