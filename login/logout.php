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

function page_title() {
	echo "Logged Off";
}
function head_content() {
?>	<style type="text/css">
	.textual {
		margin:20px;
		border:3px solid black;
		background-color:#e0e0e0;
		color:black;
		padding:20px;
		font-family:Verdana, Arial, Helvetica, sans-serif;
		font-size:10pt;
	}
	</style>
<?php 	
}
function content_title() {
	echo "Logged Off";
}

function menu_items() {
}

function main_content() {
?><div class="textual"><div id="authblock">
        <p>Sorry, but another person is already logged into chat with the same credentials as you and chat can only support one instance of
        each person running at the same time.</p>
        <p>If you have rectified the problem and would like to return to try again, please click <a href="../index.php">here</a></p>
    </div>
    </div>
 <?php 
}

function foot_content() {
 ?>    <div id="copyright">MB Chat <span id="version"><?php include('../inc/version.inc');?></span> &copy; 2008-2010
        <a href="http://www.chandlerfamily.org.uk">Alan Chandler</a></div>
<?php
 }
 require_once($_SERVER['DOCUMENT_ROOT'].'/inc/template.inc');
