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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['rid']) && isset($_POST['start'])&& isset($_POST['end'])))
	die('Log - Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Log - Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));


define ('MBC',1);   //defined so we can control access to some of the files.
require_once('./send.php');


class Log extends LogWriter {

    function __construct() {
        parent::__construct(Array('log' => "UPDATE users SET time = :time WHERE uid = :uid ;"));
    }
    
    function doWork () {
        $uid = $_POST['user'];
        $user = $this->getRow("SELECT name,role from USERS where uid = $uid ;");
        $this->bindInt('log','uid',$uid);
        $this->bindInt('log','time',time());
        $this->post('log');
        $this->sendLog($uid, $user['name'],$user['role'],"LH",$_POST['rid'],'');
    }
}

$l = new Log();
$l->transact();
	
echo '{"messages":[';	
	
	$sql = "SELECT lid, time AS utime, type, rid, uid , name, role, text  FROM log";
	$sql .= " WHERE time > "$_POST['start'])." AND time < ".$_POST['end']." AND ";
	$sql .= "rid = ".$_POST['rid']." ORDER BY lid ;";

	$result = $l->query($sql);
	$i = 0 ;
    while($row = $l->fetch($result)) {
			$user = array();
			$item = array();
			$item['lid'] = $row['lid'];
			$item['type'] = $row['type'];
			$item['rid'] = $row['rid'];
			$user['uid'] = $row['uid'];
			$user['name'] = $row['name'];
			$user['role'] = $row['role'];
			$item['user'] = $user;
			$item['time'] = $row['utime'];
			$item['message'] = $row['text'];
			if ($i != 0) {
				echo ',';
			}
			$i++ ;
			echo json_encode($item) ;
	};
    $l->free($result);
unset($l);   
echo ']}';
?>
