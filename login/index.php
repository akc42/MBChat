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


error_reporting(E_ALL);

$t = ceil(time()/300)*300;

$username = $_POST['user'];
$return = Array();

$db = new PDO('sqlite:../data/users.db');

if (substr($_POST['user'],0,3) == '$$G') {
    //A guest
    $r1 = md5('guest'.sprintf("%012u",$t));
    $r2 = md5('guest'.sprintf("%012u",$t+300));
    if ($_POST['pass1'] == $r1 || $_POST['pass1'] == $r2 || $_POST['pass2'] == $r1 || $_POST['pass2'] == $r2) {
        $db->exec("INSERT INTO users (name,password,isGuest) VALUES ('$username','guest',1)");
        $return['login']['uid'] = $db->lastInsertID();
        $return['login']['name'] = substr($username,3).'_(G)';
        $return['login']['role'] = 'B';
        $return['login']['mod'] = 'N';
        $return['login']['whi'] = true;
        $return['login']['cap'] = '';
        $return['status'] = true;
    } else {
        $return['status'] = false;
        $return['usererror'] = true;
    }
} else {
    //Normal user
    $result = $db->query("SELECT password FROM users WHERE isguest = 0 AND name = '$username'");
    if($pass = $result->fetchColumn()){
        $r1 = md5($pass.sprintf("%012u",$t));
        $r2 = md5($pass.sprintf("%012u",$t+300));
        if ($_POST['pass1'] == $r1 || $_POST['pass1'] == $r2 || $_POST['pass2'] == $r1 || $_POST['pass2'] == $r2) {
        
            $result->closeCursor();
            $result = $db->query("SELECT * FROM users WHERE name = '$username'");
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $return['login']['uid'] = $row['uid'];
            $return['login']['name'] = $row['name'];
            $c = explode(":",$row['capability']);
            $return['login']['whi'] = (!in_array('501',$c));
            $return['login']['role'] = (in_array('10',$c)?'A':(in_array('12',$c)?'L':(in_array('14',$c)?'G':(in_array('16',$c)?'H':'R'))));
            $return['login']['mod'] = (in_array('2',$c)?'M':(in_array('3',$c)?'S':'N'));
            $return['login']['cap'] = $row['capability'];
            $return['status'] = true;
        } else {
            $return['status'] = false;
            $return['usererror'] = false;
            
        }
    } else {
        $return['status'] = false;
        $return['usererror'] = true;
    }
    $result->closeCursor();
}
echo json_encode($return);    



