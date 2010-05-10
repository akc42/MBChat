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

error_reporting(E_ALL);


require_once('../inc/client.inc');

$uid = $_POST['uid'];
$q = cs_query('login',$_POST['name'],$_POST['role'],$_POST['mod'],$_POST['cap'],$_POST['msg']); //log on!.
if($q['status']) {
    $q['key'] = bcpowmod($q['key'],$_POST['e'],$_POST['n']);
    if(isset($q['des'])) $q['des'] = bcpowmod($q['des'],$_POST['e'],$_POST['n']);
}
echo json_encode($q);    

