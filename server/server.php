#!/usr/bin/php
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
error_reporting(E_ALL);

if($argc != 3) die("Chat Server usage wrong\n");
$datadir = $argv[1];
if(!is_dir($datadir)) die("Data Directory is missing\n");
if(!is_writable($datadir)) die("Data Directory is not writeable\n");

//ensure there is a slash at the end of the directory name
if(strpos($datadir,'/',strlen($datadir)-1) === false) {
    $datadir .="/";
}

define('DATA_DIR',$datadir);  //Should be outside of web space

define('DATABASE',DATA_DIR.'chat.db');
define('INIT_FILE',DATA_DIR.'database.sql');


define('MAX_CMD_LENGTH',200); //It can be longer as we will loop until we have it all
define('LOG_FILE',DATA_DIR.'server.log');
define('SERVER_LOCK',DATA_DIR.'server.lock');
define('SERVER_RUNNING',DATA_DIR.'server.run');
define('SERVER_SOCKET',DATA_DIR.'message.sock');
define('SERVER_KEY',DATA_DIR.'server.key');

$handle = fopen(SERVER_LOCK,'w+');
flock($handle,LOCK_EX);


if(file_exists(SERVER_RUNNING)) {
        file_put_contents(SERVER_RUNNING,''.time());
        flock($handle, LOCK_UN); //Aready running
        fclose($handle);
        exit(0);
}
/*  We generate the key used in UID to PASSPHRASE conversion - but we need to make it rapidly availble
    to the authorization routines, so the easiest thing to do is write it to a file
    We calculate it before the fork so we still have the variable $key available to the server, whilst
    allow us to write it into a file in the parent before it releases the lock */
$m1 = rand(0,9999);
$m2 = rand(0,9999);
$key = bcmod(bcadd(strval($m2),bcmul(strval($m1),"10000")),$argv[2]);

if( $pid = pcntl_fork() != 0) {
    if($pid < 0) {
        flock($handle, LOCK_UN);
        fclose($handle);
        die("Cannot start Server");
    }
    file_put_contents(SERVER_KEY,$key);
    //I am the parent
    file_put_contents(SERVER_RUNNING,''.time());
    flock($handle, LOCK_UN);
    fclose($handle);
    usleep(50000);
    exit(0);
}
posix_setsid();
fclose($handle);

fclose(STDIN);  // Close all of the standard
fclose(STDOUT); // file descriptors as we
fclose(STDERR); // are running as a daemon.
if(pcntl_fork()) {
    exit();
}




function logger($logmsg) {
    global $logfp;
    fwrite($logfp,date("d-M-Y H:i:s")." S".getmypid().": $logmsg\n");
}

function sig_term ($signal) {
        //time to leave
    throw new Exception("User Requested Shutdown");
}

function timeout ($signal) {
    global $statements,$running,$socket,$db,$blocking,$purge_message_interval,$user_timeout,$tick_interval,$check_ticks,$ticks;
    pcntl_alarm($tick_interval); //Setup to timeout again

    if($running) {
        if($ticks-- < 0) {
            $ticks = $check_ticks;

            if($blocking) $db->exec("BEGIN"); //Only inside a transaction already if not blocking
            $s = $statements['purge_log'];
            $s->bindValue(':interval',time() - $purge_message_interval*86400,SQLITE3_INTEGER);
            $s->execute();
            $s->reset();

            $t = $statements['timeout'];
            $t->bindValue(':time',time() - $user_timeout,SQLITE3_INTEGER);
            $result = $t->execute();
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                if(is_null($row['permanent'])) {
                    $s = $statements['delete_user'];
                } else {
                    $s = $statements['set_absent'];
                }
                $s->bindValue(':uid',$row['uid'],SQLITE3_INTEGER);
                $s->execute();
                $s->reset();
                
                sendLog($row['uid'], $row['name'],$row['role'],"LT",$row['rid'],(is_null($row['permanent']))?'guest':'permanent');
         
            }
            $result->finalize();
            $t->reset();

            $handle = fopen(SERVER_LOCK,'r+');
            flock($handle,LOCK_EX);

            if($db->querySingle("SELECT count(*) FROM users WHERE present = 1") == 0
            // It could be that a request to start the server occurred just before we decided to shut down.  
            //If a request occurred in the last two seconds then don't, as a new command will come shortly (if it hasn't already)
                                        && file_get_contents(SERVER_RUNNING) < time() -2)) {

                unlink(SERVER_RUNNING);
                flock($handle, LOCK_UN);
                fclose($handle);
               //Time to leave
                $running = false;
                pcntl_alarm(0);  //No more alarms
                socket_shutdown($socket,1);  //stop any writing to the socket
                socket_close($socket); //this will block until reading has finished  
                unlink(SERVER_SOCKET);           
            } else {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
            if($blocking) $db->exec("COMMIT");
        } 

    }
}

function begin() {
    global $socket,$blocking,$db;
    if($blocking) { //If we are not blocking, then we must already have done the begin, so skip it
        $blocking = false;
        socket_set_nonblock($socket);
        $db->exec("BEGIN"); //We do as much as we can inside a transaction until a non-blocked listen returns nothing to do
    }
}

