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

DEFINE('OPAQUE','a39ca9bb1783df434c38d8a86258e065'); // Chattering
DEFINE('REALM','chat@hartley-consultants.com');
DEFINE('PRIVATE_KEY','chandlers ZEN');

/** HTTP Digest authentication class */
abstract class HTTPDigest
{

    private $domain;
    private $nonceLife;  //in seconds

    function __construct($domain = '/',$nonceLife = 300) {
        $this->domain = $domain;
        $this->nonceLife = $nonceLife;
    }

    function send() {
        $this->refresh(false);
    }

    /** Send HTTP Auth header */
    function refresh($stale = true)
    {
        header('WWW-Authenticate: Digest '.
            'realm="'.REALM.'", '.
            'domain="'.$this->domain.'", '.
            'qop=auth, '.
            'algorithm=MD5, '.
            (($stale)?'stale=true,':'').
            'nonce="'.$this->getNonce().'", '.
            'opaque="'.OPAQUE.'"'
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
        $this->noAthentication();
        return NULL;
    }

    abstract protected function getPassphrase($username); //Return passphrase or false if user doesn't exist

    /** Authenticate the user and return username on success.
     * @param callback function with single parameter, username = should return with passphrase (or false if no user)
     * @return 
     *      'str'   the username
     *      null    invalid user
     *      false   valid user, but the authetication is stale.  Call HTTPDigest::refresh() to ask browser to refresh it.   
     */
    function authenticate() {
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
                $passphrase = $this->getPassphrase($username);
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
        $this->badRequest();
        return NULL;
    }

    function noAuthentication() {
        header('HTTP/1.0 403 Forbidden');
    }

    function badRequest() {
        header('HTTP/1.0 400 Bad Request');
    }

    
    /** Get nonce value for HTTP Digest.
     * @return str
     */
    private function getNonce() {
        $time = ceil(time() / $this->nonceLife) * $this->nonceLife;
        return md5(date('Y-m-d H:i', $time).':'.$_SERVER['REMOTE_ADDR'].':'.PRIVATE_KEY);
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
$digest = new HTTPDigest(['domain'][,noncelife]);

if(!$digest->getAuthHeader() {
    //If you get to this point, the function will have (by default) returned the 'HTTP/1.0 403 Forbidden' status code.  If you want to 
    // change this, extend the class and overide the function noAthentication().  For instance you might want to override it with 
            $this->send() to ask for some credentials from the user.
    //This normally means that a user has tried to contact you without any authetication.  You can either reject immediately, or...
    ;  //Ask the browser to send some authentication
    //Even though the echo is not seen by the user, this code IS executed the first time through, so beware
    echo 'User Pressed Cancel'
    
} elseif ($username = $digest->authenticate("getPassword(username)")){ //callback function with returns the password, or false if no user
    echo sprintf('Logged in as "%s"', $username);      
} else {
    if(is_null($username)) {
        //If you get to this point the function will have (be default) returned the 'HTTP/1.0 400 Bad Request' status code.  If you want
        // change this, override the $this->badRequest() method.
        echo 'Not logged in';
    } else {
        //The nonce timer has timed out.  You can either do ...    
        $digest->refresh(); //Ask browser to refresh the authentication
          //Even thought the echo from the statement below is not seen, this code is still executed - so beware.
        echo 'authentication is stale';  

        //or you might want to issue a success and indicate failure some other way (for instance explain to the user he has to close
        browser and log in again)      
        
    }
}

*/




