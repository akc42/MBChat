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


/*  Although it doesn't look like it, this file is supposed to return some javascript.  It is
    requested by the browser as it loads the chat page - meaning that the browsers cookies for this
    will be set, enabling us to determine login details.  We will either return some javascript that
    immediately redirects the user to the chat.html page (saying what an interesting facility chat is, 
    but you need to be logged on, or we will return some javascript which will ask the server to confirm who he
    is and then co-ordinate with the rsa generation to allow logon
*/


// Link to SMF forum as this is only for logged in members
// Show all errors:
error_reporting(E_ALL);
define('NO_LOGIN_URL','http://www.melindasbackups.com/chat2/chat.html');
include('./public.inc');


function cs_tcheck($key,$pass) {
    return ($pass == md5($key));
}
//ensure we had a proper request from the chat software
if (!cs_tcheck(REMOTE_KEY,$_GET['pass']))  {
    header('HTTP/1.0 403 Forbidden');
?><html>
    <head>
        <style type="text/css">
            body {
                font-family: Arial;
                color: #345;
            }
            h1 {
                border-bottom: 3px solid #345;
            }
            a {
                color: #666;
            }
        </style>
    </head>
    <body>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-6767400-1']);
  _gaq.push(['_trackPageview']);
</script>        
<h1>Forbidden</h1>
        <p>This URL is intended to only be called by authorised applications</p>
<!-- Google Analytics Tracking Code -->
  <script type="text/javascript">
    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(ga);
    })();
  </script>

    </body>
</html>
<?php
exit;
}



require_once(dirname(__FILE__).'/../forum/SSI.php');
//If not logged in to the forum, not allowed so send back the javascript to redirect to our error page
if($user_info['is_guest']) {
    echo "window.location = '".NO_LOGIN_URL."' ;\n";
    exit;
}
$groups =& $user_info['groups'];

echo "loginRequestOptions.uid = $ID_MEMBER ;\n";

echo "loginRequestOptions.pass = '".md5("U".$ID_MEMBER."P".REMOTE_KEY)."' ;\n" ;


echo "loginRequestOptions.name = '".$user_info['name']."' ;\n";

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

echo "loginRequestOptions.role = '".((in_array(SMF_CHAT_HONORARY, $groups))? 'L' : (  // which role 
		    (in_array(SMF_CHAT_BABY, $groups))? 'B' :(
		    (in_array(SMF_CHAT_MELINDA, $groups))?'H' :(
		    (in_array(SMF_BOARD_MODERATOR, $groups) || in_array(SMF_MEMBERSHIP_MGR,$groups))? 'G' : 'R'))))."' ;\n";

$cap = 2; //everyone is a secretary
if(in_array(SMF_CHAT_LITE,$groups)) $cap += 1;
if($user_info['is_admin']) $cap += 4;
if(in_array(SMF_CHAT_MODERATOR,$groups)) $cap += 8;
if(in_array(SMF_CHAT_SPEAKER,$groups)) $cap += 16;
if(in_array(SMF_CHAT_NO_WHISPER,$groups)) $cap+=32;

echo "loginRequestOptions.cap = $cap ;\n";

$rooms = Array();
if(in_array(SMF_CHAT_HONORARY,$groups) || 
        in_array(SMF_BOARD_MODERATOR,$groups) || 
            in_array(SMF_CHAT_MELINDA,$groups)) $rooms[] = '6'; //lead team room
if(in_array(SMF_SCHOLARSHIP,$groups)) $rooms[] = '7';
if(in_array(SMF_MALARIA,$groups)) $rooms[] = '8';
if(in_array(SMF_PROMOTIONS,$groups)) $rooms[] = '9';
if(in_array(SMF_WEBSITE,$groups)) $rooms[] = '10';

echo "loginRequestOptions.rooms = '".implode(':',$rooms)."' ;\n";

echo "coordinator.done('login',{});\n";

