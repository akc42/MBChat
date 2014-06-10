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

/* check we are called with all the right parameters.  If not, we need to call our initialisation routine */


require_once('../inc/client.inc');

cs_validate();

$rooms = cs_query('rooms');
$groups = explode(":",$rooms['cap']);


function getRoomClass ($type) {
    $class = "room";
    switch($type) {
    case 0:
        $class .= " forum";
        break;
    case 1:
        $class .= " meeting";
        break;
    case 2:
        $class .= " gallery";
        break;
    case 3:
        $class .= " auditorium";
        break;
    case 4:
        $class .= " member";
        break;
    case 5:
        $class .= " guest";
        break;
    case 6:
        $class .= " dungeon";
        break;
    default:
    }
    return $class;
}

if(isset($rooms['security']) && isset($_POST['e'])) {
    /* We are concerned about security so in this instance we take the security message and encrypt it with the
        public key and send it to him.  He will decrypt and correct the display - allowing a visual check that its the
        security message set by the admin of this server.  This prevents another server taking over our servers role and
        capturing all the messages
    */
?><div class="security"><span>Security Check:<span id="security">
<?php
    $message = $rooms['security'];
    do {
        //we split into 8 character chunks
        $chunk = substr($message,0,8);
        $chunk = str_pad($chunk,8,"\0\0\0\0\0\0\0\0");
        $digits = '0';
        for($i = 7; $i >= 0; $i--) {
            $digits = bcadd(bcmul($digits,'128'),ord($chunk[$i]));
        }
        $cipher = bcpowmod($digits,$_POST['e'],$_POST['n'],0); //encrypt using public key
?><span class="sc"><?php echo $cipher; ?></span>
<?php
    } while($message = substr($message,8));
?></span></div>
<?php
}
?><div  id="mainRooms" class="rooms">
	    <h3>Main Rooms</h3>
<?php
$i=0;


foreach($rooms['rooms'] as $row) {
    $rid = $row['rid'];
    if( ($row['type'] != 1 || in_array($rid,$groups))
            && (!(($row['type'] == 4 && $rooms['role'] == 'B') || ($row['type'] == 5 && $rooms['role'] != 'B'))) ) {
        if($i > 0 && $i%4 == 0) {
?><div class="rooms">
    	<h3>Meeting Rooms</h3>
<?php   }
        if($rooms['blind']) {
?>    	<input id="R<?php echo $row['rid']; ?>"
                type="button" onclick="MBchat.updateables.message.enterRoom(<?php echo $row['rid']; ?>)"
                class="<?php echo getRoomClass($row['type']);?>"
                value="<?php echo $row['name']; ?>" />
<?php   }else {
?>		<div id="R<?php echo $row['rid']; ?>" class="<?php echo getRoomClass($row['type']);?>"><?php echo $row['name']; ?></div>
<?php   }
        $i++;
	    if( ($i % 4) == 0 ) {
?>  	<div style="clear:both"></div>
	</div>
<?php
        }
    }
}

//If ended loop and hadn't just comleted div we will have to do it here
if( ($i % 4) != 0 ) {
?>      <div style="clear:both"></div>
    </div><?php
}

