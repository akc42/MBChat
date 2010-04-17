<?php
/*
 	Copyright (c) 2009 Alan Chandler
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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['text']) && isset($_POST['wid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));

define ('MBC',1);   //defined so we can control access to some of the files.
include_once('./send.php');

class Whisper extends LogWriter {

    function __construct() {
        parent::construct(Array('there' => "UPDATE users SET time = :time WHERE uid = :uid ;"));
    }

    function doWork() {
        $uid = $_POST['user'];
        $wid = $_POST['wid'];

        if($row = $this->getRow("SELECT participant.uid, users.name, role, wid  FROM participant LEFT JOIN users ON users.uid = participant.uid WHERE
                        participant.uid = $uid AND wid = $wid ;",true)) {
            $this->bindInt('there','uid',$uid);
            $this->bindInt('there','time',time());
            $this->post('there');
            if($_POST['text'] != '')
                $this->sendLog($uid, $row['name'],$role,"WH",$wid,$_POST['text']);	
            }
        }
    }
}
        
$w = new Whisper();
$w->transact();
unset($w);

echo '{"Status":"OK"}';
?> 
