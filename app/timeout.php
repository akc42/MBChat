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
if (!defined('MBC'))
	die('Hacking attempt...');
require_once('./send.php');

class Timeout extends LogWriter {

    function __construct($statements) {
        $statements['timeout'] = "SELECT uid, name, role, rid, permanent FROM users WHERE time < :time AND present = 1;";
        $statements['delete_user'] = "DELETE FROM users WHERE uid = :uid ;";
        $statements['set_absent'] = "UPDATE users SET present = 0 WHERE uid = :uid ;";
        parent::__construct($statements);
    }
    
    function doTimeout () {
        $this->bindInt('timeout','time',time() - $this->getParam('user_timeout'));
        $result = $this->query('timeout');
        while($row = $this->fetch($result)) {
            if(is_null($row['permanent'])) {
                $this->bindInt('delete_user','uid',$row['uid']);
                $this->post('delete_user',true);
            } else {
                $this->bindInt('set_absent','uid',$row['uid']);
                $this->post('set_absent',true);
            }
            if(file_exists("./data/msg".$row['uid'])) {
                unlink("./data/msg".$row['uid']); //Loose FIFO
            }
            $this->sendLog($row['uid'], $row['name'],$row['role'],"LT",$row['rid'],'');
        }	
        $this->free($result);
    }
};

?>
