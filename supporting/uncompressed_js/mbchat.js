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
MBchat = function () {
    /* flags in capabilities which say what can do */
    var BLIND = 1;
    var SECRETARY = 2;
    var ADMIN = 4;
    var MOD = 8;
    var SPEAKER = 12
    var NO_WHISPER = 32;
    /* room types */
    var OPEN = 0;
    var MEETING = 1;
    var OPS = 2;
    var MODERATED = 3;
    var MEMBER = 4;
    var GUEST = 5;
    var DUNGEON = 6;
	var me = {};
	me.is = function (cap) {
        return ((me.cap & cap) != 0);
    };

    function padDigits(n, totalDigits) {  
        var pd = ''; 
        if (totalDigits > n.length) { 
            for (i=0; i < (totalDigits-n.length); i++) { 
                pd += '0'; 
            } 
        } 
        return pd + n; 
    }
	var Room = new Class({
		initialize: function(rid,name,type) {
			this.rid = rid || 0;
			this.name = name || 'Entrance Hall';
			this.type = type || 0;
		},
		set: function(room) {
			this.rid = room.rid;
			this.name = room.name;
			this.type = room.type;
		} 
	});
	var room;
	var entranceHall;
	var privateRoom = 0;
	var chatBot;
	var messageListSize;
	var hyperlinkRegExp = new RegExp('(^|\\s|>)(((http)|(https)|(ftp)|(irc)):\\/\\/[^\\s<>]+)(?!<\\/a>)','gm');;
	var emoticonSubstitution = new Hash({});
	var emoticonRegExpStr; //dynamically calculated during init
	var logOptions = {};
	var logged_in;
	var crossWhisper = true;

	var reqQueue = new Chain();
    var reqRunning = false;
	
	var ServerReq = new Class({
		initialize: function(url,process) {
			this.request = new Request.JSON({url:url, link:'chain',onComplete: function(response,errorMessage) {
				if(response) {
   					process(response);
				} else {
					displayErrorMessage(''+url+' failure:'+errorMessage);
				}
				reqRunning = false;
				reqQueue.callChain();
			}});
		},
		transmit: function (options) {
		    var reqOptions = $merge(auth,options);
            if (reqRunning) {
                reqQueue.chain(arguments.callee.bind(this,reqOptions));
            } else {
                reqRunning = true;
			    this.request.post.delay(1,this.request,reqOptions);
			}
		}		
	});
	var displayUser = function(user,container) {
		var el = new Element('span',{'class' : user.role, 'text' : user.name });
        el.inject(container); 
		return el;
	};
	var displayErrorMessage = function(txt) {
		var msg = '<span class="errorMessage">'+txt+'</span>';
		var d = new Date();
		MBchat.updateables.message.displayMessage(0,d.getTime()/1000,chatBot,msg);  //need to convert from millisecs to secs
	};
	var chatBotMessage = function (msg) {
		return '<span class="chatBotMessage">'+msg+'</span>';
	};
	var messageReq = new ServerReq('client/message.php',function (r) {
        if(!r.status) {
		    var d = new Date();
		    MBchat.updateables.message.displayMessage(0,d.getTime()/1000,chatBot,r.reason); 
        }
	});
	var whisperReq = new ServerReq('client/whisper.php',function (r) {});
	var privateReq = new ServerReq('client/private.php',function (r) {});
	var goPrivate = function() {
		privateReq.transmit({
			'wid': this.getParent().get('id').substr(1).toInt(),
			'lid' : MBchat.updateables.poller.getLastId(),
			'rid' : room.rid});
	};
	var contentSize;
	var pO;


    var desKey = false;
    var auth = {};
	var rsaKeys;
    var loginReq = new Request.JSON({
        url:'client/login.php',
        link:'chain',
        onSuccess: function(response,t) {
            document.id('rsa_generator').addClass('hide');
            if(response && response.status) {
                document.id('chatblock').removeClass('hide');
                logged_in = true;
                chatBot = {uid:0, name : response.params.chatbot_name, role: 'C'};  //Make chatBot like a user
                messageListSize = response.params.max_messages;  //Size of message list
                var c = new BigInteger(response.key);
                var m = c.modPow(rsaKeys.d,rsaKeys.n);// This decrypts the key we need for the next stage.
                auth.uid = me.uid;
                auth.pass = hex_md5('U'+auth.uid+'P'+padDigits(m.toString(10),5));
                if(response.des) {
                    var d = new BigInteger(response.des);
                    desKey = padDigits(d.modPow(rsaKeys.d,rsaKeys.n).toString(10),10);
                }
                logOptions.fetchdelay = response.params.log_fetch_delay.toInt();
                logOptions.spinrate = response.params.log_spin_rate.toInt();
                logOptions.secondstep = response.params.log_step_seconds.toInt();
                logOptions.minutestep = response.params.log_step_minutes.toInt();
                logOptions.hourstep = response.params.log_step_hours.toInt();
                logOptions.sixhourstep = response.params.log_step_6hours.toInt();
                crossWhisper = (response.params.whisper_restrict == 'false');
                entranceHall = new Room(0,response.params.entrance_hall,0);
                room = new Room();
                room.set(entranceHall);
                var roomReq = new Request({url:'client/chat.php',onSuccess:function (html) {
                    document.id('entranceHall').set('html',html);
                    var exit = $('exit');
                    if(me.is(BLIND)) {
		                document.addEvent('keydown',function(e) {
			                if(!e.control ) {
				                if(!e.alt) return;  //only interested if control or alt key is pressed
				                if (room.rid != 0) return; //only interested if in entrance hall
				                if (e.key == '0') {
					                MBchat.updateables.online.show(0);  //get entrance hall list
				                } else {
					                if($('R'+e.key)) {
						                MBchat.updateables.online.show(e.key.toInt());  //get entrance hall list
					                }
				                }
			                } else {				
				                if (e.key == '0' || e.key == 'x') {
					                if (privateRoom != 0) {
						                privateReq.transmit({
							                'wid':  0,
							                'lid' : MBchat.updateables.poller.getLastId(),
							                'rid' : room.rid });
					                } else {
						                if (room.rid == 0) {
							                MBchat.logout(); //Go back from whence you came
						                } else {
							                MBchat.updateables.message.leaveRoom();
						                }
					                }
				                } else {
					                if($('R'+e.key)) {
						                MBchat.updateables.message.enterRoom(e.key.toInt());
					                } else {
						                if(e.key == 's') {
							                messageSubmit(e);
						                }
					                }
				                }
			                }
		                });

                    } else {
	                    var roomTransition = new Fx.Transition(Fx.Transitions.Bounce, 6);
                        var exitfx = new Fx.Morph(exit, {link: 'cancel', duration: 500, transition: roomTransition.easeOut});
                        exit.addEvent('mouseenter',function(e) {
                            exitfx.start({width:100});
                        });
                        exit.addEvent('mouseleave', function(e) {
                            exitfx.start({width:50});
                        });
	                    var roomgroups = $$('.rooms');
	                    roomgroups.each( function (roomgroup,i) {
		                    var rooms = roomgroup.getElements('.room');
		                    var fx = new Fx.Elements(rooms, {link:'cancel', duration: 500, transition: roomTransition.easeOut });
		                    rooms.each( function(door, i){
			                    var request;
			                    door.addEvent('mouseenter', function(e){
                    				if (room.rid == 0) {
					                    //adjust width of room to be wide
					                    var obj = {};
					                    obj[i] = {'width': [door.getStyle('width').toInt(), 219]};
					                    rooms.each(function(otherRoom, j){
						                    if (otherRoom != door){
							                    var w = otherRoom.getStyle('width').toInt();
							                    if (w != 67) obj[j] = {'width': [w, 67]};
						                    }
					                    });
                       					fx.start(obj);
					                    // Set up online list for this room 
                       					MBchat.updateables.online.show(door.get('id').substr(1).toInt());
                 					} else {
					                    displayErrorMessage('Debug - MouseEnter in Room');
				                    }
			                    });
			                    door.addEvent('mouseleave', function(e){
				                    var obj = {};
				                    rooms.each(function(other, j){
					                    obj[j] = {'width': [other.getStyle('width').toInt(), 105]};
				                    });
                   					fx.start(obj);
				                    if(room.rid == 0 ) {
                       					MBchat.updateables.online.show(0);
				                    }
			                    });
			                    door.addEvent('click', function(e) {
				                    e.stop();			//browser should not follow link
                    				if((e.control || e.shift) && 
                    				        ((me.is(ADMIN) && !door.hasClass('meeting')) || 
                    				            (me.is(SECRETARY) && door.hasClass('meeting')))) {
                    					MBchat.updateables.logger.startLog(door.get('id').substr(1).toInt(),door.get('text'));
				                    } else {
					                    MBchat.updateables.message.enterRoom(door.get('id').substr(1).toInt());
				                    }
			                    });
		                    });
	                    });
	                }
                    exit.addEvent('click', function(e) {
	                    e.stop();
	                    if (privateRoom != 0) {
		                    privateReq.transmit({
			                    'wid':  0, 
			                    'lid' : MBchat.updateables.poller.getLastId(),
			                    'rid' : room.rid }); 
	                    } else {
		                    if (room.rid == 0 ) {
			                    if (this.hasClass('exit-r')) {
				                    // just exiting from logging
				                    MBchat.updateables.logger.returnToEntranceHall();
			                    } else {
				                    MBchat.logout(); //Go back from whence you came

			                    }
		                    } else {
			                    MBchat.updateables.message.leaveRoom();
		                    }
	                    }
                    });
                    document.id('messageForm').addEvent('submit', function(e) {
		                messageSubmit(e);
		                return false;
	                });

                    MBchat.updateables.init(); //Start Rest
                    MBchat.updateables.whispers.init(response.lid);
                    MBchat.updateables.online.show(0);	//Show online list for entrance hall
                    MBchat.updateables.poller.init(response.lid,response.params.presence_interval.toInt());
                    reqRunning = false;
			        reqQueue.callChain();
	            }});
	            var roomReqSend = function() {
    		        roomReq.post(auth);
    		    }
                if (reqRunning) {
                    reqQueue.chain(roomReqSend.bind(this));
                } else {
                    reqRunning = true;
		            roomReqSend();;
		        }
            } else {
                window.location = 'login/logout.php';
            }
        }
	           
    });
    var messageSubmit;
    var identString;
return {
	init : function(loginOptions,keys) {
        identString = loginOptions.msg;
	    me = $extend(me,loginOptions);
	    me.uid = me.uid.toInt();
	    me.cap = me.cap.toInt();
	    delete me.e;
	    delete me.n;
	    delete me.msg;
	    delete me.pass;
        rsaKeys = keys;
	    logged_in = false;
        messageSubmit = function(event) {
		    event.stop();
		    var msg;
		    if(desKey) {
    		    msg = Base64.encode(des(desKey, $('messageText').value, true));
    		} else {
    		    msg = $('messageText').value;
    		}
		    if (privateRoom == 0 ) {
			    messageReq.transmit({
				    'rid':room.rid,
				    'lid':MBchat.updateables.poller.getLastId(),
				    'text':msg});
		    } else {
			    whisperReq.transmit({
				    'wid':privateRoom,
				    'rid':room.rid,
				    'lid':MBchat.updateables.poller.getLastId(),
				    'text':msg});
		    }

		    $('messageText').value = '';
		    MBchat.sounds.resetTimer();
	    }

	    if(me.is(BLIND)) {
            //I am blind
            document.id('emoticonContainer').addClass('hide');
            document.id('soundEnabled').checked = false; //switch off sound as it interferes with screen reader
            document.id('autoScroll').checked = false;
            document.id('userOptions').addClass('hide');
            //replace exit div with a button
            document.id('exit').dispose();
            var el = new Element('input',{id:'exit',type:'button',value:'Exit'});
            el.inject(document.id('chatblock'),'top');
            //remove send buttons as ctrl+s is used.
            document.id(document.id('messageForm').submit).dispose();
            document.id(document.id('whisperBoxTemplate').getElement('form').submit).dispose();
        } else {
        	//Set up emoticons
	        var regExpStr = ':('; //start to make an regular expression to find them (the all start with :)
	        var emoticons = $$('img.emoticon');
	        emoticons.each(function(icon,i) {
		        var key = icon.get('alt').substr(1);
		        var img = '<img src="' + icon.get('src') + '" alt="' + key + '" title="' + key + '" />' ;
		        emoticonSubstitution.include(key,img);
		        if(i!=0) regExpStr += '|';
		        regExpStr += key.replace(/\)/g,'\\)') ;  //regular expression is key except if has ) in it which we need to escape
		        icon.addEvent('click', function(e) {
			        e.stop();		
			        var msgText = $('messageText');
			        msgText.value += ':'+ key ;
			        msgText.focus();
		        });
	        });
	        //finish pattern and turn it into a regular expression to use;
	        regExpStr += ')';
 	        emoticonRegExpStr = new RegExp(regExpStr, 'gm');
       }
	    contentSize = $('content').getCoordinates();
	    window.addEvent('resize', function() {
		    contentSize = $('content').getCoordinates();
	    });
	    //lets login
        loginReq.post(loginOptions);
	},
	logout: function () {
	    if(logged_in) {
	        MBchat.updateables.poller.logout(); //Stop Poller Function completely
    		var logoutRequest = new Request.JSON({url:'client/logout.php',async:false});
    		logoutRequest.post($merge(auth,{ident:identString}));
            logged_in = false;
		    window.location = 'client/index.php?'+Hash.toQueryString(auth) ;
        }
	},
	sounds: function () {
		var music = false;
		var musicEnabled = false;
		var soundEnabled = false;
		var playAgain = true;
		var Timer = {counter:30 , start : 6 }; //Units of 10 seconds
		var countDown = function() {
			if (this.counter > 0 ) this.counter-- ;
			if (!music) {
				music = soundManager.getSoundById('music');
				music.options.onfinish = function () {
					playAgain = true;
				}
				music.volume = 10;
			}
			if (musicEnabled.checked) {
				if (playAgain) {
					soundManager.play('music');
					playAgain = false;
				}
			} else {
				if(!playAgain) {
					soundManager.stop('music');
					playAgain = true;
				}
			}
		}
		
		return {
			init: function () {
				//deal with sound delay
				countDown.periodical(10000,Timer); //countdown in 10 sec chunks				
				//music
				musicEnabled = $('musicEnabled');
				musicEnabled.addEvent('click', function(e) {
					if(!musicEnabled.checked) {
						soundManager.stop('music');
						playAgain = true;
					}
				});
				soundEnabled = $('soundEnabled');		
			},
			resetTimer: function() {
				Timer.counter = Timer.start * $('soundDelay').value.toInt();
			},
			roomMove : function() {
				if(soundEnabled && soundEnabled.checked) {
					if(room.type == DUNGEON) { //special room type for creaky door
						soundManager.play('creaky');
					} else {
						soundManager.play('move');
					}
				}
			},
			newWhisper: function() {
				if(soundEnabled && soundEnabled.checked) soundManager.play('whispers');
			},
			messageArrives:function() {
				if(soundEnabled && soundEnabled.checked && Timer.counter == 0) soundManager.play('speak');
			}
		};
	}(),
	updateables : function () {
		var replaceEmoticons = function(text) {
			return text.replace(emoticonRegExpStr,function(match,p1) {
				return emoticonSubstitution.get(p1);
			});
		};
		var replaceHyperLinks = function(text) {
			return text.replace(hyperlinkRegExp,function(str, p1, p2) {
				return p1 + '<a href="' + p2 
					+ '" onclick="window.open(this.href); return false;">' + p2 + '</a>';
			});
		};
		return {
			init : function () {
				//only initialise parts of this - the rest needs to wait until we get response from login.
				MBchat.updateables.online.init();
				MBchat.updateables.message.init();
				MBchat.updateables.logger.init();
			},
			processMessage : function(message) {
			    if(message.message && desKey) {
			        message.message = des(desKey,Base64.decode(message.message),false).replace(/\0+/g,'');
			    }
				MBchat.updateables.online.processMessage(message);
				MBchat.updateables.message.processMessage(message);
				MBchat.updateables.whispers.processMessage(message);
			},
			poller : function() {
				var pollerId;
				var lastId = null;
				var fullPoll=0;   //0 = polling stopped, 1=polling requested to stop, but not yet done so, 2= polling running
				var pollInterval;
				var wid;
                
    			var pollRequest = new Request.JSON({url:'client/read.php',link:'ignore',onComplete:function (r,t) {
    			    readComplete.delay(10,this,[r,t]);
    			}}); 

				var readComplete = function(response,errorMessage) {
				    if(response) {
				        if(response.messages) {
                            if(response.lastlid) lastId = response.lastlid; //
                             if ( fullPoll == 2) {
						        response.messages.each(function(item) {
							        MBchat.updateables.processMessage(item);
                                });
				            }
				        } else {
				            if(response.reason) displayErrorMessage("read.php failure:"+response.reason);
				        } 
				    } else {
				        if(errorMessage) {
				            displayErrorMessage("read.php failure:"+errorMessage); //Final Logout is a null message
				            fullPoll = 0;  //At this point we are getting read fails, so lets just stop the polling
				        }
				    }
				    if (fullPoll == 2) {
				        pollRequest.post({uid:auth.uid,pass:auth.pass,'lid':lastId+1}); //Should chain (we are in previous still)
				    } else {
				        fullPoll = 0; //If stop was pending, it has now finished
				    }
				}
				var presenceReq = new ServerReq('client/presence.php', function(r) {});
				var pollPresence = function () {
							presenceReq.transmit({});  //say here (also timeout others)
				};
				return {
					init : function (lid,presenceInterval) {
					    lastId = lid;
						pollInterval = presenceInterval*1000; //presenceInterval is in seconds - convert to milliseconds
						pollerId = pollPresence.periodical(pollInterval,MBchat.updateables);	
		                MBchat.updateables.poller.start();
					},
					getLastId: function() {
						return lastId;
					},

					start : function () {
	        		    if (fullPoll == 0) {
        		            pollRequest.post({uid:auth.uid,pass:auth.pass,'lid':lastId+1});		
						 }
                        fullPoll= 2;
					},

					stop : function() {
						fullPoll=1;
					},
					logout: function() {
					    MBchat.updateables.poller.stop();
					    $clear(pollerId);
					}
				};
			}(),
			online : function() {	//management of the online users list
				var onlineList ;		//Actual Display List
				var lastId;
				var loadingRid;
				var currentRid = -1;
				var labelList = function() {
					var node = onlineList.getFirst();
					if (node) {
						var i = 0;
						do {	
							node.removeClass('rowEven');
							node.removeClass('rowOdd');
							if( i%2 == 0) {
								node.addClass('rowEven');
							} else {
								node.addClass('rowOdd');
							}
							i++;
						} while (node = node.getNext());
					} 
				};
				var addUser = function (user) {
				    var span;
					var div =$('U'+user.uid);  // Does this already exist?
					if(div) div.destroy();  //So remove him, because we need to recreate from scratch
    				div = new Element('div', {'id': 'U'+user.uid}); 
					if(me.is(BLIND)) {
					    span = new Element('input',{type:'button','class':user.role,value:user.name});
					    span.inject(div);
					} else {
					    span = displayUser(user,div);
                    }
					if (user.wid && user.wid.toInt() != 0  ) { 
						//This user is in a private room so maybe we don't display him
						if (user.uid != me.uid) {
							//Not me, but I might be in a whisper with them

							var whisperBox = $('W'+user.wid);
							if (!whisperBox) {
								return null; //not in any whisper box, so don't display 
							}
						}
						span.addClass('priv');  //makes them Italic to show its private
					} else {
						if (room.type === MODERATED || room.type === OPS) {
							if ((room.type === MODERATED && me.is(MOD)) || (room.type === OPS && !me.role == 'B')) {
								if (user.uid != me.uid) {
									if (user.question) {
										span.addClass('ask');
										div.store('question',user.question);
										div.addClass('hasQuestion');
									}
									// I am a moderator in a moderated room - therefore I need to be able to moderate others
									div.addEvents({
										'click' : function(e) {
											var qtext = div.retrieve('question');
											if (qtext) { // only send one if there is one
												var request = new ServerReq('client/release.php',function (response) {
//														MBchat.updateables.poller.pollResponse(response);
												}).transmit({
													'lid':MBchat.updateables.poller.getLastId(),
													'rid':room.rid,
													'quid':user.uid});
											}
										},
										'mouseenter' : function(e) {
											var span = div.getFirst();
											if ((room.type == MODERATED && 
											        !(span.hasClass('M') || span.hasClass('S'))) ||
											        (room.type == OPS && span.hasClass('B'))) { 											
												var qtext = div.retrieve('question');
												if (qtext) {
												    var question = new Element('div', {'id' : 'Q'+user.uid});
													qtext = replaceHyperLinks (qtext);  //replace Hperlinks
													qtext = replaceEmoticons(qtext); //Then replace emoticons.
													question.set('html',
														'<p><b>Click to Release Question<br/>',
														'<p>',qtext,'</p>'); 
													question.setStyles({'top': e.client.y, 'left':e.client.x - 200});
    												question.inject(document.body);
												}
											}
										},
										'mouseleave' : function(e) {
											div.removeClass('hasQuestion');
											var question = $('Q'+user.uid);
											if (question) {
												question.destroy();
											}
										}
									});
									div.getFirst().addClass('whisperer'); //Adds cursor pointer
								}
							} else {
								if (user.question) {
									span.addClass('ask');
								}
								if (me.uid == user.uid) {
									if (user.question) {
										div.store('question',user.question);
										div.addClass('hasQuestion');
									}
									div.addEvents({
										'mouseenter' : function(e) {
											var span = div.getElement('span');
											if ((room.type == MODERATED && !(span.hasClass('M') || span.hasClass('S'))) 
												|| (room.type == OPS & span.hasClass('B'))) {
												var qtext = div.retrieve('question');
												if (qtext) {
												    var question = new Element('div', 
													    {'id' :  'Q'+div.get('id').substr(1)});
													qtext = replaceHyperLinks (qtext);  //replace Hperlinks
													qtext = replaceEmoticons(qtext); //Then replace emoticons.
													question.set('html','<p>',qtext,'</p>'); 
													question.setStyles({'top': e.client.y, 'left':e.client.x - 200});
													div.addClass('hasQuestion');
													question.inject(document.body);
												}
											}
										},
										'mouseleave' : function(e) {
											var question = $( 'Q'+div.get('id').substr(1));
											if (question) {
												question.destroy();
											}
										}
									});
								} 
							}
						} 
					}
					// Figure out if we can whisper together
					if (user.uid != me.uid && !me.is(NO_WHISPER) 
					              && (crossWhisper || (me.role != 'B' && user.role != 'B') || ( me.role == 'B' && user.role === 'B' ))) {
					    var ww = function(e) { 
							MBchat.updateables.whispers.whisperWith(user,span,e);
						};
                        if(me.is(BLIND)) {
                            div.addEvent('click',ww);
                            div.addClass('whisperer');
                        } else { 
						    span.addEvent('mousedown',ww);
						    div.getFirst().addClass('whisperer');
						}
					}
					var qtext = div.retrieve('question');
					if (qtext) {
						div.inject(onlineList,'top')
					} else {
    					div.inject(onlineList,'bottom'); 
                    }
					labelList();
					return div;
				};
				var removeUser = function (div) {
					//remove any question it might have
					var question = $('Q'+div.get('id').substr(1));
					if (question) {
						question.destroy();
					}
					div.destroy(); //removes from list
					labelList();
				};
				onlineReq = new ServerReq('client/online.php',function(response) {
					onlineList.removeClass('loading');
					onlineList.addClass(room.type);
					currentRid = loadingRid;
					loadingRid = -1;
					onlineList.empty();
					var users = response.online;
					if (users.length > 0 ) {
						users.each(function(user) {
							user.uid = user.uid.toInt();
							addUser(user);
						});
					} 
					lastId = response.lastid;
				});
				return {
					init: function () {
						onlineList = $('onlineList');		//Actual Display List
						lastId = null;
						currentRid = -1;
					},
					show : function (rid) {
						onlineList.empty();
						onlineList.erase('class');
						onlineList.addClass('loading');
						onlineReq.transmit({'rid':rid });
						currentRid = -1;
						loadingRid = rid;
					},
					getCurrentRid: function() {
						return currentRid;
					},
					processMessage: function (msg) {
						if(!lastId) return;	//not processing messages yet
						var lid = msg.lid;
						if (lastId < lid) {
							lastId = lid;
							userDiv = $('U'+msg.user.uid);

						    var whisperer;
						    switch (msg.type) {
						    case 'LO' : 
						    case 'LT' :
							    if (me.uid == msg.user.uid) {
				                    /*  It is me that has been logged off.  For this to happen it means my comms is broken.  THe best
				                        thing for me to do is to exit */
                                    window.location('./client/index.php');
							    } else {
								    if (userDiv) {
									    removeUser(userDiv)
								    }
							    }
							    break;
						    case 'LX' :
						        if (me.uid == msg.user.uid) {
						            MBchat.logout(); //Go back from whence you came
						        }
						        break;
						    case 'RX' :
							    if (currentRid == 0) {
								    if (!userDiv) {
									    userDiv = addUser(msg.user);
									    if (privateRoom != 0) {
										    whisperer = $('W'+privateRoom+'U'+msg.user.uid);  //Are we in a whisper
										    if (whisperer) userDiv.addClass('priv');
									    }
								    }
							    } else {
								    if (userDiv) {
									    if (privateRoom == 0 ) {  
										    removeUser(userDiv);
									    } else  {
										    if (!userDiv.hasClass('priv')) {
											    removeUser(userDiv);  //Only remove if not in the private room with this person
										    }
									    }
								    } 
							    }			 
							    break;
						    case 'LI' : 
							    if (!userDiv && msg.rid == currentRid) {
								    addUser(msg.user); //Can't be in a whisper so don't look
							    }	
							    break;
						    case 'RE' :
							    if (currentRid != 0) {
								    if (!userDiv  && msg.rid == currentRid) {
									    var user = msg.user;
									    user.question = msg.message;
									    userDiv = addUser(user);
									    if (privateRoom != 0) {
										    whisperer = $('W'+privateRoom+'U'+user.uid);  //Are we in a whisper
										    if (whisperer) userDiv.addClass('priv');
									    }
								    }
							    } else {
								    if (userDiv) {
									    if (privateRoom == 0 ) {  
										    removeUser(userDiv);
									    } else  {
										    if (!userDiv.hasClass('priv')) {
											    removeUser(userDiv);  //Only remove if not in the private room with this person
										    }
									    }
								    } 
							    }
							    break;
						    case 'MQ' : // User asks a question
							    if(msg.rid == currentRid) {
								    if ((room.type == MODERATED && (me.is(MOD) || me.uid == msg.user.uid))
								            || (room.type == OPS && (me.role != 'B' || me.uid == msg.user.uid))) {
									    var user = msg.user;
									    user.question = msg.message;
									    userDiv = addUser(user);
								    }
								    var span = userDiv.getFirst();
    							    span.addClass('ask');
							    }
							    break;
						    case 'MR' :
						    case 'ME' :
							    if(msg.rid == currentRid) {
								    //A message from a user who is still here must mean he no longer has a question outstanding
								    if(userDiv) {
								        userDiv.getFirst().removeClass('ask');
								    }
								    if ((room.type == MODERATED && (me.is(MOD) || me.uid == msg.user.uid))
								            || (room.type == OPS && (me.role != 'B' ||me.uid == msg.user.uid))) {
									    addUser(msg.user); //there will be no question
								    }
							    }
							    break;
						    case 'PE' :
							    var whisperBox = $('W' + msg.rid);
							    if(userDiv) { //only relevent if we have this user
								    if (msg.user.uid != me.uid) {
									    //Not me, but I might be in a whisper with them
									    if (!whisperBox) {
										    MBchat.updateables.message.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Leaves the Room'));
										    MBchat.sounds.roomMove();
										    removeUser(userDiv);
									    } else {
										    userDiv.addClass('priv');
									    }
								    } else {
									    if (room.rid == 0) {
										    var messageList=$('chatList');
										    //I'm in entrance hall so have to make it look like a room
										    messageList.removeClass('whisper');
										    messageList.addClass('chat');
										    $('inputContainer').set('styles',{ 'display':'block'});
										    $('emoticonContainer').set('styles',{ 'display':'block'});
										    $('entranceHall').set('styles',{'display':'none'});	
										    var exit = $('exit');
										    exit.addClass('exit-r');
										    exit.removeClass('exit-f');
									    }
									    $('roomNameContainer').empty();
									    var el = new Element('h1',{'class':'privateRoom'})
										    .set('text', 'Private Room')
										    .inject('roomNameContainer');
									    //remove P markers from all whisper boxes
									    var privateMarkers= $$('.private');
									    privateMarkers.addClass('nonprivate');
									    privateMarkers.removeClass('private');
									    privateRoom = msg.rid.toInt();
									    var fellowWhisperers = 	
										    whisperBox.getElement('.whisperList').getChildren();
									    var users = $('onlineList').getChildren();
									    users.each( function(user) {
										    if (fellowWhisperers.some(function(item){
											    return this.get('id').substr(1) 
											    == item.get('id').substr(whisperBox.get('id').length+1);
										    },user)) {
											    user.addClass('priv');
										    }
									    });
									    userDiv.addClass('priv');
									    $('messageText').focus();
									    MBchat.sounds.resetTimer();
									    whisperBox.setStyle('display','none');
									    $('content').setStyles(contentSize);
									    MBchat.sounds.roomMove();
								    }
							    } else {
								    // Add user to list if in a whisper (otherwise doesn't)
								    var user = msg.user;
								    user.wid = msg.rid;
								    addUser(user);
							    }
							    break;
						    case 'PX' :
							    if (msg.user.uid == me.uid) {
								    $('roomNameContainer').empty();
								    var el = new Element('h1')
									    .set('text', room.name)
									    .inject('roomNameContainer');
								    //Put private markers back on all whisper boxes
								    var privateMarkers= $$('.nonprivate');
								    privateMarkers.addClass('private');
								    privateMarkers.removeClass('nonprivate');
								    MBchat.updateables.online.show(room.rid); //reshow the online list from scratch
								    $('messageText').focus();
								    if (room.rid == 0) {
									    var messageList=$('chatList');
									    //need to restore entrance hall
									    messageList.removeClass('chat');
									    messageList.addClass('whisper');
									    $('inputContainer').set('styles',{ 'display':'none'});
									    $('emoticonContainer').set('styles',{ 'display':'none'});
									    $('entranceHall').set('styles',{'display':'block'});
									    var exit = $('exit');	
									    exit.addClass('exit-f');
									    exit.removeClass('exit-r');
								    }
								    MBchat.sounds.resetTimer();
								    if($('W'+privateRoom)) {
							    //need to make a whisper box with my whisperers in it (if not closed already)
									    $('W'+privateRoom).setStyle('display','block');
								    }
								    $('content').setStyles(contentSize);
								    privateRoom = 0;
							    } else {
								    MBchat.updateables.message.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Enters the Room'));
								    addUser(msg.user);
							    }
							    MBchat.sounds.roomMove();	
							    break;
						    case 'WJ' :
							    if(privateRoom == msg.rid) { //only interested in people joining my private room
								    userDiv = addUser(msg.user);
								    userDiv.addClass('priv');
							    }
							    break;
						    case 'WL' :
							    if(privateRoom == msg.rid) { //only interested in people leaving my private room
								    userDiv.removeClass('priv');
//TODO might need system that records with this user was only there because of the whisper and remove him completely
							    }
							    break;
						    default :  // ignore anything else
							    break;
						    }

						}
					}
				};

			}(),
			message : function () {
				var messageList; 
				var mlScroller;
				var lastId;
				return {
					init: function () {
						messageList = $('chatList');
						mlScroller = new Fx.Scroll(messageList,{'link':'cancel'});
						lastId = null;
					},
					enterRoom: function(rid) {
						lastId = null;  //prepare to fill up with old messages
						messageList.removeClass('whisper');
						messageList.empty();
						messageList.addClass('chat');
						$('roomNameContainer').empty();
						$('inputContainer').removeClass('hide');
						if(!me.is(BLIND)) {
						    $('emoticonContainer').removeClass('hide');
						}
						$('entranceHall').addClass('hide');	
						var exit = $('exit');
						exit.addClass('exit-r');
						exit.removeClass('exit-f');
						room = new Room(rid,'Loading',0); //set upi room first so random calls afterwards don't screw me
						var request = new ServerReq('client/room.php',function(response) {
							response.room.rid = response.room.rid.toInt();
							room.set(response.room);
							MBchat.sounds.roomMove();
							var soundEnabled = $('soundEnabled').checked;
							$('soundEnabled').checked = false; //remember if sound was enabled but turn it off
							response.messages.each(function(item) {
								item.lid = item.lid.toInt();
								item.rid = item.rid.toInt();
								item.user.uid = item.user.uid.toInt();
								if(!lastId) lastId = item.lid - 1;
								MBchat.updateables.processMessage(item);
							});
							$('soundEnabled').checked = soundEnabled; //turn it on again
							if(response.listid)lastId = response.lastid.toInt();
						//Display room name at head of page
							var el = new Element('h1')
								.set('text', room.name )
								.inject('roomNameContainer');
							if (room.type == MODERATED && 
								(me.is(MOD) || me.is(SPEAKER) )) { //Can't go to private room here
								var whisperBoxes = $$('.whisperBox');
								whisperBoxes.each ( function (whisperBox) {
									var privateBox = whisperBox.getElement('.private');
									privateBox.removeClass('private');
									privateBox.addClass('nonprivate');
									privateBox.removeEvent('click',goPrivate);
								});
							}
								
							MBchat.updateables.online.show(room.rid);	//Show online list for room	
							$('messageText').focus();							
						}).transmit({'rid' : rid});
						MBchat.sounds.resetTimer();
					},
					leaveRoom: function () {
						lastId = null;
						var request = new ServerReq ('client/exit.php',function(response) {
							response.messages.each(function(item) {
								item.lid = item.lid.toInt();
								item.rid = item.rid.toInt();
								item.user.uid = item.user.uid.toInt();
								if(!lastId) lastId = item.lid -1;
								MBchat.updateables.processMessage(item);
							});
							if(response.lastid)lastId = response.lastid.toInt();
							MBchat.updateables.online.show(0);	//Show online list for entrance hall
						}).transmit({'rid' : room.rid});
						//we might have been in a room that stopped me going to private room
						if (room.type == MODERATED && 
							(me.is(MOD) || me.is(SPEAKER) )) { //Can't go to private room here	
							var whisperBoxes = $$('.whisperBox');
							whisperBoxes.each ( function (whisperBox) {
								var privateBox = whisperBox.getElement('.nonprivate');
								if (privateBox) {
									privateBox.removeClass('nonprivate');
									privateBox.addClass('private');
									privateBox.addEvent('click',goPrivate);
								}
							});
						}
						room.set (entranceHall);;   //Set up to be in the entrance hall 
						messageList.removeClass('chat');
						messageList.empty();
						messageList.addClass('whisper');
						$('roomNameContainer').empty();
						var el = new Element('h1')
							.set('text', room.name)
							.inject('roomNameContainer');
						$('inputContainer').addClass('hide');
						$('emoticonContainer').addClass('hide');
						$('entranceHall').removeClass('hide');
						var exit = $('exit');	
						exit.addClass('exit-f');
						exit.removeClass('exit-r');
						MBchat.sounds.resetTimer();
					},
					processMessage: function (msg) {
						var lid = msg.lid.toInt();
						if (lastId < lid) {
							lastId = lid;
							switch(msg.type) {
							case 'RE' :
								if (privateRoom == 0) {
									if (room.rid == 0  || msg.rid == room.rid) {
										if (room.rid == 0) {
											this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' leaves for a Room'));
										} else {
											this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Enters the Room'));
										}
										MBchat.sounds.roomMove();
									}
								}
								break;
							case 'RX' :
								if (privateRoom == 0) {
									if (room.rid == 0  || msg.rid == room.rid) {
										if (room.rid == 0) {
											this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Re-enters the Hall'));
										} else {
											this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Leaves the Room'));
										}
										MBchat.sounds.roomMove();
									}
								}
								break;
							case 'WH' :
								if (MBchat.updateables.whispers.isWhisperingIn(msg.rid)) {
									if (privateRoom == 0) {
										var whisper = new Element('span',{'class':'whisper'});
										var othersAdded = false;
										if (me.uid == msg.user.uid) {
											whisper.appendText('(whispers to')
										} else {
											whisper.appendText('(whispers to me')
											othersAdded = true;
										}
										//whisperList says who the other whisperers are
										var whisperIdStr = 'W'+msg.rid;
										var whisperers = $(whisperIdStr).getElement('.whisperList').getChildren();
										whisperers.each(function(whisperer) {
											var uid = whisperer.get('id').substr(whisperIdStr.length+1).toInt();
											if (uid != msg.user.uid) { //This is not the whisperer so include
												if(othersAdded) {
													whisper.appendText(', ');
												}else {
													whisper.appendText(' ');
													othersAdded = true;
												}
												var newWhisperer = whisperer.clone(); //Make a clone to remove Id 
												newWhisperer.inject(whisper);
											};
										});
										whisper.appendText(') ') ;
										this.displayMessage(lastId,msg.time,msg.user,whisper.get('html') + msg.message);
									} else {
										this.displayMessage(lastId,msg.time,msg.user,'(private) ' + msg.message);
									}
									MBchat.sounds.messageArrives();
								}
								break;
							case 'WJ' :
								if(msg.user.uid != me.uid && MBchat.updateables.whispers.isWhisperingIn(msg.rid)) {
									this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Joins your whisper box'));
								}
								break;
							case 'WL' :
								if(msg.user.uid != me.uid&& MBchat.updateables.whispers.isWhisperingIn(msg.rid)) {
									this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Leaves your whisper box'));
								}
								break;
							default:
								break;
							}
						}
						//Regardless of whether we;ve seen these before we are going to display them again.
						if(privateRoom == 0) {
							if (msg.rid == room.rid) {
								switch(msg.type) {
								case 'ME' :
									this.displayMessage(lastId,msg.time,msg.user,msg.message);
									MBchat.sounds.messageArrives();
									break;
								case 'LT' :
									this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Logs Out (timeout)'));
									MBchat.sounds.roomMove();
									break;
								case 'LI' :
									this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Logs In to Chat'));
									MBchat.sounds.roomMove();
									break;
								case 'LO' :
									this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Logs Out'));
									MBchat.sounds.roomMove();
									break;
								default:
									break;
								}
							}
						}
					},
					displayMessage: function(lid,time,user,msgText,noLimit) {
						var addLeadingZeros = function(number) {
							number = number.toString();
							if(number.length < 2)
								number = '0'+number;
							return number;
						};
						var div = new Element('div');
						if (lid != 0) div.set('id','L'+lid);	//This should be all messages except errors
						var date = new Date(time.toInt()*1000);
						var hour = date.getHours();
						var suffix = ' am';
						if (hour > 12 ) {
							suffix = ' pm';
							hour = hour - 12;
						} else {
							if (hour == 12) {
								suffix = ' pm';
							}
						}
						var timeEl = new Element('span',{
							'class':'time', 
							'text':	addLeadingZeros(hour) + ':' + addLeadingZeros(date.getMinutes()) + ':'
								+ addLeadingZeros(date.getSeconds()) + suffix });
						timeEl.inject(div);
						displayUser(user,div);
						msgText = replaceHyperLinks (msgText);  //replace Hperlinks first
						msgText = replaceEmoticons(msgText); //Then replace emoticons.
						var span = new Element('span',{'html': msgText }) ;
						span.inject(div);
						if(!noLimit) {
							while (messageList.getChildren().length >= messageListSize) {
								messageList.getFirst().destroy();
							}
						}	
						if((!messageList.getLast()) || (messageList.getLast().get('class') == 'rowOdd') ) {
							div.addClass('rowEven');
						} else {
							div.addClass('rowOdd');
						}
						div.inject(messageList);
						if ($('autoScroll').checked) mlScroller.toBottom();
					}
				}
			}(),
			whispers : function () {
				var lastId = null;
				var channels = null;
				var activeWb = null;
				var addUser = function (user,whisperBox) {
					var widStr = whisperBox.get('id');
					var whisperList = whisperBox.getElement('.whisperList');
					var whisperers = whisperList.getChildren();
					if( whisperers.every(function(whisperer) {
						if (whisperer.get('id').substr(widStr.length+1).toInt() == user.uid ) {
								return false;  // Found it, so do nothing
						}
						return true;
					})) {
						// if we get here, we haven't found the user, so we need to add him
						var span = displayUser(user,whisperList);
						span.addClass('whisperer');
						span.set('id',widStr+'U'+user.uid);
						return true
					}
					return false;
				}
				var whisperSubmit = function(whisper,wid) {
					whisperReq.transmit({
						'wid':wid,
						'rid':room.rid,
						'lid':MBchat.updateables.poller.getLastId(),
						'text':whisper.getElement('.whisperInput').value});
					whisper.getElement('.whisperInput').value = '';
					MBchat.sounds.resetTimer();
				}
				var createWhisperBox = function (wid,user) {
					var template = $('whisperBoxTemplate');
					var whisper = template.clone();
					whisper.addClass('whisperBox');
					whisper.set('id','W'+wid);
					var whisperList = whisper.getElement('.whisperList');
					whisperList.addClass('loading');
					if (user) {
						//inject a user element into box
						var whisperer = displayUser(user,whisperList);
						whisperer.addClass('whisperer');
						whisperer.set('id', 'W'+wid+'U'+user.uid);
					}
					var getWhisperersReq = new ServerReq('client/getwhisperers.php',function(response) {
						whisperList.removeClass('loading');
						response.whisperers.each(function(whisperer) {
							whisperer.uid = whisperer.uid.toInt();
							if(me.uid != whisperer.uid) addUser(whisperer,whisper);
						});
					});
					getWhisperersReq.transmit({'wid':wid});
					//Now we have to make the whole thing draggable.
					var closeBox = whisper.getElement('.closeBox');
					var leaveWhisper = new ServerReq('client/leavewhisper.php',function(response) {
						whisper.destroy();
						$('content').setStyles(contentSize);
					});
					closeBox.addEvent('click', function(e) {
						leaveWhisper.transmit({'wid': wid});
					});
					var privateBox = whisper.getElement('.private');
					//can't go private if a key character in a modded room
					if (room.type == MODERATED && (me.is(MOD) || me.is(SPEAKER))) {
						privateBox.removeClass('private');
						privateBox.addClass('nonprivate');
					} else {
						privateBox.addEvent('click', goPrivate);
					}
					whisper.getElement('form').addEvent('submit', function(e) {
						e.stop();
						whisperSubmit(whisper,wid);
					});
					whisper.addEvent('keydown',function(e) {
						if(!e.alt) return;
						switch (e.key) {
						case 'p' :
							if (!(room.type == MODERATED && (me.is(MOD) || me.is(SPEAKER)))) {
								e.stop();
								var boundPrivateBox = goPrivate.bind(privateBox);
								boundPrivateBox();
							}
							break;
						case 's' :
							e.stop();
							whisperSubmit(whisper,wid);
							break;
						case 'x' :
							e.stop();
							leaveWhisper.transmit({'wid': wid});
							break;
						default:
							break;
						}

					});
					whisper.addClass('wBactive');
					whisper.inject(document.body);
					activeWb = whisper;
					var position = whisper.getCoordinates();
					position.top = position.top + (Math.random()-0.5) * 50;
					position.left = position.left + (Math.random()-0.5) * 150;
					whisper.setStyles(position);
//(see if helps usability)		whisper.getElement('.whisperInput').focus();
					var drag = new Drag(whisper,{
						handle:whisper.getElement('.dragHandle'),
						snap:0,
						onStart: function(el) {
							if (activeWb) {
								activeWb.removeClass('wBactive');
							}
							activeWb = el;
							el.addClass('wBactive');
						},
						onComplete: function(el) {
							el.getElement('.whisperInput').focus();
						}
					});
					$('content').setStyles(contentSize);
					return whisper;
				}
				var removeUser = function(whisperBox,uid) {
					if (me.uid == uid) {
						whisperBox.destroy();
						$('content').setStyles(contentSize);
					} else {
						var span = $(whisperBox.get('id')+'U'+uid);
						if (span) {
							span.destroy();
						}
						if (whisperBox.getElement('.whisperList').getChildren().length == 0 ) {
							whisperBox.destroy();
							$('content').setStyles(contentSize);
						}
					}
				}
				return {
					init: function (lid) {
						lastId = lid;
					},
					whisperWith : function (user,el,event) {
					    if(me.is(BLIND)) {
						    if(privateRoom == 0) {
							    //See if we are already in a whisper with this user
							    var whisperBoxes = $$('.whisperBox');
							    if (whisperBoxes.every(function(whisperBox,i) {
								    var widStr = whisperBox.get('id');
								    var whisperers = whisperBox.getElement('.whisperList').getChildren();   //gets users in whisper
								    if (whisperers.length == 1) { //we only want to worry about this if only other person
									    if (whisperers[0].get('id').substr(widStr.length+1).toInt() == user.uid) {
										    this.start(whisperBox.getCoordinates());
										    if(activeWb) {
											    activeWb.removeClass('wBactive');
										    }
										    activeWb = whisperBox;
										    whisperBox.addClass('wBactive');
										    whisperBox.getElement('.whisperInput').focus();
										    return false;
									    }
								    }
								    return true;		 
							    })){ 
					    //If we get here we have not found that we already in a one on one whisper with this person, so now we have to create a new Whisper					
								    var getNewWhisperReq = new ServerReq('client/newwhisper.php',function(response) {
									    if(response.wid != 0) {
										    var user = response.user
										    user.uid = user.uid.toInt();
										    var whisper = createWhisperBox(response.wid.toInt(),user);
									    } 
								    });
								    getNewWhisperReq.transmit({'wuid':user.uid});
							    }
						    } else {
							    droppable = $('W'+privateRoom); //This was a private room drop
							    $('U'+user.uid).addClass('priv'); //Show in room on online list
							    //See if already in whisper with this user
							    if (addUser (user,droppable) ) {
								    var addUserToWhisperReq = new ServerReq('client/joinwhisper.php',function(response) {});
								    addUserToWhisperReq.transmit({
									    'wuid':user.uid,
									    'wid':droppable.get('id').substr(1).toInt()});
							    }
						    }
					    } else {
						    var startPosition = el.getCoordinates();
						    var dropNew;
						    if (room.rid == 0 && privateRoom == 0 ) {
							    dropNew = $('chatList');
						    } else {
							    dropNew = $('inputContainer');
						    }
						    var dropZones = $$('.whisperBox');
						    var dragMan = new Element('div',{'class':'dragBox'});
						    dragMan.setStyles(startPosition);
						    var dragDestroy = function() {
							    dragMan.destroy();
							    $('content').setStyles(contentSize);
						    }
						    el.addEvent('mouseup', dragDestroy);
						    displayUser(user,dragMan);
						    var dragReturn = new Fx.Morph(dragMan, {
							    link: 'cancel',
							    duration: 500,
							    transition: Fx.Transitions.Quad.easeOut,
							    onComplete: function (dragged) {
								    dragged.destroy();
								    $('content').setStyles(contentSize);
							    }
						    });
						    dragMan.inject(document.body);
						    dragMan.addEvent('mouseup',dragDestroy);
						    dropZones.include(dropNew);
						    var drag = new Drag.Move(dragMan,{
							    droppables:dropZones,
							    onSnap: function(element) {
								    element.removeEvent('mouseup',dragDestroy);
							    },
							    onDrop: function(element, droppable){
								    dropZones.removeClass('dragOver');
								    if(droppable) {
									    if(droppable == dropNew && privateRoom == 0) {
										    //See if we are already in a whisper with this user
										    var whisperBoxes = $$('.whisperBox');
										    if (whisperBoxes.every(function(whisperBox,i) {
											    var widStr = whisperBox.get('id');
											    var whisperers = whisperBox.getElement('.whisperList').getChildren();   //gets users in whisper
											    if (whisperers.length == 1) { //we only want to worry about this if only other person
												    if (whisperers[0].get('id').substr(widStr.length+1).toInt() == user.uid) {
													    this.start(whisperBox.getCoordinates());
													    if(activeWb) {
														    activeWb.removeClass('wBactive');
													    }
													    activeWb = whisperBox;
													    whisperBox.addClass('wBactive');
													    whisperBox.getElement('.whisperInput').focus();
													    return false;
												    }
											    }
											    return true;		 
										    }, dragReturn)){ 
								    //If we get here we have not found that we already in a one on one whisper with this person, so now we have to create a new Whisper					
											    var getNewWhisperReq = new ServerReq('client/newwhisper.php',function(response) {
												    if(response.wid != 0) {
													    var user = response.user
													    user.uid = user.uid.toInt();
													    var whisper = createWhisperBox(response.wid.toInt(),user);
													    dragReturn.start(whisper.getCoordinates()); //move towards it
												    } else {
												    //logged out before we started whisper
													    dragReturn.start($('onlineList').getCoordinates());
												    } 
											    });
											    getNewWhisperReq.transmit({'wuid':user.uid});
										    }
									    } else {
										    if (droppable == dropNew) {
											    droppable = $('W'+privateRoom); //This was a private room drop
											    $('U'+user.uid).addClass('priv'); //Show in room on online list
											    dragReturn.start($('chatList').getCoordinates());
										    } else {
											    dragReturn.start(droppable.getCoordinates());
										    }
										    //See if already in whisper with this user
										    if (addUser (user,droppable) ) {
											    var addUserToWhisperReq = new ServerReq('client/joinwhisper.php',function(response) {});
											    addUserToWhisperReq.transmit({
												    'wuid':user.uid,
												    'wid':droppable.get('id').substr(1).toInt()});
										    }
									    }
								    } else {
									    dragReturn.start(startPosition);  // should make dragman return on online list
								    }
							    },
       							onEnter: function(element, droppable){
								    droppable.addClass('dragOver');
							    },
     							onLeave: function(element, droppable){
								    droppable.removeClass('dragOver');
							    }							
						    });
						    drag.start(event);
						}
						$('content').setStyles(contentSize);
					},
					processMessage: function (msg) {
						var lid = msg.lid.toInt();
						if (lastId < lid) {
							lastId = lid;
							switch(msg.type) {
							case 'WJ' :
								if ($$('.whisperBox').every(function(whisperBox) {
									var whisperStr = whisperBox.get('id');
									if(whisperStr.substr(1).toInt() == msg.rid) {
										if (me.uid != msg.user.uid) {
											addUser(msg.user,whisperBox);
										}
										return false;
									}
									return true;
								})) {
									// If we get here, this is a WJ for a whisper box we don't have
									if (me.uid == msg.user.uid ) {
										//OK - someone else has selected me to be in a whisper
										createWhisperBox(msg.rid.toInt());  //but without (yet) any other user
										MBchat.sounds.newWhisper();
									}
									// Throw others away 
								}
								break;
							case 'LT':
							case 'LO':
								var whisperBoxes = $$('.whisperBox');
								if (whisperBoxes) {
									whisperBoxes.each(function(whisperBox) {
										removeUser(whisperBox,msg.user.uid);
									});
								}
								break;
							case 'WL' :
								var whisperBox = $('W'+msg.rid);
								if(whisperBox) {
									removeUser(whisperBox,msg.user.uid);
								}
								break;
							default:
							//ignore the rest of the messages
								break;
							}
						}
					},
					updateWhisperers: function(wid,whisperers) {
						whisperBox = $('W'+wid);
						if (whisperBox) { //just in case it disappeared in the mean time
							var whisperList = whisperBox.getElement('.whisperList');
							whisperList.removeClass('loading');
							whisperers.each(function(whisperer) {
								whisperer.uid = whisperer.uid.toInt();
								//inject a user element into box
								if(me.uid != whisperer.uid) {
									addUser(whisperer,whisperBox);
								}
							});
						}
					},
					isWhisperingIn : function (wid) {
						var whisperBoxes = $$('.whisperBox');
						return !whisperBoxes.every(function(whisperBox) {
							if(wid == whisperBox.get('id').substr(1).toInt()) {
								return false;
							}
							return true;
						});
					}
				};
			}(),
			logger : function () {
				var logControls;
				var printLog;
				var messageList;
				var timeShowStartLog;
				var timeShowEndLog;
				var aSecond = 1000;
				var aMinute = 60*aSecond;
				var anHour = 60*aMinute;
				var sixHours = 6*anHour;
				var aDay = 24*anHour;
				var aWeek = 7*aDay;
				var earliest;
				var startTimeOffset;
				var endTime;
				var timeChange;

				var timeShow = function() {
					timeShowEndLog.set('text',endTime.toLocaleString());
					timeShowStartLog.set('text', new Date(endTime.getTime() - startTimeOffset).toLocaleString());
				};
				var intervalCounterId = null;
				var intervalCounter;
				var getInterval = function() {
					var i = intervalCounter;
					intervalCounter++;
					if(i < logOptions.secondstep) return aSecond;
					if(i < logOptions.minutestep) return aMinute;
					if(i < logOptions.hourstep) return anHour;
					if(i < logOptions.sixhourstep) return sixHours;
					return aDay;
				};
				var logRid;
				var printQuery;
				var processMessage = function (msg) {
					var message = function (txt) {
						MBchat.updateables.message.displayMessage(msg.lid,msg.time,chatBot,chatBotMessage(msg.user.name + ' ' + txt),true);
					}
					switch (msg.type) {
					case 'LI':
						message('Logs In ' + msg.message);
						break;
					case 'LO':
						message('Logs Out ' + msg.message);
						break;
					case 'LT':
						message('Logs Out (timeout)');
						break;
					case 'RE':
						message('Enters Room');
						break;
					case 'RX':
						message('Leaves Room');
						break;
					case 'WJ':
						message('Joins whisper no: ' + msg.rid);
						break;
					case 'WL':
						message('Leaves whisper no: ' + msg.rid);
						break;
					case 'ME':
						MBchat.updateables.message.displayMessage(msg.lid,msg.time,msg.user,msg.message,true);
						break;
					case 'WH':
						MBchat.updateables.message.displayMessage(msg.lid,msg.time,msg.user,'(whispers to :' +msg.rid+')'+msg.message,true);
						break;
					case 'LH':
						message('Reads Log');
					default:
					// Do nothing with these
						break;
					}
				}
				var request = new ServerReq('client/log.php',function(response) {
					messageList.removeClass('loading');
					if(response) {
						response.messages.each(function(item) {
							item.lid = item.lid.toInt();
							item.rid = item.rid.toInt();
							item.user.uid = item.user.uid.toInt();
							processMessage(item);
						});
					}
				});
				var fetchLogDelay;
				var fetchLog = function() {
					messageList.empty();
					messageList.addClass('loading');
					request.transmit({
						'rid' : logRid,
						'start' : Math.floor(new Date(endTime.getTime()-startTimeOffset).getTime()/1000),
						'end': Math.ceil(endTime.getTime()/1000 )});
				};
				return {
					init: function() {
						logControls = $('logControls');
						messageList = $('chatList');
						printLog = $('printLog');
						printLog.addEvent('click',function(e) {
							printQuery += '&start='+ Math.floor(new Date(endTime.getTime()-startTimeOffset).getTime()/1000);
							printQuery += '&end='+Math.ceil(endTime.getTime()/1000);
							printQuery += '&tzo='+endTime.getTimezoneOffset();
							MBchat.logout();
							window.location = 'client/print.php?' + printQuery ; 
						});
						timeShowStartLog = $('timeShowStartLog');
						timeShowEndLog = $('timeShowEndLog');
						logOptions.minutestep += logOptions.secondstep;  //Operationally this is better, so set it up
						logOptions.hourstep += logOptions.minutestep;
						logOptions.sixhourstep += logOptions.hourstep;
						$('minusStartLog').addEvents({
							'mousedown' : function (e) {
								var incrementer = function() {
									startTimeOffset += getInterval();
									if (endTime.getTime()-startTimeOffset < earliest) {
										startTimeOffset = endTime.getTime()- earliest;
									}
									timeShow();
								};

								$clear(fetchLogDelay);
								if(intervalCounterId) $clear(intervalCounterId);
								intervalCounter=0;

								incrementer(); //do first one
								intervalCounterId = incrementer.periodical(logOptions.spinrate);
							},
							'mouseup' : function (e) {
								$clear(intervalCounterId);
								$clear(fetchLogDelay);
								fetchLogDelay = fetchLog.delay(logOptions.fetchdelay);
							}
						});
						$('plusStartLog').addEvents({
							'mousedown' : function (e) {
								var decrementer = function() {
									if (startTimeOffset > 0) {
										startTimeOffset -= getInterval();
									}
									if (startTimeOffset < 0) startTimeOffset = 0;
									timeShow();
								};

								$clear(fetchLogDelay);
								if(intervalCounterId) $clear(intervalCounterId);
								intervalCounter=0;

								decrementer(); //do first one
								intervalCounterId = decrementer.periodical(logOptions.spinrate);
							},
							'mouseup' : function (e) {
								$clear(intervalCounterId);
								$clear(fetchLogDelay);
								fetchLogDelay = fetchLog.delay(logOptions.fetchdelay);
							}
						});
						$('minusEndLog').addEvents({
							'mousedown' : function (e) {
								var decrementer = function() {
									var oSTO = startTimeOffset;
									if (startTimeOffset > 0) {
										startTimeOffset -= getInterval();
									}
									if (startTimeOffset < 0) startTimeOffset = 0;
									endTime = new Date(endTime.getTime()-oSTO+startTimeOffset);
									timeShow();
								};

								$clear(fetchLogDelay);
								if(intervalCounterId) $clear(intervalCounterId);
								intervalCounter=0;

								decrementer(); //do first one
								intervalCounterId = decrementer.periodical(logOptions.spinrate);
							},
							'mouseup' : function (e) {
								$clear(intervalCounterId);
								$clear(fetchLogDelay);
								fetchLogDelay = fetchLog.delay(logOptions.fetchdelay);
							}
						});
						$('plusEndLog').addEvents({
							'mousedown' : function (e) {
								var incrementer = function() {
									var oSTO = startTimeOffset;
									var maxOffset = new Date().getTime() - endTime.getTime() + oSTO;
									startTimeOffset += getInterval();
									if (startTimeOffset > maxOffset) startTimeOffset = maxOffset;
									endTime = new Date(endTime.getTime() - oSTO + startTimeOffset);
									timeShow();
								};
								
								$clear(fetchLogDelay);
								if(intervalCounterId) $clear(intervalCounterId);
								intervalCounter=0;

								incrementer(); //do first one
								intervalCounterId = incrementer.periodical(logOptions.spinrate);
							},
							'mouseup' : function (e) {
								$clear(intervalCounterId);
								$clear(fetchLogDelay);
								fetchLogDelay = fetchLog.delay(logOptions.fetchdelay);
							}
						});
					},
					startLog: function (rid,roomName) {
						logRid = rid;
						MBchat.updateables.poller.stop(); //presence polls still happen
						messageList.removeClass('whisper');
						messageList.removeClass('chat');
						messageList.addClass('logging');
						messageList.empty();
						printQuery = 'uid='+auth.uid+'&pass='+auth.pass+'&rid='+rid+'&room='+roomName ;
						$('inputContainer').addClass('hide');
						$('emoticonContainer').addClass('hide');
						$('roomNameContainer').empty();
						$('entranceHall').addClass('hide');
						var exit = $('exit');	
						exit.removeClass('exit-f');
						exit.addClass('exit-r');
						$('soundOptions').addClass('hide');
						$('onlineListContainer').addClass('hide');
						logControls.removeClass('hide');
						endTime = new Date();
						startTimeOffset = anHour;
						earliest = endTime.getTime() - aWeek;
						timeShow();
						fetchLogDelay = fetchLog.delay(logOptions.fetchdelay);

					},
					returnToEntranceHall : function() {
						logControls.addClass('hide');
						messageList.removeClass('logging');
						$('header').removeClass('hide');
						$('content').removeClass('hide');
						$('entranceHall').removeClass('hide');	
						$('soundOptions').removeClass('hide');
						$('onlineListContainer').removeClass('hide');
						MBchat.updateables.poller.start();
						MBchat.updateables.message.leaveRoom();	//go To Entrance Hall
					}
				};
			}()
		};
	}()
  }; 


}();
