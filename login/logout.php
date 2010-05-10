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
    <title>Hartley Chat</title>
	<link rel="stylesheet" type="text/css" href="../css/chat.css" />
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="../css/chat-ie.css"/>
	<![endif]-->
</head>
<body>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', GOOGLE_ACCOUNT]);
  _gaq.push(['_trackPageview']);
</script>
<table id="header" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" >
<tbody>
	<tr>
	<td align="left" width="30" class="topbg_l" height="70">&nbsp;</td>
	<td align="left" colspan="2" class="topbg_r" valign="top"><a href="/" alt="Main Site Home Page">
		<img  style="margin-top: 24px;" src="../images/chandlerfamily_logo.png" alt="Chandler's Zen" border="0" /></a>	
	</td>
	<td align="center" width="300" class="topbg_r" valign="middle">
	MB Chat
	</td>	
	<td align="right" width="400" class="topbg" valign="top">
	<span style="font-family: tahoma, sans-serif; margin-left: 5px;">Chandler&#8217;s Zen Software</span>
	</td>
		<td align="right" width="25" class="topbg_r2" valign="top">
		<!-- blank -->
		</td>
	</tr>
</tbody>
</table>
<div id="roomNameContainer"><h1>Logged Off</h1></div>
<div id="content">
    <div id="authblock">
        <p>Sorry, but another person is already logged into chat with the same credentials as you and chat can only support one instance of
        each person running at the same time.</p>
        <p>If you have rectified the problem and would like to return to log back in, please click <a href="../index.php">here</a></p>
    </div>
    <div id="copyright">MB Chat <span id="version"><?php include('../inc/version.inc');?></span> &copy; 2008-2010
        <a href="http://www.chandlerfamily.org.uk">Alan Chandler</a></div>
</div>
<!-- Google Analytics Tracking Code -->
  <script type="text/javascript">
    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(ga);
    })();
  </script>
</body>
</html>
