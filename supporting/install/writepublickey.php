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

function hex2dec($keystring) {
    $set="0123456789abcdef";
    $l = strlen($keystring);
    $result='0';
    for($i=0;$i<$l;$i++) {
        $char=substr($keystring,$i,1);
        $dec = stripos($set,$char);
        $result = bcadd(bcmul($result,"16"),$dec);
    }
    return $result;
}

$res=openssl_pkey_get_private('file://'.dirname(__FILE__).'/newkey.pem');
$key=openssl_pkey_get_details($res);

echo "<?php \n";
echo "define('RSA_EXPONENT',".hex2dec(bin2hex($key['rsa']['e'])).");\n";
echo "define('RSA_MODULUS','".hex2dec(bin2hex($key['rsa']['n']))."');\n";
echo "define('REMOTE_KEY','".$argv[1]."');\n";
echo "define('CHAT_URL','".$argv[2]."');\n";


  




