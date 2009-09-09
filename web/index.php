<?php
/* A new version of chat
	Copyright (c) 2008 Alan Chandler
	Licenced under the GPL
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

// No call the remote chat with all the correct parameters
if(in_array(SMF_CHAT_LITE,$groups)) {
	header( "Location: lite.php?uid=$uid&pass=$pass&name=$name&role=$role&mod=$mod&whi=$whisperer&gp=$gp" );
	exit;
};
header( "Location: chat.php?uid=$uid&pass=$pass&name=$name&role=$role&mod=$mod&whi=$whisperer&gp=$gp");
?>
