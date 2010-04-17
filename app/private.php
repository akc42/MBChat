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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['wid']) && isset($_POST['rid'])))
	die('Private - Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Private - Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));


define ('MBC',1);   //defined so we can control access to some of the files.
include_once('./send.php');

class Private extends LogWriter {

    function __construct() {
        parent::__construct(Array('priv' => "UPDATE users SET time = :time, private = :wid WHERE uid = ".$_POST['uid']" ;"));
    }
    
    function doWork() {
        $uid = $_POST['user'];
        $wid = $_POST['wid'];
        $rid = $_POST['rid'];

        if ($wid != 0 ) {
            $row = getRow("SELECT participant.uid, users.name, role, wid  FROM participant 
				            JOIN users ON users.uid = participant.uid WHERE participant.uid =  $uid AND wid = $wid ;");
			$this->bindInt('priv','wid',$wid);
			$this->bindInt('priv','time',time());
			$this->post('priv');
			$this->sendLog($uid, $row['name'],$row['role'],"PE",$wid,'');	
        } else {
            $row = getRow("SELECT uid, name, role FROM users WHERE uid = $uid ;");
			$this->bindInt('priv','wid',0);
			$this->bindInt('priv','time',time());
			$this->post('priv');
			$this->sendLog($uid, $row['name'],$row['role'],"PX",$rid,'');
		}
	}
}

$p = new Private();
$p->transact();
unset($p);
echo '{"Status":"OK"}';
?> 
