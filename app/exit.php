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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['rid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$rid = $_POST['rid'];

define ('MBC',1);   //defined so we can control access to some of the files.
include('./send.php');

class Leaver extends LogWriter {

    function __construct($statements) {
        parent::__construct($statements);
    }
    
    function doWork() {
        $uid = $_POST['user'];
        $rid = $_POST['rid'];
        
        $room = $this->getRow("SELECT rid, name, type FROM rooms WHERE rid = $rid ;");
        $user = $this->getRow("SELECT uid, name, role, moderator FROM users WHERE uid = $uid ;");
        if ($room['type'] == 'M'  && $user['moderator'] != 'N') {
        //This is a moderated room, and this person is not normal - so swap them out of moderated room role
	        $role = $user['moderator'];
	        $mod = $user['role'];
        } else {
	        $role = $user['role'];
	        $mod = $user['moderator'];
        }
	    $this->bindChars('exit','role',$role);
	    $this->bindChars('exit','mod',$mod);
	    $this->bindInt('exit','uid',$uid);
	    $this->bindInt('exit','time',time());
	    $this->post('exit');
	    $this->sendLog($uid, $user['name'],$role,"RX",$rid,'');	
    }
}
$sql = "SELECT lid  FROM log";
$sql .= " LEFT JOIN participant ON participant.wid = rid WHERE participant.uid = ".$uid ;
$sql .= " AND type = 'WH' AND log.time > :t ORDER BY lid DESC LIMIT :m ";



$sql2 = "SELECT lid, time, type, rid, log.uid AS uid , name, role, text  FROM log LEFT JOIN participant ON participant.wid = rid";
$sql2 .= " WHERE participant.uid = $uid AND type = 'WH' AND lid >= :lid ";


$e = new Leaver(Array(
                'lid' => $sql,
                'msg' => $sql2,
                'exit' => "UPDATE users SET rid = 0, time = :time, role = :role , moderator = :mod WHERE uid = :uid "));
if ($rid != 0) {
    $e->transact();
}

echo '{"messages" : [';

//should only return the whispers

//First run just finds the lid to start the query in the correct order from
//We do it this way because there could be a lot of messages and I don't want to fill up memory with them
$e->bindInt('lid','t',time() - 60*$e->getParam('max_time'));
$e->bindInt('lid','m',$e->getParam('max_messages'));
$result = false;
do {
    try {
        $result = $e->query('lid');
        break;
    } catch (DBCheck $ex) {
        $e->checkBusy();
    }
} while(true);    


while($row = $e->fetch($result)) {
    $lid = $row['lid'];
}
$e->free($result);

//now we know where to start, actually collect the messages to display



$donefirst = false;
$e->bindInt('msg','lid',$lid);
$result = false;
do {
    try {
        $result = $e->query('msg');
        break;
    } catch (DBCheck $ex) {
        $e->checkBusy();
    }
} while(true);    

while( $row = $e->fetch($result)) {
    if($donefirst) {
        echo ",\n";
    }
    $donefirst = true;
		$user = array();
		$item = array();
		$item['lid'] = $row['lid'];
		$lid = $row['lid'];
		$item['type'] = $row['type'];
		$item['rid'] = $row['rid'];
		$user['uid'] = $row['uid'];
		$user['name'] = $row['name'];
		$user['role'] = $row['role'];
		$item['user'] = $user;
		$item['time'] = $row['time'];
		$item['message'] = $row['text'];
		echo json_encode($item);
}
$e->free($result);
unset($e);
echo '], "lastid" :'.$lid.'}';
?>