function sendLog ($uid,$name,$role,$type,$rid,$text) {
    global $statements,$db,$maxlid;
    $l = $statements['logger'];
    $l->bindValue(':uid',$uid,SQLITE3_INTEGER);
    $l->bindValue(':name',htmlentities($name,ENT_QUOTES,'UTF-8',false),SQLITE3_TEXT);
    $l->bindValue(':role',$role,SQLITE3_TEXT);
    $l->bindValue(':type',$type,SQLITE3_TEXT);
    $l->bindValue(':rid',$rid,SQLITE3_INTEGER);
    $l->bindValue(':text',htmlentities($text,ENT_QUOTES,'UTF-8',false),SQLITE3_TEXT);
    $l->execute();
    $lid = $db->lastInsertRowID();
    $l->reset();        

    $maxlid = max($maxlid,$lid);
    return $lid;
}

function markActive($uid) {
    global $statements;
    $s = $statements['active'];
    $s->bindValue(':uid',$uid,SQLITE3_INTEGER);
    $s->bindValue(':time',time(),SQLITE3_INTEGER);
    $s->execute();
    $s->reset();
}

//Encrypts text using RSA algorithmn, given base64 encoded PUBLIC key created with modulus 65537
//Code here is 
function encrypt($key,$text) {
/*
    code here is derived from http://stevish.com/rsa-encryption-in-pure-php which is copyright GPL
    the author requests his name is not associated with any modifications, and this comment confirms that
*/

	function blockify($in, $keysize) {
		//Calculate blocksize by keysize
		$b_len = floor($keysize/8);
		return str_split($in, $b_len);
	}
	function txt2num($str) {
		//Turns regular text into a number that can be manipulated by the RSA algorithm
		$result = '0';
		$n = strlen($str);
		do {
			$result = bcadd(bcmul($result, '256'), ord($str{--$n}));
		} while ($n > 0);
		return $result;
	}
	function long_base_convert ($numstring, $frombase, $tobase) {
		//Converts a long integer (passed as a string) to/from any base from 2 to 145
		$chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-+=!@#$%^*(){[}]|:,.?/`~€¶§ÇüéâäàåçêëèïîìÄÅÉæÆôöòûùÿÖÜ¢£¥áíóúñÑªº¿¬œŒ¡«»¯ßµ±÷;<>";
		$fromstring = substr($chars, 0, $frombase);
		$tostring = substr($chars, 0, $tobase);

		$length = strlen($numstring);
		$result = '';
		for ($i = 0; $i < $length; $i++) {
			$number[$i] = strpos($fromstring, $numstring{$i});
		}
		do {
			$divide = 0;
			$newlen = 0;
			for ($i = 0; $i < $length; $i++) {
				$divide = $divide * $frombase + $number[$i];
				if ($divide >= $tobase) {
					$number[$newlen++] = (int)($divide / $tobase);
					$divide = $divide % $tobase;
				} elseif ($newlen > 0) {
					$number[$newlen++] = 0;
				}
			}
			$length = $newlen;
			$result = $tostring{$divide} . $result;
		} while ($newlen != 0);
		return $result;
	}


		$p=base64_decode($key);
		$in = blockify($text, 64);
		$out = '';
		foreach($in as $block) {
			if($block) {
				$cryptblock = bcpowmod(txt2num($block), $p, 65537);
				$out .= long_base_convert($cryptblock, 10, 145) . " ";
			}
		}
		return $out;
	}



}


