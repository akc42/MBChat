<?php
/*  MBChat - an Ajax based Chat application for Melinda's Backups
 * Author:  Alan Chandler
 * Copyright (c) 2008 - Alan Chandler
 * Licenced under the GPL
 *
 */
	if (!defined('MBC'))
		die('Hacking attempt...');


define('MB_CHAT_BOT_NAME', 'Hephaestus');

require_once(MBCHAT_PATH.'db.php');

class MBChat {
	var $_user;
	function MBChat($user) {
		$this->_user = $user;

	}

/*	function json_user() {
		return json_encode($this->_user);
	}
*/
	function getAdultOrBabyRoom() {
		return ($this->_user->getRole() == 'B' ) ? 'green-room': 'blue-room' ;
	}

	function getRoomNames() {
		$sql = 'SELECT id, name, type FROM rooms JOIN  member_list_entry AS ml ON ml.room_id = rooms.id' ;
		$sql .= ' WHERE ml.member_id = '.dbMakeSafe($this->_user->getId()).';';
		$result = dbQuery($sql); 
		$names = Array();
		while ($row = mysql_fetch_assoc($result)) {
			if($row['type'] == 'L' ) {
				$key = $row['id'];
				$names[$key] = $row['name'];
			}; 
		} ;
		return $names;
	}
	function generateRoomURL($roomId) {
		return '"room.php?userid='.$this->_user->getId().'&roomid='.$roomId.'"';
	}

	function userSoundSetting() {
		return '"'.(($this->_user->getSoundEnable())?'checked':'').'"';
	}

	function userSoundDelay() {
		return '"'.$this->_user->getSoundDelay().'"';
	}

	function mayCreateRooms() {
		return ($this->_user->getRole() == 'A' or $this_user->getRole() == 'L') ;
	}

	function createRoomURL() {
		return '"create.php?userid='.$this->_user->getId().'"';
	}		
}

?>