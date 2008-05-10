<?php
//This is a convenient place to force everything we output to not be cached (even 
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	if (!defined('MBC'))
		die('Hacking attempt...');
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
