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

$txt = 'MBchat version - '.$_POST['mbchat'].', Mootools_Version - '.$_POST['version'].' - build - '.$_POST['build'] ;
$txt .=' Browser - '.$_POST['browser'].' on Platform - '.$_POST['platform'];
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

$result=dbQuery('SELECT uid, name, role, rid FROM users WHERE uid = '.dbMakeSafe($uid).';');
if($row=dbFetch($result)) {
	
    
//If FIFO doesn't exists (when trying to login after timeout for instance) create it
	if(!file_exists("./data/msg".$uid)) {
	    $old_umask = umask(0007);
    	posix_mkfifo("./data/msg".$uid,0660);
        umask($old_umask);
    }
	
	include_once('send.php');
    $lid = send_to_all($uid, $row['name'],$row['role'],"LI",$row['rid'],'');	
		
};
dbFree($result);

echo '{"Login" : '.$uid.', "lastid" : '.$lid.' }' ;
?> 
