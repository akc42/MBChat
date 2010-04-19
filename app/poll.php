<?php
/*
 	Copyright (c) 2010 Alan Chandler
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

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: -1"); // Date in the past
if(!(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['lid']) ))
	die('Poll-Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));

define ('MBC',1);   //defined so we can control access to some of the files.
require_once('./db.php');

class Reader extends DB {

    function __construct($sql) {
        parent::__construct(Array('read' => $sql));
    }
    
    function doWork() {
        $lid = 0;
        $result = $this->query('read');
        //last point of rollback and repeat
        $donefirst = false;
        while($row = $this->fetch($result)) {
            if($donefirst) {
                echo ",";
            }
            $donefirst = true;
            $message = '{"lid":'.$row['lid'].',"user" :{"uid":'.$row['uid'].',"name":"'.$row['name'].'","role":"';
            $message .= $row['role'].'"},"type":"'.$row['type'].'","rid":'.$row['rid'].',"message":"'.$row['text'].'","time":'.$row['time'].'}';
            echo $message;
            $lid =$row['lid'];
        }
        if($donefirst) { return $lid; } else { return $_POST['lid'] -1;};
        
    }
}    
    
    echo '{"messages":[' ;      

        $d = new Reader("SELECT * FROM log WHERE lid >= ".$_POST['lid']." ORDER BY lid ASC ;");
        $lid = $d->transact(); 
        unset($d);
   
    echo '],"lastlid": '.$lid.'}';
?>
