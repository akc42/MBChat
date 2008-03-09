<?php
	if (!defined('MBC'))
		die('Hacking attempt...');
	$db_server = 'localhost';
	$db_name = 'melindas_chat';
	$db_user = 'melindas_chat';
	$db_password = 'x5aces42';
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
