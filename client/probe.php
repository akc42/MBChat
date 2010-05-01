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

/* check we are called with all the right parameters.  If not, we need to call our initialisation routine */


require_once('../inc/client.inc');

$return = cs_query('validate');
$key = $return['key'];
$realm = $return['realm'];
if(!$header = d_get_header()) {
    d_send($realm,'client/');
    exit;
}
if(!$u = d_authenticate($header,'cs_get_uid')) {
    if(is_null($u)) {
        cs_forbidden();
    } else {
        d_refresh($realm,'client/');
        exit;
    }
}
?><p>Probe</p>

