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

if (!defined('MBC'))
	die('Hacking attempt...');
require_once('./db.php');
	
function send_to_all($uid,$name,$role,$type,$rid,$text) {
	dbQuery('INSERT INTO log (uid, name, role, type, rid, text) VALUES ('.
			dbMakeSafe($uid).','.dbMakeSafe($name).','.dbMakeSafe($role).
			', '.dbMakeSafe($type).' ,'.dbMakeSafe($rid).','.dbMakeSafe($text).');');
    $lid = dbLastId();

    $message = '<{"lid":'.$lid.',"user" :{"uid":'.$uid.',"name":"'.$name.'","role":"'.$role.'"},"type":"'.$type.'","rid":'.$rid.',';
    $message .= '"message":"'.$text.'","time":'.time().'}>';

    if ($dh = opendir('./data/')) {
        while (($file = readdir($dh)) !== false) {
            if (filetype('./data/'.$file) == 'fifo') {
                $writer=fopen ('./data/'.$file,'r+');
                fwrite($writer,$message);
                fclose($writer);  
            }
        }
        closedir($dh);
    }
    file_put_contents('./data/time.txt', ''.time()); //make a time file
    return $lid;
}
?>

