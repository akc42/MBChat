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
declare(ticks = 1);

define('DATABASE','./data/chat.db');
define('INIT_FILE','./database.sql');
define('SERVER_SOCKET','./data/message.sock');
define('LOCK_WAIT_TIME',10000);
define('TIMEOUT_TIME',60);
define('CONFIG','./data/config.db');
define('CFG_INIT_FILE','./config.sql');
define('MAX_CMD_LENGTH',200); //It can be longer as we will loop until we have it all
define('LOG_FILE','./data/server.log')

class DBError extends Exception {

    function __construct ($message) {
        parent::__construct("<p> $message <br/></p>\n".
	                "<p>Please inform <i>alan@chandlerfamily.org.uk</i> that a database query failed and include the above text.\n".
	                "<br/><br/>Thank You</p>");
	}
    
};

if(!file_exists(CONFIG) ) {
    $db = new SQLite3(CONFIG);
    $db->exec("BEGIN");
    $db->exec(file_get_contents(CFG_INIT_FILE));
    $db->exec("COMMIT");
} else {
    $db = new SQLite3(CONFIG);
}

$user_timeout = db->querySingle("SELECT value FROM parameters WHERE name ='user_timeout'");
$purge_message_interval = db->querySingle("SELECT value FROM parameters WHERE name ='purge_message_interval'");
$wakeup_interval = db->querySingle("SELECT value FROM parameters WHERE name ='wakeup_interval'");
$max_messages = db->querySingle("SELECT value FROM parameters WHERE name ='max_messages'");
$max_time = db->querySingle("SELECT value FROM parameters WHERE name ='max_time'");

if(!file_exists(DATABASE) ) {
    $db = new SQLite3(DATABASE);
    $db->exec("BEGIN");
    $db->exec(file_get_contents(INIT_FILE));
    $db->exec("COMMIT");
} else {
    $db = new SQLite3(DATABASE);
}

$logfp = fopen(LOG_FILE,'a');

log("STARTING SERVER NO ".getmypid());

function log($logmsg) {
    global $logfp;
    fwrite($logfp,$logmsg."\n");
}






