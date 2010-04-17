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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['wuid']) && isset($_POST['wid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('./send.php');

class Join extends LogWriter {

    function __construct() {
        parent::__construct(Array('join' => "INSERT INTO participant (wid,uid) VALUES (:wid, :uid);"));
    }
    
    function doWork() {
        $uid = $_POST['user'];
        $wuid = $_POST['wuid'];
        $wid = $_POST['wid'];
        $num = $this->getValue("SELECT count(*) FROM participant WHERE uid = $uid AND wid = $wid ;");
        if($num != 0) {
            //I am in this whisper group, so am entitiled to add the new person
            if($row = $this->getRow("SELECT name, role FROM users WHERE uid = $wuid ;",true)) {
                $this->bindInt('join','wid',$wid);
                $this->bindInt('join','uid',$wuid);
                $this->sendLog($wuid, $row['name'],$row['role'],"WJ",$wid,'');	
            }
        }
    }
}

$j = new Join();
$j->transact();
unset($j);
echo '{ "Status" : "OK"}';
?>