$logfp = fopen(LOG_FILE,'a');
$running = false; 
declare(ticks = 1);
date_default_timezone_set('Europe/London');
try {

if($socket = socket_create(AF_UNIX,SOCK_STREAM,0)) {
    if(socket_bind($socket,SERVER_SOCKET) && socket_listen($socket) &&
            pcntl_signal(SIGTERM,"sig_term") && pcntl_signal(SIGALRM,"timeout")) {


        logger("STARTING");

        if(!file_exists(DATABASE) ) {
            $db = new SQLite3(DATABASE);
            $db->exec(file_get_contents(INIT_FILE));
        } else {
            $db = new SQLite3(DATABASE);
        }

        $user_timeout = $db->querySingle("SELECT value FROM parameters WHERE name ='user_timeout'");
        $purge_message_interval = $db->querySingle("SELECT value FROM parameters WHERE name ='purge_message_interval'");
        $wakeup_interval = $db->querySingle("SELECT value FROM parameters WHERE name ='wakeup_interval'");
        $max_messages = $db->querySingle("SELECT value FROM parameters WHERE name ='max_messages'");
        $max_time = $db->querySingle("SELECT value FROM parameters WHERE name ='max_time'");
        $tick_interval = $db->querySingle("SELECT value FROM parameters WHERE name ='tick_interval'");
        $check_ticks = $db->querySingle("SELECT value FROM parameters WHERE name ='check_ticks'");

        $running = true;
        $ticks = $check_ticks;
        pcntl_alarm($tick_interval);
        $blocking = true;
        $pending_reads = Array();

//These are all the prepared statements the system uses.

//Timeout users and purge log
$statements['timeout'] = $db->prepare("SELECT uid, name, role, rid, permanent FROM users WHERE time < :time AND present = 1");
$statements['delete_user'] = $db->prepare("DELETE FROM users WHERE uid = :uid ");
$statements['set_absent'] = $db->prepare("UPDATE users SET present = 0 WHERE uid = :uid ");
$statements['read_log'] = $db->prepare("SELECT lid,time,uid,name,role,rid,type,text FROM log WHERE lid >= :lid ORDER BY lid ASC");
$statements['new_user'] = $db->prepare("INSERT INTO users (uid,name,role,moderator, present) VALUES (:uid, :name , :role, :mod, 1)");
$statements['old_user'] = $db->prepare("UPDATE users SET name = :name , role = :role , moderator = :mod , present = 1 , time = :time 
                                            WHERE uid = :uid");
$statements['purge_log'] = $db->prepare("DELETE FROM log WHERE time < :interval ");

//Sendlog
$statements['logger'] = $db->prepare("INSERT INTO log (uid,name,role,type,rid,text) VALUES (:uid,:name,:role,:type,:rid,:text)");

//Signin
$statements['users'] = $db->prepare("SELECT * FROM users WHERE name = :permanent OR name = :guest ");
$statements['new'] = $db->prepare("INSERT INTO users (name,groups) VALUES ( :name , :groups)");

//Presence
$statements['active'] = $db->prepare("UPDATE users SET time = :time WHERE uid = :uid ");

//Online
$statements['online'] = $db->prepare("SELECT uid, name, role, question,private AS wid 
                                        FROM users WHERE rid = :rid AND present = 1 ORDER BY time ASC");

//Room Entry
$statements['room_msg_start'] = $db->prepare("SELECT lid  FROM log LEFT JOIN participant ON participant.wid = rid 
                                                WHERE ( (participant.uid = :uid AND type = 'WH' ) OR rid = :rid) AND 
                                                log.time > :time ORDER BY lid DESC LIMIT :max ");

$statements['room_msgs'] = $db->prepare("SELECT lid, time, type, rid, log.uid AS uid , name, role, text  
                                            FROM log LEFT JOIN participant ON participant.wid = rid
                                            WHERE ( (participant.uid = :uid  AND type = 'WH' ) OR rid = :rid ) 
                                            AND lid >= :lid ORDER BY lid ASC");
$statements['enter'] = $db->prepare("UPDATE users SET rid = :rid , time = :time, role = :role, moderator = :mod WHERE uid = :uid ");
//Room Exit
$statements['room_whi_start'] = $db->prepare("SELECT lid  FROM log  JOIN participant ON participant.wid = rid 
                                            WHERE participant.uid = :uid AND type = 'WH' AND log.time > :time 
                                            ORDER BY lid DESC LIMIT :max ");
$statements['room_whis'] = $db->prepare("SELECT lid, time, type, rid, log.uid AS uid , name, role, text  
                                        FROM log JOIN participant ON participant.wid = rid WHERE participant.uid = :uid 
                                        AND type = 'WH' AND lid >= :lid ");
$statements['exit'] = $db->prepare("UPDATE users SET rid = 0, time = :time, role = :role , moderator = :mod WHERE uid = :uid ");

//Message
$statements['question'] = $db->prepare("UPDATE users SET time = :time, question = :q , rid = :rid WHERE uid = :uid ");
//Managing Moderation
$statements['demote'] = $db->prepare("UPDATE users SET role = :role , moderator = 'N', time = :time WHERE uid = :uid ");
$statements['promote'] = $db->prepare("UPDATE users SET role = 'M',  moderator = :mod, time = :time, question = NULL WHERE uid = :uid ");
$statements['release'] = $db->prepare("UPDATE users SET question = NULL WHERE uid = :uid ");
//Whispering
$statements['join'] = $db->prepare("INSERT INTO participant (wid,uid) VALUES (:wid, :uid)");
$statements['leave'] = $db->prepare("DELETE FROM participant WHERE uid = :uid AND wid = :wid ");
$statements['seq'] = $db->prepare("UPDATE wid_sequence SET value = value + 1 ");
$statements['new_whi'] = $db->prepare("INSERT into participant (wid,uid) VALUES (:wid,:uid)");
$statements['priv_room'] = $db->prepare("UPDATE users SET time = :time, private = :wid WHERE uid = :uid ");


//Print/Log
$statements['getlog'] = $db->prepare("SELECT lid, time AS utime, type, rid, uid , name, role, text  FROM log
                                         WHERE time > :start AND time < :finish AND rid = :rid ORDER BY lid ");

        $maxlid = $db->querySingle("SELECT max(lid) FROM log");
        $minlid = $maxlid;

    } else {
        throw new Exception("Failed to bind to socket" ); 
    }
} else {
    throw new Exception("Failed to get socket"); 
}

/*
    We are going to generate a key for use in the UID->Password relationship.  The relationship we
    use is $password = md5("U".$uid.$key); which makes it simple for all chat client routines to verify
    the digest authentication used. We generate $key and pass it back to the client encrypted by a Public
    key of a Private/Public RSA key pair generated by the client as it starts up.  For this reason, to prevent the 
    client taking too long generating the key, we need something relatively short, so we are only generating a 6 character key
*/

$password_key = ''
;
for ( $i=0; $i<6; $i++ ){
	$n = rand(48,109);
	if($n > 57) $n += 7;
	if($n>90) $n+= 6;
	$password_key .= chr($n);
}

/*
    If we are going to encrypt messages, we need a key to do this.  For super protection, the key will NOT
    be stored in the same database as the message.  This has the limitation that it is harder to recover the 
    messages and the key will still need to be recovered from somewhere (another database).  So it is possible for
    us to generate the key and store it in the database.  This code does that if that is the option chosen
*/

$encrpyt_messages = $db->querySingle("SELECT value FROM parameters WHERE name = 'encrypt_messages'");

if($encrpyt_messages == "yes") {
    $encrypt_key_name = $db->querySingle("SELECT value FROM parameters WHERE name = 'encrypt_key_name'");
    if($encrypt_key_name != 'x') {
        //See if already stored
        if ( $db->querySingle("SELECT count(*) FROM parameters WHERE name = '$encrypt_key_name'") == 0 ) {
            $aes_key='';
            for ($i=0; $i<16; $i++ ){
                $aes_key .= rand(0,255);
            }
            $aes_key = base64_encode( $aes_key );
            $db->exec("INSERT INTO parameters (name,value) VALUES('$encrypt_key_name','$aes_key')");
        else {
            $aes_key = $db->querySingle("SELECT value FROM parameters WHERE name = '$encrypt_key_name'");
        }
    }
}



//main loop
while($running) {

    if($new_socket = @socket_accept($socket)) {

        //Got one process it
        if($read = socket_read($new_socket,MAX_CMD_LENGTH,PHP_NORMAL_READ)) {

            $cmd = json_decode($read,true);  //makes an array of the data

            if($cmd['cmd'] == "read") {
                if(!($lid = $cmd['params'][0]) ) $lid = $maxlid;
                $reader = Array();
                $reader['socket'] = $new_socket;
                $reader['lid'] = $lid;
                $pending_reads[] = $reader;  //save reader
                $minlid = min ($minlid,$lid);
                if($minlid < $maxlid) begin();
            } else {
                begin();
                switch($cmd['cmd']) {
                case 'count':
                    $message = '{"status":'.$db->querySingle("SELECT count(*) FROM users WHERE present = 1").'}';
                    break;
                case 'check':
                    $name = htmlentities($cmd['params'][0],ENT_QUOTES,'UTF-8',false);
                    $row = $db->querySingle("SELECT * FROM users WHERE name = '$name'");
                    if($row) {
                        $message = '{"status":true,"user":'.json_encode($row).',"realm":"';
                        $message .= $db->querySingle("SELECT value FROM parameters WHERE name = 'realm'");
                        $message .= '","key":"';
                        $message .= $db->querySingle("SELECT value FROM parameters WHERE name = 'auth_key'");
                        $message .= '"}';
                    } else {
                        $message = '{"status":false}';
                    }
                    break;
                case 'user':
                    $no = $db->querySingle("SELECT count(*) FROM users WHERE uid = '".$cmd['params'][0]."'");
                    if($no == 0) {
                        $u = $statements['new_user'];
                    } else {
                        $u = $statements['old_user'];
                    }
                    $u->bindValue(':uid',$cmd['params'][0],SQLITE3_INTEGER);
                    $u->bindValue(':name',htmlentities($cmd['params'][1],ENT_QUOTES,'UTF-8',false),SQLITE3_TEXT);
                    $u->bindValue(':role',$cmd['params'][2],SQLITE3_TEXT);
                    $u->bindValue(':mod',$cmd['params'][3],SQLITE3_TEXT);
                    $u->bindValue(':time',time(),SQLITE3_INTEGER);
                    $u->execute();
                    $u->reset();
                    $message = '{"status":true}';
                    break;
                case 'rooms':
                    $result = $db->query("SELECT * FROM rooms");
                    $message = '{"rows":[';
                    $df=false;
                    while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        if($df) {
                            $message .= ",";
                        }
                        $df = true;
                        $message .= json_encode($row);
                    }
                    $message .= ']}';
                    $result->finalize();
                    break;
                case 'param' :
                    $message = '{"value": "'.$db->querySingle("SELECT value FROM parameters WHERE name ='".$cmd['params'][0]."'").'"}';
                    break;
                case 'signin':
                    $name = htmlentities($cmd['params'][0],ENT_QUOTES,'UTF-8',false);
                    $u = $statements['users'];
                    $u->bindValue(':permanent',$name,SQLITE3_TEXT);
                    $u->bindValue(':guest',$name." (G)",SQLITE3_TEXT);
                    $result = $u->execute();
                    $row = $result->fetchArray(SQLITE3_ASSOC);
                    $u->reset();
                    if($row && $row['present'] == '0' && (is_null($row['permanent']) || $row['permanent'] == md5($cmd['params'][1]))) {
        // We are in the database, not present and either a non permenant entry or a permenant entry with the correct password
                        $gp = $row['groups'];       
                        $uid = $row['uid'];
                        $name = $row['name'];
                        $role = $row['role'];
                        $mod = $row['moderator'];
                    } else {
                        $name = $name." (G)";
                        $role = "R";
                        $mod = "N";
                        $whisperer = "true";
                        $gp = "12".(($cmd['params'][2] == 'lite')?'_22':''); 
                        $u = $statements['new'];
                        $u->bindValue(':name',$name,SQLITE3_TEXT);
                        $u->bindValue(':groups',$gp,SQLITE3_TEXT);
                        $u->execute();
                        $uid = $db->lastInsertRowID();
                        $u->reset();
                    }
                    $message = '{"rows":{"uid":'.$uid.',"name":"'.$name.'","role":"'.$role.'","mod":"'.$mod.'","groups":"'.$gp.'"}}';
                    break;
                case 'login':
                    markActive($cmd['params'][0]);
                    $row = $db->querySingle("SELECT name,role, rid FROM users WHERE uid = ".$cmd['params'][0],true);
                    $lid = sendLog($cmd['params'][0], $row['name'],$row['role'],"LI",$row['rid'],$cmd['params'][1]);
                    $message = '{"Login" : '.$cmd['params'][0].', "lastid" : '.$lid.' }' ;
                    break;
                case 'logout':
                    $row = $db->querySingle("SELECT uid, name, role, rid, permanent FROM users WHERE uid = ".$cmd['params'][0],true);
                    if(is_null($row['permanent'])) {
                        $u = $statements['delete_user'];
                    } else {
                        $u=$statements['set_absent'];
                    }
                    $u->bindValue(':uid',$row['uid'],SQLITE3_INTEGER);
                    $u->execute();
                    $u->reset();
                    sendLog($row['uid'], $row['name'],$row['role'],"LO",$row['rid'],$cmd['params'][1]);
                    $message = '{"status":true}'; 
                    break;
                case 'presence':
                    markActive($cmd['params'][0]);
                    $message = '{"status":true}';
                    break;
                case 'online':
                    $o = $statements['online'];
                    $o->bindValue(':rid',$cmd['params'][0],SQLITE3_INTEGER);
                    $result = $o->execute();
                    $df = false;
                    $message = '{ "lastid":'.$maxlid.', "online":[' ;
                    while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        if($df) {
                            $message .= ",";
                        }
                        $df=true;
                        $message .= json_encode($row);
                    }
                    $o->reset();
                    $result->finalize();
                    $message .= ']}';
                    break;
                case 'enter':
                    $uid = $cmd['params'][0];
                    markActive($uid);
                    $rid = $cmd['params'][1];
                    if($room = $db->querySingle("SELECT rid, name, type FROM rooms WHERE rid = $rid ",true)) { //validate room
                        $user = $db->querySingle("SELECT uid, name, role, moderator, question FROM users WHERE uid = $uid ",true);

	                    if ($room['type'] == 'M'  && $user['moderator'] != 'N') {
	                    //This is a moderated room, and this person is not normal - so swap them into moderated room role
		                    $role = $user['moderator'];
		                    $mod = $user['role'];
	                    } else {
		                    $role = $user['role'];
		                    $mod = $user['moderator'];
	                    }
	                    $name = $user['name'];
	                    $question = $user['question'];
                        $u = $statements['enter'];
                        $u->bindValue(':uid',$uid,SQLITE3_INTEGER);
                        $u->bindValue(':rid',$rid,SQLITE3_INTEGER);
	                    $u->bindValue(':role',$role,SQLITE3_TEXT);
	                    $u->bindValue(':mod',$mod,SQLITE3_TEXT);
	                    $u->bindValue(':time',time(),SQLITE3_INTEGER);
	                    $u->execute();
	                    $u->reset();
	                    sendLog($uid, $name,$role,"RE",$rid,$question);

                        $message = '{"room":'.json_encode($room);



	                    
                    } else {
                        $message = '{"room":{}';
                    }
                    $message .= ',"messages" : [';
                    $u = $statements['room_msg_start'];
                    $u->bindValue(':uid',$uid,SQLITE3_INTEGER);
                    $u->bindValue(':rid',$rid,SQLITE3_INTEGER);
                    $u->bindValue(':time',time() - 60*$max_time,SQLITE3_INTEGER);
                    $u->bindValue(':max',$max_messages,SQLITE3_INTEGER);
                    $result = $u->execute();


                    while($row = $result->fetchArray(SQLITE3_NUM)) {
                        $lid = $row[0];
                    }
                    $result->finalize();
                    $u->reset();
                   
                    //now we know where to start, actually collect the messages to display
                    $u = $statements['room_msgs'];
                    $u->bindValue(':uid',$uid,SQLITE3_INTEGER);
                    $u->bindValue(':rid',$rid,SQLITE3_INTEGER);
                    $u->bindValue(':lid',$lid,SQLITE3_INTEGER);

                    $df = false;
                    $result = $u->execute();

                    while( $row = $result->fetchArray(SQLITE3_ASSOC)) {
                        if($df) {
                            $message .= ",";
                        }
                        $df = true;
                        $user = array();
                        $item = array();
                        $item['lid'] = $row['lid'];
                        $lid = $row['lid'];
                        $item['type'] = $row['type'];
                        $item['rid'] = $row['rid'];
                        $user['uid'] = $row['uid'];
                        $user['name'] = $row['name'];
                        $user['role'] = $row['role'];
                        $item['user'] = $user;
                        $item['time'] = $row['time'];
                        $item['message'] = $row['text'];
                        $message .= json_encode($item);
                    }
                    $result->finalize();
                    $u->reset();
                    if($df) {
                        $message .= '], "lastid" :'.$lid.'}';
                    } else {
                        $message .= ']}';
                    }
                    break;
                case 'exit':
                    $uid = $cmd['params'][0];
                    markActive($uid);
                    if(($rid = $cmd['params'][1]) != 0) {
                        $room = $db->querySingle("SELECT rid, name, type FROM rooms where rid = $rid ",true);
                        $user = $db->querySingle("SELECT uid, name, role, moderator FROM users WHERE uid = $uid ",true);

                        if ($room['type'] == 'M'  && $user['moderator'] != 'N') {
                        //This is a moderated room, and this person is not normal - so swap them out of moderated room role
	                        $role = $user['moderator'];
	                        $mod = $user['role'];
                        } else {
	                        $role = $user['role'];
	                        $mod = $user['moderator'];
                        }
	                    $name = $user['name'];

                        $u = $statements['exit'];
                        $u->bindValue(':uid',$uid,SQLITE3_INTEGER);
	                    $u->bindValue(':role',$role,SQLITE3_TEXT);
	                    $u->bindValue(':mod',$mod,SQLITE3_TEXT);
	                    $u->bindValue(':time',time(),SQLITE3_INTEGER);
	                    $u->execute();
	                    $u->reset();
	                    sendLog($uid, $name,$role,"RX",$rid,'');
                    }
                    $message = '{"messages" : [';
                    $u = $statements['room_whi_start'];
                    $u->bindValue(':uid',$uid,SQLITE3_INTEGER);
                    $u->bindValue(':time',time() - 60*$max_time,SQLITE3_INTEGER);
                    $u->bindValue(':max',$max_messages,SQLITE3_INTEGER);
                    $result = $u->execute();

                    $lid = false;
                    while($row = $result->fetchArray(SQLITE3_NUM)) {
                        $lid = $row[0];
                    }
                    $result->finalize();
                    $u->reset();
                   
                    //now we know where to start, actually collect the messages to display
                    $u = $statements['room_whis'];
                    $u->bindValue(':uid',$uid,SQLITE3_INTEGER);
                    $u->bindValue(':lid',$lid,SQLITE3_INTEGER);

                    $df = false;
                    $result = $u->execute();

                    while( $row = $result->fetchArray(SQLITE3_ASSOC)) {
                        if($df) {
                            $message .= ",";
                        }
                        $df = true;
                        $user = array();
                        $item = array();
                        $item['lid'] = $row['lid'];
                        $lid = $row['lid'];
                        $item['type'] = $row['type'];
                        $item['rid'] = $row['rid'];
                        $user['uid'] = $row['uid'];
                        $user['name'] = $row['name'];
                        $user['role'] = $row['role'];
                        $item['user'] = $user;
                        $item['time'] = $row['time'];
                        $item['message'] = $row['text'];
                        $message .= json_encode($item);
                    }
                    $result->finalize();
                    $u->reset();
                    $message .= '], "lastid" :'.(($lid && $lid < $maxlid)?$lid:$maxlid).'}';
                    break;
               case 'msg':
                    $uid = $cmd['params'][0];
                    markActive($uid);
                    $rid = $cmd['params'][1];
                    $text = htmlentities(stripslashes($cmd['params'][2]),ENT_QUOTES,false);

                    $row = $db->querySingle("SELECT uid, users.name, role, question, users.rid, type ".
                                        "FROM users LEFT JOIN rooms ON users.rid = rooms.rid WHERE uid=".$uid,true);
    	
	                $role = $row['role'];
	                $type = $row['type'];
	                $u = $statements['question'];
	                $mtype = '' ;
	                if ($type == 'M' && $role != 'M' && $role != 'H' && $role != 'G' && $role != 'S' ) {
	                //we are in a moderated room and not allowed to speak, so we just update the question we want to ask
		                if( $text == '') {
		                    $u->bindValue(':q',null,SQLITE3_NULL);
                			$mtype = "MR";
                		} else {
		                    $u->bindValue(':q',$text,SQLITE3_TEXT);
                		    $mtype = "MQ";
		                    }
	                } else {
		                    $u->bindValue(':q',null,SQLITE3_NULL);
		            //just indicate presence
		                if ($text != '') {  //only insert non blank text - ignore other
		                    $mtype = "ME";
		                }
		            }
                    $u->bindValue(':time',time(),SQLITE3_INTEGER);
                    $u->bindValue(':rid',$rid,SQLITE3_INTEGER);
                    $u->bindValue(':uid',$uid,SQLITE3_INTEGER);
                    $u->execute();
                    $u->reset();
	                if ($mtype != '') {
	                    sendLog($uid, $row['name'],$role,$mtype,$rid,$text);
                    }
                    $message = '{"status":true}'; 
                    break;
                case 'demote':
                    $uid = $cmd['params'][0];
                    markActive($uid);
                    $rid = $cmd['params'][1];
                    $u = $statements['demote'];
                    $user = $db->querySingle("SELECT uid, name, role, rid, moderator FROM users WHERE uid = $uid",true);

                	if ($user['role'] == 'M' && $user['rid'] == $rid ) {
                	    $u->bindValue(':role',$user['moderator'],SQLITE3_TEXT);
                	    $u->bindValue(':uid',$uid,SQLITE3_INTEGER);
                        $u->bindValue(':time',time(),SQLITE3_INTEGER);
                        $u->execute();
                        $u->reset();
                        sendLog($uid, $user['name'],$user['moderator'],"RN",$rid,"");
                    }
                    $message = '{"status":true}';
                    break; 
                case 'promote':
                    markActive($cmd['params'][0]);
                    $puid = $cmd['params'][1];
                    $user = $db->querySingle("SELECT uid, name, role, rid, moderator, question  FROM users WHERE uid = $puid ",true);

	                if ($user['role'] == 'M' || $user['role'] == 'S') {
		                //already someone special 
		                $mod =$user['moderator'];
	                } else {
		                $mod = $user['role'];
	                }
	    
            	    $u = $statements['promote'];
            	    $u->bindValue(':mod',$mod,SQLITE3_TEXT);
            	    $u->bindValue(':uid',$puid,SQLITE3_INTEGER);
                    $u->bindValue(':time',time(),SQLITE3_INTEGER);
                    $u->execute();
                    $u->reset();
	                sendLog($puid,$user['name'],"M","RM",$user['rid'],'');
	                if ($user['question'] != '' ) {
                        sendLog($puid, $user['name'],"M","ME",$user['rid'],$user['question']);	
	                }
                    $message = '{"status":true}';
                    break;
                case 'release':
                    markActive($cmd['params'][0]);
                    $quid = $cmd['params'][1];
                    if($user = $db->querySingle("SELECT uid, name, role, rid, question FROM users WHERE uid = $quid ", true)) {
                        $statements['release']->bindValue(':uid',$quid,SQLITE3_INTEGER);
                        $statements['release']->execute();
                        $statements['release']->reset();
                        sendLog($quid, $user['name'],$user['role'],"ME",$user['rid'],$user['question']);	
                        $message = '{"status":true}';      
                    } else {
                        $message = '{"status":false}';
                    }
                    break; 
                case 'get':
                    $message = '{"whisperers":[';
                    $f = false;
                    $result = $db->exec("SELECT users.uid,name,role FROM participant 
                                            JOIN users ON users.uid = participant.uid WHERE wid = ".$cmd['params'][0]);
                    while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        if($df) {
                            $message .= ",";
                        }
                        $df = true;
                    
	                    $message .= json_encode($row);
                    }
                    $result->finalize();
                    $message .= ']}';
                    break;
                case 'newwhi':
                    $uid = $cmd['params'][0];
                    $wuid = $cmd['params'][1];
                    if($wuser = $db->querySingle("SELECT uid,name,role,moderator FROM users WHERE uid = $wuid") &&
                        $user = $db->querySingle("SELECT uid,name,role,moderator FROM users WHERE uid = $uid")) {
                        markActive($uid);
                        $statements['seq']->execute();
                        $statements['seq']->reset();
                        $wid = $db->querySingle("SELECT value FROM wid_sequence");

                        $u = $statements['new-whi'];
                        $u->bindValue(':wid',$wid,SQLITE3_INTEGER);
                        $u->bindValue(':uid',$wuid,SQLITE3_INTEGER);
                        $u->execute();
                        $u->reset();
                        $u->bindValue(':uid',$uid,SQLITE3_INTEGER);
                        $u->execute();
                        $u->reset();
                        sendLog($wuid, $wuser['name'],$wuser,"WJ",$wid,'');
                        sendLog($uid, $user['name'],$user['role'],"WJ",$wid,'');
                        $message = '{ "wid" :'.$wid.', "user" :'.json_encode($wuser).'}';
                    } else {
                        $message = '{ "wid" : 0 , "user" : 0 }';
                    }
                    break;
                case 'join':
                    $uid = $cmd['params'][0];
                    $wuid = $cmd['params'][1];
                    $wid = $cmd['params'][2];
                    $num = $db->querySingle("SELECT count(*) FROM participant WHERE uid = $uid AND wid = $wid ");
                    if($num != 0 && ($row = $db->querySingle("SELECT name, role FROM users WHERE uid = $wuid ;",true))) {
                        markActive($uid);
                        //I am in this whisper group, so am entitiled to add the new person
                        $u = $statements['join'];
                        $u->bindValue(':wid',$wid,SQLITE3_INTEGER);
                        $u->bindValue(':uid',$wuid,SQLITE3_INTEGER);
                        $u->execute();
                        $u->reset();
                        sendLog($wuid, $row['name'],$row['role'],"WJ",$wid,'');	
                        $message = '{"status":true}';      
                    } else {
                        $message = '{"status":false}';
                    }
                    break;
                case 'leave':
                    $uid = $cmd['params'][0];
                    $wid = $cmd['params'][1];
                    if($row = $db->querySingle("SELECT  name, role FROM users JOIN participant ON users.uid = participant.uid 
                                                    WHERE users.uid = $uid AND wid = $wid ",true)){
                        markActive($uid);
                        $u = $statements['leave'];
                        $u->bindValue(':wid',$wid,SQLITE3_INTEGER);
                        $u->bindValue(':uid',$wuid,SQLITE3_INTEGER);
                        $u->execute();
                        $u->reset();
                        sendLog($uid, $row['name'],$row['role'],"WL",$wid,'');	
                        $message = '{"status":true}';      
                    } else {
                        $message = '{"status":false}';
                    }
                    break;
                case 'private':
                    $uid = $cmd['params'][0];
                    $rid = $cmd['params'][1];
                    $wid = $cmd['params'][2];
                    $p = $statements['priv_room'];
			        $p->bindValue(':time',time(),SQLITE3_INTEGER);
                    if ($wid != 0 ) {
                        $row = $db->querySingle("SELECT participant.uid, users.name, role, wid  FROM participant 
				                        JOIN users ON users.uid = participant.uid WHERE participant.uid =  $uid AND wid = $wid ",true);
			            $p->bindValue(':wid',$wid,SQLITE3_INTEGER);
			            sendLog($uid, $row['name'],$row['role'],"PE",$wid,'');	
                    } else {
                        $row = $db->querySingle("SELECT uid, name, role FROM users WHERE uid = $uid ",true);
			            $p->bindValue(':wid',0,SQLITE3_INTEGER);
			            sendLog($uid, $row['name'],$row['role'],"PX",$rid,'');
		            }
		            $p->execute();
		            $p->reset();
                    $message = '{"status":true}';  
                    break;
                case 'whisper':
                    $uid = $cmd['params'][0];
                    $wid = $cmd['params'][1];
                    $text = htmlentities(stripslashes($cmd['params'][2]),ENT_QUOTES,false);

                    if($row = $db->querySingle("SELECT participant.uid, users.name, role, wid  FROM participant 
                                                LEFT JOIN users ON users.uid = participant.uid WHERE
                                                    participant.uid = $uid AND wid = $wid ",true)) {
                        markActive($uid);
                        
                        if($text != '') sendLog($uid, $row['name'],$role,"WH",$wid,$text);	
                        
                        $message = '{"status":true}';      
                    } else {
                        $message = '{"status":false}';
                    }
                    break;
                case 'getlog':
                    $uid = $cmd['params'][0];
                    markActive($uid);
                    $rid = $cmd['params'][1];
                    $user = $db->querySingle("SELECT name, role FROM user WHERE uid = $uid",true);
                    sendLog($uid, $user['name'],$user['role'],"LH",$rid,'');

                    $l = $statements['getlog'];
	                $l->bindValue(':rid',$rid,SQLITE3_INTEGER);
	                $l->bindValue(':start',$cmd['params'][2],SQLITE3_INTEGER);
	                $l->bindValue(':end',$cmd['params'][3],SQLITE3_INTEGER);
                    $result = $l->execute();

                    $df = false;
                    $message = '{"messages":[';	
                    while( $row = $result->fetchArray(SQLITE3_ASSOC)) {
                        if($df) {
                            $message .= ",";
                        }
                        $df = true;
                        $user = array();
                        $item = array();
                        $item['lid'] = $row['lid'];
                        $item['type'] = $row['type'];
                        $item['rid'] = $row['rid'];
                        $user['uid'] = $row['uid'];
                        $user['name'] = $row['name'];
                        $user['role'] = $row['role'];
                        $item['user'] = $user;
                        $item['time'] = $row['utime'];
                        $item['message'] = $row['text'];
                        $message .= json_encode($item);
                    }
                    $result->finalize();
                    $l->reset();
                    $message .= ']}';
                    break; 
                case 'print':
                    markActive($cmd['params'][0]);
                    $rid = $cmd['params'][1];
                    $sql = 'SELECT time, type, rid, uid , name, role, text  FROM log';
                    $sql .= ' WHERE time > '.$cmd['params'][2].' AND time < '.$cmd['params'][3].' AND rid ';
                    if ($rid == 99) {
	                    $sql .= '> 98 ORDER BY rid,lid ;';

                    } else {
	                    $sql .= '= '.$rid.' ORDER BY lid ;';
                    }
                    $result = $db->query($sql);
                    $message = '{"rows":[';
                    $df=false;
                    while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        if($df) {
                            $message .= ",";
                        }
                        $df = true;
                        $message .= json_encode($row);
                    }
                    $message .= ']}';
                    $result->finalize();
                    break;         
//TODO add in the commands we support
                default:
                    logger("Command: ".$cmd['cmd']." :NOT IMPLEMENTED: Raw Message:$read");
                    $message = '{"status":false,"reason":"Command '.$cmd['cmd'].' NOT IMPLEMENTED"}';
                    break;
                }
                @socket_write($new_socket,$message);
                @socket_close($new_socket);  //close link
            }
        }
       
    } else {
        if(!$blocking) {  
            //Nothing left to do, so finish up by ...
            //a) Send all read requests any new messages (if there are any)

            if($maxlid > $minlid && !empty($pending_reads)) {
                $statements['read_log']->bindValue(':lid',$minlid,SQLITE3_INTEGER);
                $result = $statements['read_log']->execute();
                 $lid=false;

                foreach($pending_reads as &$reader) {
                    $reader['reply'] = '{"messages":[';
                    $reader['df'] = false;
                }
                while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $lid=$row['lid'];

                    $message = '{"lid":'.$row['lid'].',"user" :{"uid":'.$row['uid'].',"name":"'.$row['name'].'","role":"';
                    $message .= $row['role'].'"},"type":"'.$row['type'].'","rid":'.$row['rid'].',"message":"'.$row['text'];
                    $message .= '","time":'.$row['time'].'}';

                    foreach($pending_reads as &$reader) {
                        if($lid >= $reader['lid']) {
                            if($reader['df']) {
                                $reader['reply'] .= ",";
                            }
                            $reader['df'] = true;
                            $reader['reply'] .= $message;
                        }
                    }
                }
                $result->finalize();
                $statements['read_log']->reset();
                if($lid !== false) $maxlid = max($maxlid,$lid);
                foreach($pending_reads as $i => $reader) {
                    if($reader['df']) {              
                        $message = $reader['reply'].'],"lastlid": '.$lid.'}';
                        @socket_write($reader['socket'],$message);
                        @socket_close($reader['socket']);
                        unset($pending_reads[$i]); //reset that pending read - its gone
                    }
                }
            }        
            $minlid = $maxlid;

            //b) commit the current transaction and go back to blocking mode

            $blocking = true;
            $db->exec("COMMIT");
            socket_set_block($socket);
        }
    }
}
} catch (Exception $e) {
    logger("EXCEPTION:".$e->getMessage());
    pcntl_alarm(0); //Alarm off
    socket_shutdown($socket,2);  //stop ALL action
    socket_close($socket); 
    unlink(SERVER_SOCKET);
    unlink(SERVER_RUNNING);           
    $db_>exec("ROLLBACK");
}

logger("Shutting Down");
fclose($logfp); //close log file
exit();

