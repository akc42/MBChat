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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['wuid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));

$wid = 0;
$wuser= 0;
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('./send.php');

class Whisper extends LogWriter {

    function __construct() {
        parent::__construct(Array(
            'seq' => "UPDATE wid_sequence SET value = value + 1 ;",
            'in' => "INSERT into participant (wid,uid) VALUES (:wid,:uid);",
            'user' => " SELECT uid,name,role,moderator FROM users WHERE uid = :uid OR uid = :wuid ;"));
    }
    
    function doWork() {
        global $wid,$wuser;
    
        $uid = $_POST['user'];
        $wuid = $_POST['wuid'];
        $this->bindInt('user','uid',$uid);
        $this->bindInt('user','wuid',$wuid);
        $result = $this->query('user');
        if($wuser = $this->fetch($result) && $user = $this->fetch($result)) {
            $this->free($result);
        	if ($user['uid'] != $uid ) {
        	    $t = $user;
        	    $user = $wuser;
        	    $wuser = $t;
        	}
        	$this->post('seq');
        	$wid = $this->getValue("SELECT value FROM wid_sequence ;");
        	$this->bindInt('in','wid',$wid);
        	$this->bindInt('in','uid',$wuid);
        	$this->post('in',true);
        	$this->bindInt('in','uid',$uid);
        	$this->post('in');
        	$this->sendLog($wuid, $wuser['name'],$wuser,"WJ",$wid,'');
        	$this->sendLog($uid, $user['name'],$user['role'],"WJ",$wid,'');
        } else {
            $this->free($result);
        }
    }
} 

$w = new Whisper();
$w->transact();  
unset($w);         
	
echo '{ "wid" :'.$wid.', "user" :'.json_encode($wuser).'}';
?>
