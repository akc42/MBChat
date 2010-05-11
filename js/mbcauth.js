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
function MBCAuth(soundcoord) {
        var loginRequestOptions = {};

        var coordinator = new Coordinator(['rsa','login'],function(activity){
            loginRequestOptions.e = activity.get('rsa').e.toString();
            loginRequestOptions.n = activity.get('rsa').n.toString(10);
            loginRequestOptions.msg = 'MBChat version:'+MBChatVersion+' using:'+Browser.Engine.name+Browser.Engine.version;
            loginRequestOptions.msg += ' on:'+Browser.Platform.name;
            MBchat.init(loginRequestOptions,activity.get('rsa'));
            window.addEvent('beforeunload', function() {
                MBchat.logout(); //Will send you back from whence you came (if you are not already on the way)
            });
            soundcoord.done('chat',{});
        });

        var rsa = new RSA();
        function genResult (key,rsa) {
            coordinator.done('rsa',key);
        };
        /*
            We are kicking off a process to generate a rsa public/private key pair.  Typically this
            takes about 1.2 seconds or so to run to completion with this key length, so should be done
            before the user has completed his input - which is when we will need the result.  The genResult
            function will be called when complete.  
        */

        rsa.generateAsync(64,65537,genResult);

        var login = function() {
            coordinator.done('login',{});    
        };

        window.addEvent('domready',function () {
            var externalAuth = true;
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
                onComplete:function(response,t) {
                    if(response && response.status) {
                        if(response.login.uid == 0) { //special marker telling us we need the form
                            externalAuth = false;  //we are being told to do internal authentication
                            document.id('rsa_generator').addClass('hide');
                            document.id('authblock').removeClass('hide');
                            // and wait for user to respond
                        } else {
                            loginRequestOptions = response.login;
                            login();
                        }
                    } else { 
                        if(externalAuth) {
                            window.location = remoteError;
                        } else {
                            loginError(response.usererror);
                        }
                    }
                }
            })
            var t1 = (Math.ceil(new Date().getTime()/300000)*300).toString();
            while(t1.length < 12) {
                t1 = '0'+t1;
            }
            var t2 = (Math.ceil(new Date().getTime()/300000)*300+300).toString();
            while(t2.length < 12) {
                t2 = '0'+t2;
            }
            document.id('login').addEvent('submit', function(e) {
                e.stop();
                var user = document.id('login').username.value;
                var pass = document.id('login').password.value;
                if(user.contains('$')) {
                    loginError(false);
                    return ;
                }
                if(pass == '') {
                    if(!guestsAllowed) {
                        loginError(false);
                        return;
                    }
                    pass = 'guest';
                    user = '$$G'+user;
                }                    

                t1 = (Math.ceil(new Date().getTime()/300000)*300).toString();
                while(t1.length < 12) {
                    t1 = '0'+t1;
                }
                t2 = (Math.ceil(new Date().getTime()/300000)*300+300).toString();
                while(t2.length < 12) {
                    t2 = '0'+t2;
                }
                document.id('rsa_generator').removeClass('hide');
                document.id('authblock').addClass('hide');
                document.id('login_error').addClass('hide');
                document.id(document.id('login').username).removeClass('error');
                document.id(document.id('login').password).removeClass('error');
                loginReq.post({user:user,pass1:hex_md5(pass+t1),pass2:hex_md5(pass+t2)});
            });
            // This initial request will see if it can authenticate without needing to put up the form 
            loginReq.post({user:'$$$',pass1:hex_md5('auth'+t1),pass2:hex_md5('auth'+t2)});
        });
};

