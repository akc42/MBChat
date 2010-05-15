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

// Link to SMF forum as this is only for logged in members
// Show all errors:
error_reporting(E_ALL);

require_once('../inc/client.inc');
require_once(DATA_DIR.'private.inc');

if (!cs_tcheck(REMOTE_KEY,$_POST['pass']) ) cs_forbidden();
//we can assume we have a valid user
if(cs_is_server_running()) {  //we don't want to start the server just to find out it wasn't running and no one is in chat
    $count = cs_query('count');
    echo $count['count'];
} else {
    echo 0;  
}





