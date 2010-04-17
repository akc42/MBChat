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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['quid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$quid = $_POST['quid'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('./send.php');

class Release extends LogWriter {

    function __construct() {
        parent::__construct(Array('release' => "UPDATE users SET question = NULL WHERE uid = :uid ;"));
    }

    function doWork() {

        if($user = $this->getRow("SELECT uid, name, role, rid, question FROM users WHERE uid = $quid ;", true)) {
            $this->bindInt('release','uid',$quid);
            $this->post('release');
            $this->sendLog($quid, $user['name'],$user['role'],"ME",$user['rid'],$user['question']);	
        }
    }
}

$r = new Release();
$r->transact();
unset($r);
echo '{ "Status" : "OK"}';
?>
