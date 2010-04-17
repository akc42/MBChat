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
if(!(isset($_POST['user']) && isset($_POST['password']) ))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('./send.php');

class Logout extends LogWriter {

    function __construct() {
        parent::__construct(Array(
                    'delete' => "DELETE FROM users WHERE uid = :uid ;", 
                    'update' => "UPDATE users SET present = 0 WHERE uid = :uid ;"));
    }
    
    function doWork() {
    
        $uid = $_POST['user'];
        $row=$this->getRow("SELECT uid, name, role, rid FROM users WHERE uid = $uid ;");
    
        if(is_null($row['permanent'])) {
            $this->bindInt('delete','uid',$uid);
            $this->post('delete');
        } else {
            $this->bindInt('update','uid',$uid);
            $this->post('update');
        }
        
        $txt = 'MBchat version: '.$_POST['mbchat'].', Mootools Version : '.$_POST['version'].' build '.$_POST['build'] ;
        $txt .=' Browser : '.$_POST['browser'].' on Platform : '.$_POST['platform'];
        $this->sendLog($uid, $row['name'],$row['role'],"LO",$row['rid'],$txt);	
	}
};
$l = new Logout();
$l->transact();
unset($l);

usleep(20000);
unlink("./data/msg".$uid); //Loose FIFO

echo '{"Logout" : '.$txt.'}' ;
?> 
