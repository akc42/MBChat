<?php
/*
 	Copyright (c) 2009 Alan Chandler
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


error_reporting(E_ALL);
DEFINE('TEMPLATE_DIR','template');



function head_content() {
?><title>Melinda's Backups Chat - Sign In Page</title>
	<link rel="stylesheet" type="text/css" href="../client/chat.css" title="mbstyle"/>
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="../client/chat-ie.css"/>
	<![endif]-->
	<style type="text/css">
	    #content, #content td {
	        color:#ffffff;
	   }

	   #alternate {
	    background-color:white;
	    height:100px;
	    width:200px;
	   }
	</style>
    <script src="/js/mootools-1.2.4-core-nc.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/packages.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/binary.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/isarray.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/elapse.js" type="text/javascript" charset="UTF-8"></script>
	<script src="/js/cipher/BigInteger.init1.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/RSA.init1.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/SecureRandom.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/BigInteger.init2.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/RSA.init2.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/nonstructured.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/BigInteger.init3.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/RSA.init3.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript">
	    __uses( "BigInteger.init1.js" );
	    __uses( "BigInteger.init2.js" );
	    __uses( "RSA.init1.js" );
	    __uses( "RSA.init2.js" );
	    __uses( "RSA.init3.js" );
	    
        var BigInteger = __import( this,"titaniumcore.crypto.BigInteger" ); 
        var RSA = __import( this,"titaniumcore.crypto.RSA" );

        var rsa = new RSA();

        var keyPair = null;  //When complete this object will hold a Public/Private Key Pair.
        
        var genResult = function (key,rsa) {
            keyPair = key;
        };
        var progress = function(count){};
        var done = function(succeeded) {}
        /*
            We are kicking off a process to generate a rsa public/private key pair.  Typically this
            takes about 1.2 seconds or so to run to completion with this key length, so should be done
            before the user has completed his input - which is when we will need the result.  The genResult
            function will be called when complete (as is 'done' but we don't use it).  Instead we will check for 
            keyPair to become non null. 
        */
        var timerId = rsa.generateAsync(64,65537,progress,genResult,done);

        var login = function(){
            document.id('authblock').addClass('hide');
            document.id('alternate').removeClass('hide');
            var timerId;
            var checkKey = function () {
                if(keyPair) {
                    $clear(timerId);
                    proceed();
                }
            }; 
            if(keyPair) {
                //we have a key ready, so now we can use it
                proceed();
            } else {
                timerId = checkKey.periodical(50);
            }
            return false;
        };

        /* this proceed function is reached when both the user has entered a username (and possible password) AND
            the RSA generation has finished
         */
        var proceed = function() {
            var req = new Request.JSON({
                url:'/login/index.php',
                onSuccess: function(r,t) {
                    c = new BigInteger(r.c);
                    m = c.modPow(keyPair.d,keyPair.n);// This decrypts the key we need for the next stage.
  
var i=0;
            
                
                },
                onFailure: function(xhr) {
                    document.id('alternate').addClass('hide')
                    document.id('authblock').removeCLass('hide');
    
                }
            });
            var requestOptions = {};
            requestOptions.e = keyPair.e.toString(); //Add public key
            requestOptions.n = keyPair.n.toString(10);
            var user = document.id('login').username.value;
            var pass = document.id('login').password.value;
            if (pass == '') {
                requestOptions.guest = 'guest';
                pass = 'guest';
            };
            if (document.id('login').lite.checked) {
                requestOptions.lite = 'lite';
            }
            
            req.xhr.open("post",'/login/index.php',true,user,pass);
            req.send(requestOptions);
        }
   </script>
    
	
<?php
}

function content() {
?>
<div id="content">
<h1>Hartley Chat Login</h1>
<p>Enter a username that you want to be known of in chat.  You only need to enter a  
password <strong>if you are already registered as a user</strong> as this will be used to check your credentials in the database and will log you in as a normal user.</p>
<p>If you are already logged in your connection will be <strong>refused</strong>.</p>

<p>Guest users should just enter the name they wish to be known as in chat and leave the password field <strong>empty</strong>.  There will
be no check for whether you are already connected.</p>

<p>The accessibility version (see checkbox at bottom of form) is for users of the Jaws screen reading system for blind users.  This version removes
some of the graphics in exchange for an interface designed specifically to enable Jaws to provide access. Only guests need select this if they wish to try this function.  Information about regular users is already stored.</p>
<p></p>
<div id="authblock">
<form id="login" action="#" method="post" onsubmit="javascript:return login()">
    <table>
        <tr><td>Username:</td><td><input type="text" name="username" value="" /></td></tr>
        <tr><td>Password:</td><td><input type="password" name="password" value="" /></td></tr>
        <tr><td><input type="submit" name="submit" value="Sign In"/></td><td>Use "Accessibilty" version: <input type="checkbox" name="lite" /></td></tr>
    </table>
<form>
</div>
<div id="alternate" class="hide"></div>
<div id="connector" class="hide">
    <form id="chatter" action="/client/chat.php" method="post" >
        <input type="hidden" name="uid" value="" />
        <input type="hidden" name="pass" value="" />
        <input type="hidden" name="name" value="" />
        <input type="hidden" name="role" value="" />
        <input type="hidden" name="mod" value="" />
        <input type="hidden" name="whi" value="" />
        <input type="hidden" name="gp" value="" />
        <input type="hidden" name="ctype" value="" />
    </form>
</div>
</div>
<?php
}


function menu_items() {
//Noop
}

include(TEMPLATE_DIR.'/template.php');

?>
