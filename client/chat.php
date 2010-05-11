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
    default:
    }
    return $class;
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
    	<h3>Committee Rooms</h3>
<?php   }
        if($rooms['blind']) {
?>    	<input id="R<?php echo $row['rid']; ?>" 
                type="button" onclick="MBchat.goToRoom(<?php echo $row['rid']; ?>)" 
                class="<?php echo getRoomClass($row['type']);?>"
                value="<?php echo $row['name']; ?>" /><br/>
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

