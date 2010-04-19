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


define ('MBC',1);   //defined so we can control access to some of the files.
include_once('db.php');
$counter = 0;
class Online extends DB {

    function doWork() {
        global $counter;
        $counter++;
        $uid = $_POST['user'];
        $rid = $_POST['rid'];
        $this->bindInt('o','rid',$rid);
        $lid = $this->getValue("SELECT max(lid) AS lid FROM log;");
        $result = $this->query('o');
        //We have finished database queries that might fail, and therefore repeat so it doesn't matter now that we start outputing stuff
        
        $donefirst = false;
        echo '{ "lastid":'.$lid.', "online":[' ;

        while($row = $this->fetch($result)) {
            if($donefirst) {
                echo ",";
            } else {
            }
            $donefirst = true;
            echo json_encode($row);
        }
        $this->free($result);


    }
}

$o = new Online(Array('o' => "SELECT uid, name, role, question,private AS wid FROM users WHERE rid = :rid AND present = 1 ;"));
$o->transact();
unset($o);

        echo ']}';

if($counter != 1) {
    echo "Something Strange Happenen - counter = $counter <br/>\n";
}

?>
