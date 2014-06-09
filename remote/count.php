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

//If I was really concerned, I would include the following - but its only a count, so lets not worry too much
/*
require_once(dirname(__FILE__).'/../forum/SSI.php');
//If not logged in to the forum, not allowed to do this
if($user_info['is_guest']) {
    header("Location : /chat.php");
    exit;
}
*/

include('./public.inc');

define('SERVER_LOCATION','http://chat.melindasbackups.com/login/count.php');   //Where the chat server


$data = array('pass' => md5(REMOTE_KEY));


$opts = array('http' =>
    array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query($data)
    )
);

$context  = stream_context_create($opts);

echo  @file_get_contents(SERVER_LOCATION, false, $context,-1,50);

