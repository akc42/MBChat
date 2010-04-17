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
define('PROCESS_COUNT', '1'); //Number of children to initiate, or to increase/decrease number of
define('URL_BASE','http://chat/');

declare(ticks=1);
pcntl_signal(SIGCHLD, "sig_child");
pcntl_signal(SIGUSR1, "inc_child");
pcntl_signal(SIGUSR2, "dec_child");

$children = array();


function start_child() {
    global $children;
    if(($pid = pcntl_fork()) == 0) {
        exit(child_main());
    } else {
        $children[] = $pid;
    }
}

function inc_child () {
    pcntl_signal(SIGUSR1, "inc_child");
    for($i = 0; $i < PROCESS_COUNT; $i++) {
        start_child();
    }
}

function dec_child() {
    global $children;
    pcntl_signal(SIGUSR2, "dec_child");
    for($i = 0; $i < PROCESS_COUNT; $i++) {
       $pid = array_pop($children);
       if($pid) {

            posix_kill($pid,SIGINT);
        }
    }

}

function sig_child($signal) {
    global $children;
    pcntl_signal(SIGCHLD, "sig_child");

    while(($pid = pcntl_wait($status, WNOHANG)) > 0) {

        $children = array_diff($children, array($pid));
    }
}

function sig_reader (){
    exit;
}


inc_child();
while(true) {
    $char = strtolower( trim( `bash -c "read -n 1 ANS ; echo \\\$ANS"` ) );
    if($char == 'q') {
        foreach($children as $child) {
            posix_kill($child,SIGINT);
        }
        echo "Exit Program\n";
        exit;
    } elseif ($char == '=') {
        echo "Increasing children\n";
        inc_child();
        echo "There are now ".count($children)." children\n";
    } elseif ($char == '-') {
        echo "Decreasing children\n";
        dec_child();
        echo "There are now ".count($children)." children\n";
    }
}

$pid;
$data;
function child_main() {
    global $pid,$data;
    if(($pid = pcntl_fork()) == 0) {
       exit(reader_main());
    } else {

        pcntl_signal(SIGCHLD, "sig_reader");
        pcntl_signal(SIGINT,"sig_int");
        $data = Array('user' => $pid, 'password' => sha1("Key".$pid));
        do_post_request('chat.php',array('uid' => $pid, 'pass' => sha1("Key".$pid),'name' => "Test Process $pid",'mod'=>'N',
                                                'role'=>'R','whi'=>'true','ctype'=>'normal','gp'=>'12','test'=> 'true'));
        do_post_request('login.php',array_merge($data,array('mbchat'=>'vTEST','version'=>'1.2.4','build'=>'1','browser' => 'PHPcli','platform'=>'Linux')));
        do_post_request('online.php',array_merge($data,array('rid'=>0)));
        sleep(1);
        do_post_request('online.php',array_merge($data,array('rid'=>1)));

        do_post_request('room.php',array_merge($data,array('rid'=>1)));
        $i = 0;
        while(true) {
            $i++;
            sleep(28);
            do_post_request('message.php',array_merge($data,array('rid'=>1,'text' => "Child $pid sending Message $i" )));
            if($i%3 == 0) do_post_request('presence.php',$data) ;//Presence
        
        }
    }
}
function sig_int() {
    global $pid,$data;
    posix_kill($pid,SIGINT);
    do_post_request('logout.php',array_merge($data,array('mbchat'=>'vTEST','version'=>'1.2.4','build'=>'1','browser' => 'PHPcli','platform'=>'Linux')));
    exit;
}

function reader_main() {
    $pid = getmypid();
    $data = Array('user' => $pid, 'password' => sha1("Key".$pid));

    while(true) {

        do_post_request('read.php',$data);
   }
}    
    
    
function do_post_request($url, $data) {

    $postdata = http_build_query($data);
    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    $context  = stream_context_create($opts);
    @file_get_contents(URL_BASE.$url, false, $context,-1,40);
}
  
?>