function timeout () {
    global $statements,$running,$socket,$db,$blocking;

    begin();
    $statements['purge_log']->bindValue(':interval',time() - $purge_message_interval*86400,SQLITE3_INTEGER);
    $statements['purge_log']->execute();
    $statements['purge_log']->reset();
    
    $statments['timeout']->bindValue(':time',time() - $user_timeout,SQLITE3_INTEGER);
    $result = $statements['timeout']->execute();
    while($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if(is_null($row['permanent'])) {
            $statments['delete_user']->bindValue(':uid',$row['uid'],SQLITE3_INTEGER);
            $statements['delete_user']->execute();
            $statements['delete_user']->reset();
        } else {
            $statements['set_absent']->bindValue(':uid',$row['uid'],SQLITE3_INTEGER);
            $statements['set_absent']->execute();
            $statements['set_ansent']->reset();
        }
        sendLog($row['uid'], $row['name'],$row['role'],"LT",$row['rid'],'');
 
    }
    $result->finalize();
    $statement['timeout']->reset();


    if($db->querySingle("SELECT count(*) FROM users WHERE present = 1") == 0) {
        //time to leave
        $running = false;
        $blocking = false;
        socket_set_nonblock($socket);
        pnctl_alarm(0);  //No more alarms
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


pcntl_signal(SIGALRM,timeout,true);
pcntl_alarm(TIMEOUT_TIME);
socket_bind($socket,SERVER_SOCKET);
socket_listen($socket);
$running = true; 
$blocking = true;
$pending_reads = Array();

//These are all the prepared statements the system uses.
$statements['timeout'] = $db->prepare("SELECT uid, name, role, rid, permanent FROM users WHERE time < :time AND present = 1");
$statements['delete_user'] = $db->prepare("DELETE FROM users WHERE uid = :uid ");
$statements['set_absent'] = $db->prepare("UPDATE users SET present = 0 WHERE uid = :uid ");
$statement['read_log'] = $db->prepare("SELECT lid,time,uid,name,role,rid,type,text FROM log WHERE lid >= :lid");
$statement['new_user'] = $db->prepare("REPLACE INTO users (uid,name,role,moderator, present) VALUES (:uid, :name , :role, :mod, 1)");
$statement['purge_log'] = $db->prepare("DELETE FROM log WHERE time < :interval ");

$maxlid = $db->querySingle("SELECT max(lid) FROM log");


//main loop
while($running) {
    if($new_socket = socket_accept($socket)) {
        //Got one process it
        $read = '';
        while($flag = socket_recv($new_socket,$buf,MAX_CMD_LENGTH,MSG_WAITALL)) {
            if($flag == 0) {
                $flag = false; //client probably went away
                break;
            }
            $read .= $buf;
        }
        if($flag === false) {
            //probably client went away
            socket_close($new_socket);  //close link
        } else {
            $cmd = json_decode($read,true);  //makes an array of the data
            if($cmd['cmd'] == "read") {
                $reader = Array();
                $reader['socket'] = $new_socket;
                $reader['lid'] = $cmd['lid'];
                $pending_reads[] = $reader;  //save reader
            } else {
                begin();
                switch($cmd['cmd']) {
                case 'user':
                    $statements['new_user']->bindValue(':uid',$cmd['uid'],SQLITE3_INTEGER);
                    $statements['new_user']->bindValue(':name',htmlentities($cmd['name'],ENT_QUOTES,'UTF-8',false),SQLITE3_TEXT);
                    $statements['new_user']->bindValue(':role',$cmd['role'],SQLITE3_TEXT);
                    $statements['new_user']->bindValue(':mod',$cmd['mod'],SQLITE3_TEXT);
                    $statements['new_user']->execute();
                    $statements['new_user']->reset();
                    break;
//TODO add in the commands we support
                default:
                    log("Command: ".$cmd['cmd']." :NOT IMPLEMENTED");
                    $message ="Command: ".$cmd['cmd']." :NOT IMPLEMENTED\n";
                    break;
                }
                socket_send($new_socket,$message,$strlen($message));
            }
        } 
    } else {
        //Nothing to do, so
        //a) Send all read requests any new messages (if there are any)
        foreach($pending_reads as $index => $reader) {
            if($maxlid >= $reader['lid']) {
                $statements['read_log']->bindValue(':lid',$reader['lid'],SQLITE_INTEGER);
                $result = $statements['read_log']->execute();
                $donefirst=false;
                $lid=false;
                $message = '{"messages":[';
                while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    if($donefirst) {
                        $message .= ",";
                    }
                    $lid=$row['lid'];
                    $donefirst = true;
                    $message .= '{"lid":'.$row['lid'].',"user" :{"uid":'.$row['uid'].',"name":"'.$row['name'].'","role":"';
                    $message .= $row['role'].'"},"type":"'.$row['type'].'","rid":'.$row['rid'].',"message":"'.$row['text'];
                    $message .= '","time":'.$row['time'].'}';
                }
                $message .= '],"lastlid": '.$lid.'}';
                $result->finalize();
                $statement['read_log']->reset();
                $maxlid = max($maxlid,$lid);
log("READ reply to socket ".$reader['socket']." with lids ".$reader['lid']." to $lid");
                socket_send($reader['socket'],$message,strlen($message));
                unset($panding_reads[$index];
            }
                
        }
        //b) commit the current transaction and go back to blocking mode
        $db->exec("COMMIT");
        $blocking = true;
        socket_set_block($socket);
    }
}


fclose($logfp); //close log file



















class DB {

/* Temp stats 
    private $start;
    private $retries; */

    private $cf;
    private $db;
    private $statements;
    private $sql;
    private $socket;
    private $rollback;
    
    
    function __construct() {
        
/*      $this->start = microtime(true);
        $this->retries = 0; */
        if(!file_exists(CONFIG) ) {
            $this->cf = new SQLite3(CONFIG);
            $this->begin($this->cf);
            if(!$this->cf->exec(file_get_contents('./config.sql'))) {
                echo "CONFIG Setup Failed: ".$db->lastErrorMsg();
                $this->cf->exec("ROLLBACK");
                die("Can't continue");
            }
            $this-cf->exec("COMMIT");
        } else {
            $this->cf = new SQLite3(CONFIG);
        }

        if(!file_exists(DATABASE) ) {
            $this->db = new SQLite3(DATABASE);
            $this->begin($this->db);
            if(!$this->db->exec(file_get_contents('./database.sql'))) {
                echo "DATABASE Setup Failed: ".$db->lastErrorMsg();
                $this->db->exec("ROLLBACK");
                die("Can't continue");
            }
            $this->db->exec("COMMIT");
        } else {
            $this->db = new SQLite3(DATABASE_NAME);
        }


        
        //We need a socket regardless of whether we are creating the server or are just the client
        $this->socket = socket_create(AF_UNIX,SOCK_STREAM,SOL_TCP);
        socket_bind($this->socket,MSG_PIPE);

        // We NOW NEED to see if we need to become the chat server or not

        if($this->db->exec("BEGIN EXCLUSIVE")) {
            //If we got here we know that at least there is a possibility that there is no chat server
            $this->db->querySingle("SELECT 
            
        }
        //That chat server may not be running yet, so we need to wait before opening the pipe to send to it
        

    function __destruct() {
        $this->db->close();
//        file_put_contents(STATS, "".$this->retries.",".(microtime(true)-$this->start)."\n",FILE_APPEND);
    }

    private function begin($db) {
        while (!@$db->exec("BEGIN IMMEDIATE")) {
            if($db->lastErrorCode() != SQLITE_BUSY) {
                throw new DBError("In trying to BEGIN IMMEDIATE got Database Error:".$this->db->lastErrorMsg());
            }
//            $this->retries++;
            usleep(LOCK_WAIT_TIME);
        }
    }
    
    private function checkBusy ($sql) {
        if($this->db->lastErrorCode() != SQLITE_BUSY) {
            throw new DBError("SQL Statement: $sql <BR/>Database Error:".$this->db->lastErrorMsg());
        }
        $this->retries++;
        usleep(LOCK_WAIT_TIME);
    }
    
    

    function transact() {

        $return = false;
        $this->rollback = false;
        $this->begin();  //gets exclusive lock on database - so
        $return = $this->doWork();

        if(!$this->rollback)  $this->db->exec("COMMIT");
        $this->rollback = false;
                    

        return $return;
    }
    
    protected function doWork() {
        return true;  //This should be overwridden
    }
    
    
    function rollBack() {
        $this->rollback = true;
        $this->db->exec("ROLLBACK");
    }
    
    
    function getValue($sql) {
        while(! $return = $this->db->querySingle($sql) ) {
            $this->checkBusy($sql);
        } 
        return $return;
    }
    
    function getParam($name) {
        return $this->getValue("SELECT value FROM parameters WHERE name = '".$name."';");
    }

    function getRow($sql,$maybezero = false) {
        while(!$row = $this->db->querySingle($sql,true)) {
            if($maybezero) return false;
            $this->checkBusy($sql);
        }
        return $row;
    }

    function bindText($name,$key,$value) {
        $this->statements[$name]->bindValue(":".$key,htmlentities($value,ENT_QUOTES,'UTF-8',false),SQLITE3_TEXT);
    }
    
    function bindInt($name,$key, $value) {
        $this->statements[$name]->bindValue(":".$key,$value,SQLITE3_INTEGER);
    }
    
    function bindNull($name,$key) {
        $this->statements[$name]->bindValue(":".$key,null,SQLITE3_NULL);
    }
    
    function bindChars($name,$key,$value) {
        $this->statements[$name]->bindValue(":".$key,$value,SQLITE3_TEXT);
    }
    
    function post($name) {
        while (!($this->statements[$name]->execute())) {
            $this->checkBusy($this->sql[$name]);
        }
        $this->statements[$name]->reset();
        return $this->db->lastInsertRowID();
    }
    
    function query($name) {
        while (!($result = $this->statements[$name]->execute())) {
            $this->checkBusy($this->sql[$name]);
        }
        $this->statements[$name]->reset();
        return $result;
    }
    function fetch($result) {
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    function free ($result) {
        $result->finalize();
    }
}
?>
