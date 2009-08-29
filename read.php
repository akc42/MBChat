<?php
define('MBCHAT_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/');
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: -1"); // Date in the past
if(!(isset($_POST['user']) && isset($_POST['password']) ))
	die('Poll-Hacking attempt - wrong parameters');
$uid = $_POST['user'];

if ($_POST['password'] != sha1("Key".$uid))
	die('Hacking attempt got: '.$_POST['password'].' expected: '.sha1("Key".$uid));
$lid = $_POST['lid'];


$readpipe=fopen(MBCHAT_PATH."pipes/msg".$uid,'r');
$response=fread($readpipe,400);
fclose($readpipe);

if (strlen($response) > 0 ) {
    echo '{"messages":[' ;
    $messages =  explode('>',$response);
    foreach($messages as $message) {
            echo substr($message,1) ;
    }
    echo ']}';
}
?>
