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
// Link to SMF forum as this is only for logged in members
// Show all errors:
error_reporting(E_ALL);

require_once(dirname(__FILE__).'/../forum/SSI.php');
//If not logged in to the forum, not allowed any further so redirect to page to say so
if($user_info['is_guest']) {
    header('Location: chat.html');
    exit;
}
$groups =& $user_info['groups'];

$query = Array();

$query['uid'] = $ID_MEMBER;

$query['name'] =& $user_info['name'];

// SMF membergroup IDs for the groups that we have used to define characteristics which control Chat Group
define('SMF_CHAT_BABY',		10);
define('SMF_CHAT_LEAD',		9);
define('SMF_CHAT_MODERATOR',14);
define('SMF_CHAT_SPEAKER',19);
define('SMF_CHAT_MELINDA',	13);
define('SMF_CHAT_HONORARY',	20);
define('SMF_BOARD_MODERATOR',	28);
define('SMF_CHAT_LITE',		22);
define('SMF_CHAT_NO_WHISPER',23);
define('SMF_SCHOLARSHIP',12);
define('SMF_MALARIA',16);
define('SMF_PROMOTIONS',17);
define('SMF_WEBSITE',18);
define('SMF_MEMBERSHIP_MGR',29);

$query['role'] = ((in_array(SMF_CHAT_HONORARY, $groups))? 'L' : (  // which role 
		    (in_array(SMF_CHAT_BABY, $groups))? 'B' :(
		    (in_array(SMF_CHAT_MELINDA, $groups))?'H' :(
		    (in_array(SMF_BOARD_MODERATOR, $groups) || in_array(SMF_MEMBERSHIP_MGR,$groups))? 'G' : 'R'))));

$query['mod'] = (in_array(SMF_CHAT_MODERATOR,$groups)?'M':(in_array(SMF_CHAT_SPEAKER,$groups)?'S':'N'));

$cap = 2; //everyone is a secretary
if(in_array(SMF_CHAT_LITE,$groups)) $cap += 1;
if($user_info['is_admin']) $cap += 4;
if(in_array(SMF_CHAT_NO_WHISPER,$groups)) $cap+=32;

$query['cap'] = $cap;

$rooms = Array();
if(in_array(SMF_CHAT_HONORARY,$groups) || 
        in_array(SMF_BOARD_MODERATOR,$groups) || 
            in_array(SMF_CHAT_MELINDA,$groups)) $rooms[] = '6'; //lead team room
if(in_array(SMF_SCHOLARSHIP,$groups)) $rooms[] = '7';
if(in_array(SMF_MALARIA,$groups)) $rooms[] = '8';
if(in_array(SMF_PROMOTIONS,$groups)) $rooms[] = '9';
if(in_array(SMF_WEBSITE,$groups)) $rooms[] = '10';
$rooms = implode(':',$rooms);

$query['rooms'] = $rooms;

include('./url.inc');
include('./public.inc');

$urlquery = Array();

$t = ceil(time()/300)*300; //This is the 5 minute availablity password proving who we are.
$urlquery['pass1'] = md5(REMOTE_KEY.sprintf("%010u",$t));
$urlquery['pass2'] = md5(REMOTE_KEY.sprintf("%010u",$t+300));

$key = sprintf("%05u",rand(1,99999));
$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_BLOWFISH,MCRYPT_MODE_CBC));
$urlquery['user'] = '$$R';
$urlquery['key']=bcpowmod($key,RSA_EXPONENT,RSA_MODULUS);
$urlquery['iv']=$iv;
$urlquery['params'] =base64_encode(mcrypt_encrypt( MCRYPT_BLOWFISH, md5($key), json_encode($query), MCRYPT_MODE_CBC, $iv ));
$url=SERVER_LOCATION.'login/index.php?'.http_build_query($urlquery);
/* The trick here is to make the call to the url from the browser whilst it is loading the script.  The url is going to write a cookie
    with a relatively short life time with the whole query string in it and return a simple javascript program that immediately
    redirects the user to chat.

    The user will arrive at chat, and the authentication part of the script will call the same url first checking it is a valid server and
    then asking asking what it needs to do in relation to prompting the user for credentials.  If the cookie doesn't exist yet the user
    has gone to the chat url directly (possibly because someone has given them that url for chat) and so will be immediately redirected
    here so that the credentials can be checked.  

    If they DO have the cookie, then this call will decrypt the whole query using its private key and essentially return the credentials so
    the rest of chat can get started.

    The important point is that only a valid server and no one else can decrypt the details.  The chat script will only talk to a server if it
    proves it is valid.
*/
?><html><head><script type="text/javascript" src="<?php echo $url; ?>"></script></head><body></body></html>
