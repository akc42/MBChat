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

/** HTTP Digest authentication class */
class HTTPDigest
{

    /** The Digest opaque value (any string will do, never sent in plain text over the wire).
     * @var str
     */
    private $opaque = 'HChat';

    /** The authentication realm name.
     * @var str
     */    
    private $realm;
    
    /** The base URL of the application, auth data will be used for all resources under this URL.
     * @var str
     */
    private $baseURL;
    
    /** Are passwords stored as an a1 hash (username:realm:password) rather than plain text.
     * @var str
     */
    private $passwordsHashed;
    
    /** The private key.
     * @var str
     */
    private $privateKey;
    
    /** The life of the nonce value in seconds
     * @var int
     */
    private $nonceLife;

    function __construct($realm,$domain = '/',$nonceLife = 300,$passwordsHashed = true,$key = 'Hartley-Consultants') {
        $this->realm = $realm;
        $this->baseURL = $domain;
        $this->nonceLife = $nonceLife;
        $this->privateKey = $key;
        $this->passwordsHashed = $passwordsHashed;
        if($passwordsHashed && ini_get('safe_mode')) trigger_error("Hashed passwords and safe mode do not work together");
    }

    /** Send HTTP Auth header */
    function refresh($stale = true)
    {
        header('WWW-Authenticate: Digest '.
            'realm="'.$this->realm.'", '.
            'domain="'.$this->baseURL.'", '.
            'qop=auth, '.
            'algorithm=MD5, '.
            (($stale)?'stale=true,':'').
            'nonce="'.$this->getNonce().'", '.
            'opaque="'.$this->getOpaque().'"'
        );
        header('HTTP/1.0 401 Unauthorized');
    }
    
    /** Get the HTTP Auth header
     * @return str
     */
    function getAuthHeader()
    {
        if (isset($_SERVER['Authorization'])) {
            return $_SERVER['Authorization'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                return $headers['Authorization'];
            }
        }
        $this->refresh(false);
        return NULL;
    }

    /** Authenticate the user and return username on success.
     * @param callback function with single parameter, username = should return with passphrase (or false if no user)
     * @return 
     *      'str'   the username
     *      null    invalid user
     *      false   valid user, but the authetication is stale.  Call HTTPDigest::refresh() to ask browser to refresh it.   
     */
    function authenticate($checkpasswordfunction) {
        if(!is_callable($checkpasswordfunction)) {
            trigger_error("Callback function invalid");
            return null;
        };
        $authorization = $this->getAuthHeader();
        if ($authorization) {
            if (substr($authorization, 0, 5) == 'Basic') {
                trigger_error('You are trying to use HTTP Basic authentication but I am expecting HTTP Digest');
                exit;
            }
            if (
                preg_match('/username="([^"]+)"/', $authorization, $username) &&
                preg_match('/nonce="([^"]+)"/', $authorization, $nonce) &&
                preg_match('/response="([^"]+)"/', $authorization, $response) &&
                preg_match('/opaque="([^"]+)"/', $authorization, $opaque) &&
                preg_match('/uri="([^"]+)"/', $authorization, $uri)
            ) {
                $username = $username[1];
                $passphrase = $checkpasswordfunction($username);
                $requestURI = $_SERVER['REQUEST_URI'];
                if (strpos($requestURI, '?') !== FALSE) { // hack for IE which does not pass querystring in URI element of Digest string or in response hash
                    $requestURI = substr($requestURI, 0, strlen($uri[1]));
                }
                if (
                    $passphrase !== false &&
                    $opaque[1] == $this->getOpaque() &&
                    $uri[1] == $requestURI 
                ) {

                    if ($this->passwordsHashed) {
                        $a1 = $passphrase;
                    } else {
                        $a1 = md5($username.':'.$this->getRealm().':'.$passphrase);
                    }
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
                       if($nonce[1] == $this->getNonce() ) {      
                            return $username;
                        } else {
                            return false;
                        }
                    }
                }
            }
        } else {
            trigger_error('HTTP Digest headers not being passed to PHP by the server, unable to authenticate user');
            exit;
        }
        header('HTTP/1.0 400 Bad Request');
        return NULL;
    }
    
    /** Get nonce value for HTTP Digest.
     * @return str
     */
    private function getNonce() {
        $time = ceil(time() / $this->nonceLife) * $this->nonceLife;
        return md5(date('Y-m-d H:i', $time).':'.$_SERVER['REMOTE_ADDR'].':'.$this->privateKey);
    }

    /** Get opaque value for HTTP Digest.
     * @return str
     */
    private function getOpaque()
    {
        return md5($this->opaque);
    }
    
    /** Get realm for HTTP Digest taking PHP safe mode into account.
     * @return str
     */
    private function getRealm()
    {
        if (ini_get('safe_mode')) {
            return $this->realm.'-'.getmyuid();
        } else {
            return $this->realm;
        }    
    }

}

/* Example usage
$digest = new HTTPDigest('MyRealm'[,'baseurl'][,noncelife][,passwordhashed][,'private-key']);

if(!$digest->getAuthHeader() {
    //This normally means that a user has tried to contact you without any authetication.  You can either reject immediately, or...
    $digest->refresh(false);  //Ask the browser to send some authentication
    //Even though the echo is not seen by the user, this code IS executed the first time through, so beware
    echo 'User Pressed Cancel'
    
} elseif ($username = $digest->authenticate("getPassword(username)")){ //callback function with returns the password, or false if no user
    echo sprintf('Logged in as "%s"', $username);      
} else {
    if(is_null($username)) {
        echo 'Not logged in';
    } else {
        $digest->refresh(); //Ask browser to refresh the authentication  
        //Even thought the echo from the statement below is not seen, this code is still executed - so beware.
        echo 'authentication is stale';  //user has not autheticated for longer than the nonce timer (y
        
    }
}
//*/


