<?php
/*
 * User - a class to hold the user
 *
 */

	if (!defined('MBC'))
		die('Hacking attempt...');
//This is delay in minutes user has to have not talked for a beep to occur when someone else speaks
define('MBCHAT_DEFAULT_SOUND_DELAY', 5);
require_once(MBCHAT_PATH.'db.php');
class User {
	
var $_id;
var $_name;
var $_role;
var $_soundEnabled;
var $_soundDelay;

	function User ($userID, $userName, $role) {
		$this->_id = $userID;
		$this->_name = $userName;
		$this->_role = $role;	
// see if user is in database, and if so update the timestamp - if not create it
		$sql= 'SELECT id , sound_e, sound_m FROM users WHERE id = '.dbMakeSafe($this->_id).';' ;
		$result = dbQuery($sql);
		if (mysql_num_rows($result) != 0) {
//user exists already
			$row = mysql_fetch_assoc($result) ;
			$this->_soundEnabled = $row['sound_e'];
			$this->_soundDelay = $row['sound_m'];
			$this->updateLastOnline();
		} else {
//user doesn't exist, so create
			$sql='INSERT INTO users (id,name,role,last_online,sound_e,sound_m)';
			$sql .= 'VALUES ('.
				dbMakeSafe($this->_id).','.
				dbMakeSafe($this->_name).','.
				dbMakeSafe($this->_role).', NOW(), FALSE, '.MBCHAT_DEFAULT_SOUND_DELAY.' ) ; ' ;
			dbQuery($sql);
			$this->_soundEnabled = false;
			$this->_soundDelay = MBCHAT_DEFAULT_SOUND_DELAY;
		}
		mysql_free_result($result);

	}
	
	function updateLastOnline() {
		$sql = 'UPDATE users SET last_online = NOW() WHERE id = '.dbMakeSafe($this->_id).';';
		dbQuery($sql);
	}

	function isBaby() {
		return ($role == 'B') ;
	}
	
	function getRole() {
		return $this->_role;
	}

	function getId() {
		return $this->_id;
	}

	function getSoundEnable() {
		return $this->_soundEnabled;
	}

	function getSoundDelay() {
		return $this->_soundDelay;
	}
}
?> 
