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

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" type="text/css" href="css/header.css" />
    <title>RSA Key Pair generation Utility</title>
	<link rel="stylesheet" type="text/css" href="css/chat.css" />
	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="/css/chat-ie.css"/>
	<![endif]-->
    <script src="/js/mootools-1.2.4-core-nc.js" type="text/javascript" charset="UTF-8"></script>
   	<script src="/js/coordinator.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/ns.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/binary.js" type="text/javascript" charset="UTF-8"></script>
	<script src="/js/cipher/BigInteger.init1.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/RSA.init1.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/SecureRandom.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/BigInteger.init2.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/RSA.init2.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/BigInteger.init3.js" type="text/javascript" charset="UTF-8"></script>
    <script src="/js/cipher/RSA.init3.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript"> 
        var coordinator = new Coordinator(['dom','rsa'],function(activity){
            document.id('keys').set('text',rsa.toString(10));
        });

        window.addEvent('domready', function() {                
            coordinator.done('dom',{});
        });

        var rsa = new RSA();
        function genResult (key,rsa) {
            coordinator.done('rsa',key);
        };
        /*
            We are kicking off a process to generate a rsa public/private key pair.  Typically this
            takes about quite a few seconds or so to run to completion with this key length
        */

        rsa.generateAsync(256,65537,genResult);

        var login = function() {
            coordinator.done('login',{});    
        };
    </script>
</head>
<body>
<h1>RSA Public and Private Key Generator</h1>
<div id="keys">This text will be replaced by the keys when complete</div>
</body>
</html>

