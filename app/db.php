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
    private $in_t;
    
    
    function __construct($statements) {
        $this->start = microtime(true);
        $this->retries = 0;
        $this->db = new SQLite3(DATABASE_NAME);
        $this->sql = $statements;
        $this->in_t = false;
        $this->statements = Array();
        foreach ($statements as $key => $statement) {
            $this->statements[$key] = $this->statementPrepare($statement);
        }
    }

    function __destruct() {
        $this->db->close();
        file_put_contents(STATS, "".$this->retries.",".(microtime(true)-$this->start)."\n",FILE_APPEND);
    }
    
    private function statementPrepare ($statement) {
        $return = 0;
        while(!($return = $this->db->prepare($statement))) {
            if($this->db->lastErrorCode() != SQLITE_BUSY) {
                throw new DBError("Preparing Statement: $tatement <BR/>\nDatabase Error:".$this->db->lastErrorMsg());
            }
            $this->retries++;
            usleep(LOCK_WAIT_TIME);
        }
        return $return;
    }

    function transact() {
        $this->in_t = true;
        $return = false;
        do {

                try {
                    if(!$this->db->exec("BEGIN")) throw new DBCheck("BEGIN");

                    $return = $this->doWork();
                    
                    while (!$this->db->exec("COMMIT")) {
                        if($this->db->lastErrorCode() != SQLITE_BUSY) {
                            throw new DBError();
                        }
                        $this->retries++;
                        usleep(LOCK_WAIT_TIME);
                    }

                    break;
                } catch(DBCheck $e) {
                    if($this->db->lastErrorCode() != SQLITE_BUSY) {
                        throw new DBError("SQL Statement:".$e->getMessage()>"<BR/>Database Error:".$this->db->lastErrorMsg());
                    }
                    usleep(LOCK_WAIT_TIME);
                    $this->retries++;
                    while(!$this->db->exec("ROLLBACK;")) {
                        if($this->db->lastErrorCode() != SQLITE_BUSY) {
                            throw new DBError();
                        }
                        $this->retries++;
                        usleep(LOCK_WAIT_TIME);
                    }
                } catch(DBRollBack $e) {
                    $this->db->exec("ROLLBACK;");
                    break;
                }
                // we are about to go round again (we only get here if a busy exception occurred) so reset statements
                $this->begin->reset();
                foreach($this->statements as $statement) {
                    $statement->reset();
                }
                
        } while(true);
        $this->in_t = false;
        return $return;
    }
    
    protected function doWork() {
        return true;  //This should be overwridden
    }
    
    
    function rollBack() {
        throw new DBRollBack();
    }
    
    
    function getValue($sql) {
        if($return = $this->db->querySingle($sql)) return $return;
        throw new DBCheck($sql); 
    }
    
    function getParam($name) {
        $return = false;
        do {
            try {
                $return = $this->getValue("SELECT value FROM parameters WHERE name = '".$name."';");
                break;
            } catch (DBCheck $e) {
                $this->checkBusy();
            }
        } while(true) ;
        return $return;
    }

    function checkBusy () {
        if($this->in_t) throw new DBCheck($e); //We are already in a transaction so let it handle the rollback/retry
        $this->db->exec("ROLLBACK;");
        if($this->db->lastErrorCode() != SQLITE_BUSY) {
            throw new DBError("SQL Statement:".$e->getMessage()>"<BR/>Database Error:".$this->db->lastErrorMsg());
        }
        $this->retries++;
        usleep(LOCK_WAIT_TIME);
    }
    
    function getRow($sql,$maybezero = false) {
        if($row = $this->db->querySingle($sql,true)) return $row;
        if($maybezero) return false;
        throw new DBCheck($sql); 
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
        if(!($this->statements[$name]->execute())) throw new DBCheck($this->sql[$name]);
        $this->statements[$name]->reset();
        return $this->db->lastInsertRowID();
    }
    
    function query($name) {
        if(!($result = $this->statements[$name]->execute())) throw new DBCheck($this->sql[$name]);
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
