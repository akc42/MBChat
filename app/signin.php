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


error_reporting(E_ALL);

define('MBC',1);
include('./db.php');


$result = dbQuery("SELECT * FROM users WHERE name = ".dbMakeSafe($_POST['username'])." OR name = ".dbMakeSafe($_POST['username']." (G)").";");
$row = dbFetch($result);
if($row && $row['present'] == '0' && (is_null($row['permanent']) || $row['permanent'] == md5($_POST['password']))) {
// We are in the database, not present and either a non permenant entry or a permenant entry with the correct password
        $gp = $row['groups'];       
        $groups = explode(":",$gp);
        
        $uid = $row['uid'];
        $pass = sha1("Key".$uid);
        $name = $row['name'];
        $role = $row['role'];
        $mod = $row['moderator'];
        $whisperer = (in_array(23,$groups))?"false":"true";
        $lite = (in_array(22,$groups))?'lite':'normal';
} else {
    $name = $_POST['username']." (G)";
    $role = "R";
    $mod = "N";
    $whisperer = "true";
    $gp = "12";  
    $lite = (isset($_POST['lite']))?'lite':'normal';
    dbQuery("INSERT INTO users (name,groups) VALUES (".dbPostSafe($name).",'12');");
    $uid = dbLastId();
    $pass = sha1("Key".$uid);

}
dbFree($result);


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Melinda's Backups Chat</title>
	<script src="/static/scripts/mootools-1.2.4-core-yc.js" type="text/javascript" charset="UTF-8"></script>
</head>
<body>
<script type="text/javascript">
	<!--

window.addEvent('domready', function() {
    document.chatform.submit();
});
	// -->
</script>
<form name="chatform" action="<?php echo './chat.php';?>" method="post">
<input type="hidden" name="uid" value="<?php echo $uid; ?>" />
<input type="hidden" name="pass" value="<?php echo $pass; ?>" />
<input type="hidden" name="name" value="<?php echo $name; ?>" />
<input type="hidden" name="role" value="<?php echo $role; ?>" />
<input type="hidden" name="mod" value="<?php echo $mod; ?>" />
<input type="hidden" name="whi" value="<?php echo $whisperer; ?>" />
<input type="hidden" name="gp" value="<?php echo $gp; ?>" />
<input type="hidden" name="ctype" value="<?php echo $lite; ?>" />
</form>
</body>
</html>
