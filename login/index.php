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

/* This version is modified from the base to support remote authentication as opposed to local */


error_reporting(E_ALL);
require_once('../inc/client.inc');
require_once(DATA_DIR.'private.inc');

if (!(isset($_POST['user']) && cs_tcheck(REMOTE_KEY,$_POST['pass'])) )  cs_forbidden();

$username = $_POST['user'];

if ($username == '$$$') {
    if (!cs_tcheck(REMOTE_KEY,$_POST['pass'])) cs_forbidden();

    if(isset($_POST['trial'])) {
        echo '{"status":true,"trial":"'.bcpowmod($_POST['trial'],RSA_PRIVATE_KEY,RSA_MODULUS).'"}';
    }
} else if ($username == '$$#') {

    echo '{"status":true,"comment": "external authentication will finish the job"}';
}
