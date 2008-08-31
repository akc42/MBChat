<?php
if (file_exists('VERSION.txt')) {
	echo file_get_contents('VERSION.txt');
} else {
	echo '(No Version)';
}
?> 
 
