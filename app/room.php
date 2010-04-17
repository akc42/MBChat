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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['rid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];
if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$rid = $_POST['rid'];

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('./send.php');

class Room extends LogWriter {

    function __construct($statements) {
        parent::__construct($statements);
    }

    function doWork() {
        $uid = $_POST['user'];
        $rid = $_POST['rid'];
        if($room = $this->getRow("SELECT rid, name, type FROM rooms where rid = $rid ;",true)) { //validate room
            $user = $this->getRow("SELECT uid, name, role, moderator, question FROM users WHERE uid = $uid ;");

		    if ($room['type'] == 'M'  && $user['moderator'] != 'N') {
		    //This is a moderated room, and this person is not normal - so swap them into moderated room role
			    $role = $user['moderator'];
			    $mod = $user['role'];
		    } else {
			    $role = $user['role'];
			    $mod = $user['moderator'];
		    }
		    $name = $user['name'];
		    $question = $user['question'];

            $this->bindInt('enter','uid',$uid);
            $this->bindInt('enter','rid',$rid);
		    $this->bindChars('enter','role',$role);
		    $this->bindChars('enter','mod',$mod);
		    $this->bindInt('enter','time',time());
		    $this->post('enter');
		    $this->sendLog($uid, $name,$role,"RE",$rid,$question);
		    //At this point, we should have finished any possibiliy of a rollback, so now we can start to build the output
            echo '{"room":'.json_encode($room);



		    
        } else {
            echo '{"room":{}';
        }
    }
}

$sql = "SELECT lid  FROM log LEFT JOIN participant ON participant.wid = rid WHERE ( (participant.uid = $uid AND type = 'WH' )" ;
$sql .= " OR rid = $rid) AND log.time > :t ORDER BY lid DESC LIMIT :m ";

$sql2 = "SELECT lid, time, type, rid, log.uid AS uid , name, role, text  FROM log LEFT JOIN participant ON participant.wid = rid";
$sql2 .= " WHERE ( (participant.uid = $uid  AND type = 'WH' ) OR rid = $rid ) AND lid >= :lid ";



$r = new Room(Array(
                'lid' => $sql,
                'msg' => $sql2,
                'enter' => "UPDATE users SET rid = :rid , time = :time, role = :role, moderator = :mod WHERE uid = :uid ;"));
$r->transact();

echo ',"messages" : [';


//First run just finds the lid to start the query in the correct order from
//We do it this way because there could be a lot of messages and I don't want to fill up memory with them
$r->bindInt('lid','t',time() - 60*$r->getParam('max_time'));
$r->bindInt('lid','m',$r->getParam('max_messages'));

$result = $r->query('lid');

while($row = $r->fetch($result)) {
    $lid = $row['lid'];
}
$r->free($result);

//now we know where to start, actually collect the messages to display


$donefirst = false;
$r->bindInt('msg','lid',$lid);
$result = $r->query('msg');

while( $row = $r->fetch($result)) {
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
echo '], "lastid" :'.$lid.'}';
$r->free($result);
unset($r);
?>
