<?php

/*
Copyright (c) 2005 Paul James
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:
1. Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
3. Neither the name of the Paul James nor the names of its contributors
   may be used to endorse or promote products derived from this software
   without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
SUCH DAMAGE.
*/

/* subsequently modified by Alan Chandler change are Copyright (c) Alan Chandler 
                                  and licenced as defined above */
define('OPAQUE','9aa2841bcbf7e319e28a964162db2081'); //md5('chattering');
define('PRIVATE_KEY','Chandlers ZEN');
define('NONCE_LIFE',43200);


function d_send($realm,$domain) {
    d_refresh($realm,$domain,false);
}


function d_refresh($realm,$domain,$stale = true) {
    global $digest;
    header('WWW-Authenticate: Digest '.
        'realm="'.$realm.'", '.
        'domain="'.$domain.'", '.
        'qop=auth, '.
        'algorithm=MD5, '.
        (($stale)?'stale=true,':'').
        'nonce="'.d_get_nonce().'", '.
        'opaque="'.OPAQUE.'"'
    );
    header('HTTP/1.0 401 Unauthorized');
}

function d_get_header() {
    if (isset($_SERVER['Authorization'])) {
        return $_SERVER['Authorization'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            return $headers['Authorization'];
        }
    }
    return NULL;
}

function d_get_username($header) {
    if (preg_match('/username="([^"]+)"/', $header, $username)) {
        return $username[1];
    }
    return false;
}

function d_authenticate($header,$getPassphrase) {
    if (substr($header, 0, 5) == 'Basic') {
        trigger_error('You are trying to use HTTP Basic authentication but I am expecting HTTP Digest');
        exit;
    }
    if (preg_match('/username="([^"]+)"/', $header, $username) &&
        preg_match('/nonce="([^"]+)"/', $header, $nonce) &&
        preg_match('/response="([^"]+)"/', $header, $response) &&
        preg_match('/opaque="([^"]+)"/', $header, $opaque) &&
        preg_match('/uri="([^"]+)"/', $header, $uri)) {
        
        $username = $username[1];
        $passphrase = $getPassphrase($username);
        $requestURI = $_SERVER['REQUEST_URI'];
        if (strpos($requestURI, '?') !== FALSE) { // hack for IE which does not pass querystring in URI element of Digest string or in response hash
            $requestURI = substr($requestURI, 0, strlen($uri[1]));
        }
        if (
            $passphrase !== false &&
            $opaque[1] == OPAQUE &&
            $uri[1] == $requestURI 
        ) {

//                    if ($this->passwordsHashed) {
                $a1 = $passphrase;
//                    } else {
//                        $a1 = md5($username.':'.$this->getRealm().':'.$passphrase);
//                    }
            $a2 = md5($_SERVER['REQUEST_METHOD'].':'.$requestURI);
            if (
                preg_match('/qop="?([^,\s"]+)/', $authorization, $qop) &&
                preg_match('/nc=([^,\s"]+)/', $authorization, $nc) &&
                preg_match('/cnonce="([^"]+)"/', $authorization, $cnonce)
            ) {
                $expectedResponse = md5($a1.':'.$nonce[1].':'.$nc[1].':'.$cnonce[1].':'.$qop[1].':'.$a2);
            } else {
                $expectedResponse = md5($a1.':'.$nonce[1].':'.$a2);   
            }
            if ($response[1] == $expectedResponse) {
               if($nonce[1] == d_get_nonce() ) {      
                    return $username;
                } else {
                    return false;
                }
            }
        }
    }
    return NULL;
}

function d_send_forbidden() {
    header('HTTP/1.0 403 Forbidden');
}

function d_send_bad_request() {
    header('HTTP/1.0 400 Bad Request');
}

    
function d_get_nonce($nonceLife) {
    $time = ceil(time() / NONCE_LIFE) * NONCE_LIFE;
    return md5(date('Y-m-d H:i', $time).':'.$_SERVER['REMOTE_ADDR'].':'.PRIVATE_KEY);
}




