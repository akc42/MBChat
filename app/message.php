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
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['text']) && isset($_POST['rid'])))
	die('Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));


   // we need to get the text in an html pure form as possible

define ('MBC',1);   //defined so we can control access to some of the files.
include_once('./send.php');

class Message extends LogWriter {

    function __construct()  {
        parent::__construct(Array('question' => "UPDATE users SET time = :time, question = :q , rid = :rid WHERE uid = :uid ;"));
    }

    function doWork() {
        $text = htmlentities(stripslashes($_POST['text']),ENT_QUOTES,false);
        $rid = $_POST['rid'];
        $uid = $_POST['user'];
        $row = $this->getRow("SELECT uid, users.name, role, question, users.rid, type ".
                            "FROM users LEFT JOIN rooms ON users.rid = rooms.rid WHERE uid=".$uid." ;");
    	
	    $role = $row['role'];
	    $type = $row['type'];
	    $mtype = '' ;
	    if ($type == 'M' && $role != 'M' && $role != 'H' && $role != 'G' && $role != 'S' ) {
	    //we are in a moderated room and not allowed to speak, so we just update the question we want to ask
		    if( $text == '') {
		        $this->bindNull('question','q');
    			$mtype = "MR";
    		} else {
    		    $this->bindText('question','q',$text);
    		    $mtype = "MQ";
		        }
	    } else {
            $this->bindNull('question','q');
		//just indicate presence
		    if ($text != '') {  //only insert non blank text - ignore other
		        $mtype = "ME";
		    }
		}
        $this->bindInt('question','time',time());
        $this->bindInt('question','rid',$rid);
        $this->bindInt('question','uid',$uid);
        $this->post('question');
	    if ($mtype != '') {
	        $this->sendLog($uid, $row['name'],$role,$mtype,$rid,$text);
        }
    }
}

$m = new Message();
$m->transact();
unset($m);
echo '{"Status":"OK"}';
?> 
