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

// No call the remote chat with all the correct parameters
if(in_array(SMF_CHAT_LITE,$groups)) {
	$extra = 'lite.php';
} else {
    $extra = 'chat.php';
}
$url = "http://$host$uri/$extra";
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Melinda's Backups Chat</title>
	<link rel="stylesheet" type="text/css" href="chat.css" title="mbstyle"/>
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="chat-ie.css"/>
	<![endif]-->
	<script src="/js/mootools-1.2.3-core-yc.js" type="text/javascript" charset="UTF-8"></script>
</head>
<body>
<script type="text/javascript">
	<!--

window.addEvent('domready', function() {
    document.chatform.submit();
}
	// -->
</script>
<form name="chatform" action="<?php echo $url;?>">
<input type="hidden" name="uid" value="<?php echo $uid; ?>" />
<input type="hidden" name="pass" value="<?php echo $pass; ?>" />
<input type="hidden" name="name" value="<?php echo $name; ?>" />
<input type="hidden" name="role" value="<?php echo $role; ?>" />
<input type="hidden" name="mod" value="<?php echo $mod; ?>" />
<input type="hidden" name="whi" value="<?php echo $whisper; ?>" />
<input type="hidden" name="gp" value="<?php echo $gp; ?>" />
</form>
</body>
</html>


