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
//This is a convenient place to force everything we output to not be cached (even 
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	if (!defined('MBC'))
		die('Hacking attempt...');

//Use for all pipe accesses
define('MBCHAT_PIPE_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/pipes/');
	$db_server = 'localhost';
	$db_name = 'melindas_chat';
	$db_user = 'melindas_chat';
	$db_password = 'xxxxxx';
	mysql_connect($db_server, $db_user, $db_password) or die('Could not connect to database: ' . mysql_error());
	mysql_select_db($db_name) or die('Could not select database: '.mysql_error());
	function dbQuery($sql) {
		$result = mysql_query($sql);
		if (!$result) {
			die('database query failed: '.mysql_error());
		}
		return $result;
	}
	function dbMakeSafe($value) {
		return "'".mysql_real_escape_string($value)."'" ;
	}
?>
