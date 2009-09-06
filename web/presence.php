<?php
if(!(isset($_POST['user']) && isset($_POST['password'])))
	die('Presence-Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
define ('MBC',1);   //defined so we can control access to some of the files.
require_once('db.php');

dbQuery('UPDATE users SET time = NOW() WHERE uid ='.dbMakeSafe($uid).';');  //Mark me as being active
include('timeout.php');		//Timeout inactive users 


   
define('MBCHAT_PIPE_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/pipes/');

if(file_get_contents(MBCHAT_PIPE_PATH.'time.txt') + 600 < time()) {
    $dh = opendir(MBCHAT_PIPE_PATH);
    if ($dh = opendir(MBCHAT_PIPE_PATH)) {
        while (($file = readdir($dh)) !== false) {
            if (filetype(MBCHAT_PIPE_PATH.$file) == 'fifo') {
                $writer=fopen (MBCHAT_PIPE_PATH.$file,'r+');
                fclose($writer);  //should cause an immediate EOF ON EVERY READ
            }
        }
        closedir($dh);
    }
    file_put_contents(MBCHAT_PIPE_PATH.'time.txt', ''.time());
}


echo '{"Status" : "OK"}' ;
?>
