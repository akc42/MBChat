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
require_once('../inc/client.inc');

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" type="text/css" href="../css/header.css" />
    <title>MB Chat</title>
	<link rel="stylesheet" type="text/css" href="../css/chat.css" />
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="../css/chat-ie.css"/>
	<![endif]-->
</head>
<body>
<?php require('../inc/header.inc'); ?>
<div id="roomNameContainer"><h1>Logged Off</h1></div>
<div id="content">
    <div id="authblock">
        <p>Sorry, but another person is already logged into chat with the same credentials as you and chat can only support one instance of
        each person running at the same time.</p>
        <p>If you have rectified the problem and would like to return to try again, please click <a href="../index.php">here</a></p>
    </div>
    <div id="copyright">MB Chat <span id="version"><?php include('../inc/version.inc');?></span> &copy; 2008-2010
        <a href="http://www.chandlerfamily.org.uk">Alan Chandler</a></div>
</div>
<?php require('../inc/footer.inc'); ?>
</body>
</html>
