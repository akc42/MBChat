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
include('./url.inc');
include('./public.inc');

pcntl_signal(SIGALRM,"timeout"); //setup communications timer

function timeout($signal) {
    echo 0;
    exit;
}
declare(ticks = 1);
pcntl_alarm(10);

$t = ceil(time()/300)*300;

$data = array('pass1' => md5(REMOTE_KEY.sprintf("%010u",$t)),'pass2'=> md5(REMOTE_KEY.sprintf("%010u",$t+300)));
echo do_post_request(SERVER_LOCATION."login/count.php",$data );




