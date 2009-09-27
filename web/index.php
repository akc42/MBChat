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
// Link to SMF forum as this is only for logged in members
// Show all errors:
error_reporting(E_ALL);
// Path to the chat directory:

define('MBCHAT_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/');

require_once(MBCHAT_PATH.'../forum/SSI.php');
//If not logged in to the forum, not allowed any further so redirect to page to say so
if($user_info['is_guest']) {
	header( 'Location: chat.html' ) ;
	exit;
};
$groups =& $user_info['groups'];
$uid = $ID_MEMBER;
$name =& $user_info['name'];

// SMF membergroup IDs for the groups that we have used to define characteristics which control Chat Group
define('SMF_CHAT_BABY',		10);
define('SMF_CHAT_LEAD',		9);
define('SMF_CHAT_MODERATOR',	14);
define('SMF_CHAT_MELINDA',	13);
define('SMF_CHAT_HONORARY',	20);
define('SMF_CHAT_SPECIAL',	19);
define('SMF_CHAT_LITE',		22);
define('SMF_CHAT_NO_WHISPER',23);


$role = ((in_array(SMF_CHAT_LEAD, $groups))? 'L' : (  // which role 
			(in_array(SMF_CHAT_BABY, $groups))? 'B' :(
			(in_array(SMF_CHAT_MELINDA, $groups))?'H' :(
			(in_array(SMF_CHAT_HONORARY, $groups))? 'G' :(
			($user_info['is_admin'])? 'A' : 'R'))))) ;

$mod = (in_array(SMF_CHAT_MODERATOR,$groups)?'M':(in_array(SMF_CHAT_SPECIAL,$groups)?'S':'N'));
$whisperer = (in_array(SMF_CHAT_NO_WHISPER,$groups)?'false':'true');
$pass = sha1("Key".$uid);
$gp = implode(":",$groups);
$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$data = array ('uid' => $uid, 'pass' => $pass, 'name' => $name, 'role' => $role, 'mod' => $mod, 'whi' => $whisperer, 'gp' => $gp);
$data = http_build_query($data);

// No call the remote chat with all the correct parameters
if(in_array(SMF_CHAT_LITE,$groups)) {
	$extra = 'lite.php';
} else {
    $extra = 'chat.php';
}
$url = "http://$host$uri/$extra";

$params = array('http' => array('method' => 'POST', 'content' => $data ));
$ctx = stream_context_create($params);
$fp = @fopen($url, 'rb', false, $ctx);
if (!$fp) {
       die ("Problem with url $url");
}
$response = @stream_get_contents($fp);
if ($response === false) {
       die ("Problem reading data from $url");
}
?>

