/*
 	Copyright (c) 2010 Alan Chandler
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
function MBCAuth() {
        var checkNo = new BigInteger(32,new SecureRandom()); 
        var confirmedServer = false;
        var internalAuth = false;
        if(Browser.Engine.trident && Browser.Engine.version == 5) {
            document.id('rsa_generator').removeClass('loading');
            document.id('rsa_generator').removeClass('hide');  //just in case
            document.id('rsa_generator').set('html','<span class="error">Internet Explorer V7 is not supported.  Chat will work with Internet Explorer 6 and 8 as well as Firefox, Chrome, Safari and Opera.</span>');
            return;
        }
             
        function confirmTimeout() {
            if(!confirmedServer) {
                document.id('rsa_generator').removeClass('loading');
                document.id('rsa_generator').removeClass('hide');  //just in case
                document.id('rsa_generator').set('html','<span class="error">Security Alert, Server NOT confirmed.  Please notify security</span>');
            }
        }
        var loginError = function(usernameError) { 
            document.id('rsa_generator').addClass('hide');
            document.id('authblock').removeClass('hide');
            document.id('login_error').removeClass('hide');
            if(usernameError) {
                document.id(document.id('login').username).addClass('error');
                document.id(document.id('login').password).addClass('error');
            } else {
                document.id(document.id('login').password).addClass('error');
            }
        }

        var loginReq = new Request.JSON({
            url:'login/index.php',
            link:'chain',
            onComplete:function(response,t) {
                if(response && response.status) {
                    if(response.trial) { //responding with the returned security key
                        if(response.trial == checkNo.toString(10)) {
                            //matched
                            confirmedServer = true;
                            loginReq.post.delay(1,this,{user:'$$#',pass:remoteKey}); //now find out if I am supposed to prompt
                        } else {
                            confirmTimeout();
                        }
                    } else if (response.login && confirmedServer) {
                        if(response.login.uid == 0) {//special marker telling me that I must authenticate.
                            internalAuth = true;  //we are being told to do internal authentication
                            document.id('rsa_generator').addClass('hide');
                            document.id('authblock').removeClass('hide');
                            // and wait for user to respond
                        } else {
                            loginRequestOptions = response.login;
                            coordinator.done('login',{});
                        }
                    }
                } else { 
                    if(internalAuth) {
                        loginError(response.usererror); //if we get a bad response and we are authenticating, then it was an error.
                    }
                }
                //External authentication will provide its own mechanism
            }
        });



        var encCheckNo = checkNo.modPow(new BigInteger(rsaExponent),new BigInteger(rsaModulus));

        window.addEvent('domready',function () {
            document.id('login').addEvent('submit', function(e) {
                e.stop();
                var auth = {};
                auth.U = document.id('login').username.value;
                auth.P = document.id('login').password.value;
                if(auth.U.contains('$')) {
                    loginError(false);
                    return ;
                }
                if(auth.P == '') {
                    if(!guestsAllowed) {
                        loginError(false);
                        return;
                    }
                    auth.P = 'guest';
                    auth.U = '$$G'+auth.U;
                }
 
                var t1 = (Math.ceil(new Date().getTime()/100000)*100).toString();
                while(t1.length < 10) {
                    t1 = '0'+t1;
                }
                document.id('rsa_generator').removeClass('hide');
                document.id('authblock').addClass('hide');
                document.id('login_error').addClass('hide');
                document.id(document.id('login').username).removeClass('error');
                document.id(document.id('login').password).removeClass('error');
                loginReq.post({user:auth.U,pass:hex_md5(auth.P+t1)});
            });
            // This initial request will see if it can authenticate without needing to put up the form - we also want to verify the server
            loginReq.post({user:'$$$',pass:remoteKey,trial:encCheckNo.toString(10)});
            confirmTimeout.delay(10000); //give server 10 seconds to come back with correct response.
            coordinator.done('dom',{});
        });
};

