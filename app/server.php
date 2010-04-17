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



//This is a convenient place to force everything we output to not be cached 
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

define('DATABASE_NAME','./data/chat.db');
define('STATS','/home/alan/dev/chat/app/data/stats.txt');
define('LOCK_WAIT_TIME',rand(1000,10000));


class DBError extends Exception {

    function __construct ($message) {
        parent::__construct("<p> $messsage <br/></p>\n".
	                "<p>Please inform <i>alan@chandlerfamily.org.uk</i> that a database query failed and include the above text.\n".
	                "<br/><br/>Thank You</p>");
	}
    
};
class DBCheck extends Exception {};
class DBRollBack extends Exception {};

class DB {

/* Tem stats */
    private $start;
    private $retries;

    
    private $db;
    private $statements;
    private $sql;

    private $rollback;
    
    
    function __construct($statements) {
        $this->start = microtime(true);
        $this->retries = 0;
        $this->db = new SQLite3(DATABASE_NAME);
        $this->sql = $statements;

        $this->statements = Array();
        $this->begin();
        foreach ($statements as $key => $statement) {
            $this->statements[$key] = $this->db->prepare($statement);
        }
        $this->db->exec("COMMIT");
    }

    function __destruct() {
        $this->db->close();
//        file_put_contents(STATS, "".$this->retries.",".(microtime(true)-$this->start)."\n",FILE_APPEND);
    }

    private function begin() {
        while (!@$this->db->exec("BEGIN EXCLUSIVE")) {
            if($this->db->lastErrorCode() != SQLITE_BUSY) {
                throw new DBError("In trying to BEGIN EXCLUSIVE got Database Error:".$this->db->lastErrorMsg());
            }
            $this->retries++;
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
