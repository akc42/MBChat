<?php
/*
 	Copyright (c) 2009,2010 Alan Chandler
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

//This is a convenient place to force everything we output to not be cached 
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	if (!defined('MBC'))
		die('Hacking attempt...');

    try {
        $db = new PDO('sqlite:./data/chat.db');  
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
    
	function dbQuery($sql) {
	    global $db;
		$result = $db->query($sql);
		if (!$result) {
			echo '<tt>';
			echo "<br/><br/>\n";
			print_r(debug_backtrace());			
			echo "<br/><br/>\n";
			echo $sql;
			echo "<br/><br/>\n\n";
			echo "SQL error code = ".$db->errorCode();
			echo "<br/><br/>\n\n";
			echo '</tt>';
			die('<p>Please inform <i>alan@chandlerfamily.org.uk</i> that a database query failed and include the above text.<br/><br/>Thank You</p>');
		}
		return $result;
	}
	function dbMakeSafe($value) {
		if (!get_magic_quotes_gpc()) {
			$value=pg_escape_string($value);
		}
		return "'".$value."'" ;
	}
	function dbPostSafe($text) {
	  if (((string)$text) == '') return 'NULL';
	  return dbMakeSafe(htmlentities($text,ENT_QUOTES,'UTF-8',false));
	}

	function dbFetch($result) {
		return $result->fetch(PDO::FETCH_ASSOC);
	}
	function dbFree($result){
		$result->closeCursor();
	}
	function dbLastId() {
	    global $db;
	    return $db->lastInsertID();
	}
	function dbBegin() {
	    global $db;
	    $db->exec("BEGIN;");
	}
	function dbCommit() {
	    global $db;
	    $db->exec("COMMIT;");
	}
	function dbRollback() {
	    global $db;
	    $db->exec("ROLLBACK;");
	}
?>
