MBchat = function () {
	var version = 'v1.1.1';
	var me;
	var myRequestOptions;
	var entranceHall;  //Entrance Hall Object
	var room;
	var privateRoom;
	var chatBot;
	var messageListSize;
	var hyperlinkRegExp;
	var emoticonSubstitution;
	var emoticonRegExpStr;
	var displayUser = function(user,container) {
		var el = new Element('span',{'class' : user.role, 'text' : user.name });
		el.inject(container);
		return el;
	};
	var displayErrorMessage = function(txt) {
		var msg;
		if (txt) {
			msg = '<span class="errorMessage">'+txt+'</span>';
		} else {
			msg = '<span class="errorMessage">Server Error</span>';
		}
		var d = new Date();
		MBchat.updateables.message.displayMessage(0,d.getTime()/1000,chatBot,msg);  //need to convert from millisecs to secs
	};
	var chatBotMessage = function (msg) {
		return '<span class="chatBotMessage">'+msg+'</span>';
	};
	var messageReq = new Request.JSON({
		url: 'message.php',
		autoCancel: true,
		onComplete : function(response,errorMsg) {
			if(response) {
				MBchat.updateables.poller.pollResponse(response)
			} else {
				displayErrorMessage(errorMsg);
			}
		}
	});
	var whisperReq = new Request.JSON({
		url: 'whisper.php',
		autoCancel: true,
		onComplete : function(response,errorMsg) {
			if(response) {
				MBchat.updateables.poller.pollResponse(response)
			} else {
				displayErrorMessage(errorMsg);
			}
		}
	});
	var privateReq = new Request.JSON({
		url:'private.php',
		autoCancel:true,
		onComplete:function(response,errorMsg) {
			if(response) {
				MBchat.updateables.poller.pollResponse(response)
			} else {
				displayErrorMessage(errorMsg);
			}
		}
	});
	var goPrivate = function() {
		privateReq.get($merge(myRequestOptions,{
			'wid': this.getParent().get('id').substr(1).toInt(), 
			'lid' : MBchat.updateables.poller.getLastId(),
			'rid' : room.rid}));
	};
	var contentSize;
return {
	init : function(user,pollOptions,chatBotName, entranceHallName, msgLstSz) {
		var span = $('version');
		span.set('text', version);
		
// Save key data about me
		me =  user; 
		myRequestOptions = {'user': me.uid,'password': me.password};  //Used on every request to validate
		entranceHall = {rid:0, name: entranceHallName, type: 'O'};
		privateRoom = 0;
		chatBot = {uid:0, name : chatBotName, role: 'C'};  //Make chatBot like a user, so can be displayed where a user would be
		messageListSize = msgLstSz;  //Size of message list
// We need to setup all the entrance hall

		var roomgroups = $$('.rooms');
		var roomTransition = new Fx.Transition(Fx.Transitions.Bounce, 6);
		roomgroups.each( function (roomgroup,i) {
			var rooms = roomgroup.getElements('.room');
			var fx = new Fx.Elements(rooms, {link:'cancel', duration: 500, transition: roomTransition.easeOut });
			rooms.each( function(room, i){
				var request;
				room.addEvent('mouseenter', function(e){
					//adjust width of room to be wide
					var obj = {};
					obj[i] = {'width': [room.getStyle('width').toInt(), 219]};
					rooms.each(function(otherRoom, j){
						if (otherRoom != room){
							var w = otherRoom.getStyle('width').toInt();
							if (w != 67) obj[j] = {'width': [w, 67]};
						}
					});
					fx.start(obj);
					// Set up online list for this room
					MBchat.updateables.online.show(room.get('id').substr(1).toInt());
				});
				room.addEvent('mouseleave', function(e){
					var obj = {};
					rooms.each(function(other, j){
						obj[j] = {'width': [other.getStyle('width').toInt(), 105]};
					});
					fx.start(obj);
					MBchat.updateables.online.show(0);  //get entrance hall list
				});
				room.addEvent('click', function(e) {
					e.stop();			//browser should not follow link
					MBchat.updateables.message.enterRoom(room.get('id').substr(1).toInt());
				});
				if (me.role == 'A' || me.role == 'L' || room.hasClass('committee') ) {
					room.addEvent('controlclick', function(e) {
						e.stop();
						MBchat.updateables.logger.startLog(room.get('id').substr(1).toInt());
					});
				};
			});
		});

		var exit = $('exit');
		var exitfx = new Fx.Morph(exit, {link: 'cancel', duration: 500, transition: roomTransition.easeOut});
		exit.addEvent('mouseenter',function(e) {
			exitfx.start({width:100});
		});
		exit.addEvent('mouseleave', function(e) {
			exitfx.start({width:50});
		});
		exit.addEvent('click', function(e) {
			e.stop();
			if (e.control && me.additional) {
				MBchat.updateables.logger.startLog(room.rid);
			} else {
				if (privateRoom != 0) {
					privateReq.get($merge(myRequestOptions,{
						'wid':  0, 
						'lid' : MBchat.updateables.poller.getLastId(),
						'rid' : room.rid })); 
				} else {
					if (room.rid == 0 ) {
						MBchat.logout();
						window.location = '/forum' ; //and go back to the forum
					} else {
						MBchat.updateables.message.leaveRoom();
					}
				}
			}
		});
		hyperlinkRegExp = new RegExp('(^|\\s|>)(((http)|(https)|(ftp)|(irc)):\\/\\/[^\\s<>]+)(?!<\\/a>)','gm');
		//Set up emoticons
		emoticonSubstitution = new Hash({});
		
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

		$('messageForm').addEvent('submit', function(e) {
			e.stop();
			if (privateRoom == 0 ) {
				messageReq.get($merge(myRequestOptions,{
					'rid':room.rid,
					'lid':MBchat.updateables.poller.getLastId(),
					'text':$('messageText').value}));
			} else {
				whisperReq.get($merge(myRequestOptions,{
					'wid':privateRoom,
					'rid':room.rid,
					'lid':MBchat.updateables.poller.getLastId(),
					'text':$('messageText').value}));
			}

			$('messageText').value = '';
			MBchat.sounds.resetTimer();
		});
		contentSize = $('content').getCoordinates();
		window.addEvent('resize', function() {
			contentSize = $('content').getCoordinates();
		});

		room = {rid:0, name: 'Entrance Hall', type : 'O'};   //Set up to be in the entrance hall
		MBchat.updateables.init(pollOptions);
		MBchat.sounds.init();		//start sound system
		MBchat.updateables.online.show(0);	//Show online list for entrance hall
		
	},
	logout: function () {
		var logoutRequest = new Request ({url: 'logout.php'}).get(myRequestOptions);
	},
	sounds: function () {
		var music = false;
		var musicEnabled;
		var soundEnabled;
		var playAgain = true;
		var Timer = {counter:30 , start : 30 }; //Units of 10 seconds
		var countDown = function() {
			if (this.counter > 0 ) this.counter-- ;
			var soundDelay = 6*$('soundDelay').value ;
			if ( soundDelay != this.start) {
				this.counter += soundDelay -this.start ;
				this.start = soundDelay;
				if (this.counter < 0) this.counter = 0;
				Cookie.write('soundDelay', $('soundDelay').value.toString(),{duration:50});
			}
			
			if (!music && soundReady) {
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
				var sd = Cookie.read('soundDelay')
				Cookie.write('soundDeley',sd,{duration:50}); //Just write so validity starts again
				if (sd) {
					var delayMin = sd.toInt()
					Timer.start = 6 * delayMin;
					Timer.counter = Timer.start;
					$('soundDelay').value = delayMin;
				}
				countDown.periodical(10000,Timer); //countdown in 10 sec chunks				
				musicEnabled = $('musicEnabled');
				var mu = Cookie.read('musicEnabled');
				Cookie.write('musicEnabled', mu ,{duration:50});
				if (mu) {
					if (mu == 'true') {
						musicEnabled.checked = true;
					} else {
						musicEnabled.checked = false;
					}
				}
				musicEnabled.addEvent('click', function(e) {
					if(!musicEnabled.checked) {
						soundManager.stop('music');
						playAgain = true;
						Cookie.write('musicEnabled', 'false',{duration:50});
					} else {
						Cookie.write('musicEnabled', 'true',{duration:50});
					}

				});
				soundEnabled = $('soundEnabled');
				var so = Cookie.read('soundEnabled');
				Cookie.write('soundEnabled', so,{duration:50});
				if (so) {
					if (so == 'true') {
						soundEnabled.checked = true;
					} else {
						soundEnabled.checked = false;
					}
				}
				soundEnabled.addEvent('click', function(e) {
					if(!soundEnabled.checked) {
						Cookie.write('soundEnabled', 'false',{duration:50});
					} else {
						Cookie.write('soundEnabled', 'true',{duration:50});
					}

				});
				
			},
			resetTimer: function() {
				Timer.counter = Timer.start;
			},
			roomMove : function() {
				if(soundReady && soundEnabled.checked) {
					if(room.rid == 4) { //special for vamp club
						soundManager.play('creaky');
					} else {
						soundManager.play('move');
					}
				}
			},
			newWhisper: function() {
				if(soundReady && soundEnabled.checked) soundManager.play('whispers');
			},
			messageArrives:function() {
				if(soundReady && Timer.counter == 0 && soundEnabled.checked) soundManager.play('speak');
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
			init : function (pollOptions) {
				MBchat.updateables.online.init();
				MBchat.updateables.message.init();
				MBchat.updateables.poller.init(pollOptions);
				MBchat.updateables.whispers.init(pollOptions.lastid.toInt());
				MBchat.updateables.logger.init();
			},
			processMessage : function(message) {
				MBchat.updateables.online.processMessage(message);
				MBchat.updateables.message.processMessage(message);
				MBchat.updateables.whispers.processMessage(message);
			},
			poller : function() {
				var presenceInterval;
				var presenceCounter = 0;
				var pollInterval;
				var pollerId;
				var lastId = null;

				var pollRequest = new Request.JSON({
					url: 'poll.php',
					autoCancel: true,
					onComplete : function(response,errorMsg) {
						if(response) {
							MBchat.updateables.poller.pollResponse(response)
						} else {
							displayErrorMessage(errorMsg);
						}
					}
				});
				var poll = function () {
					if (this.online.getCurrentRid() >= 0) {
						var pollRequestOptions = {'lid':lastId, 'rid': this.online.getCurrentRid() };
						presenceCounter++;
						if (presenceCounter > presenceInterval) {
							presenceCounter = 0;
							$extend(pollRequestOptions,{'presence': true });
						}
						pollRequest.get($merge(myRequestOptions,pollRequestOptions));  //go get data
					}
				};
				return {
					init : function (pollOptions) {
						presenceInterval = pollOptions.presence;
						pollInterval = pollOptions.poll;	
					},
					setLastId : function(lid) {
						if (!lastId) {
							lastId = lid;
							pollerId = poll.periodical(pollInterval,MBchat.updateables);
						} else {
							lastId = (lastId > lid)? lid : lastId;  //set to earliest value
						}
					},
					getLastId: function() {
						return lastId;
					},
					pollResponse : function(response) {
						response.messages.each(function(item) {
							var lid = item.lid.toInt();
							lastId = (lastId < lid)? lid : lastId; //This should throw away messages if lastId is null
							MBchat.updateables.processMessage(item);
						});
					},
					stop : function() {
						$clear(pollerId);
						lastId = null; //Ensure no more polls come through
					}
				};
			}(),
			online : function() {	//management of the online users list
				var onlineList ;		//Actual Display List
				var lastId;
				var loadingRid;
				var currentRid;
				var labelList = function() {
					var node = onlineList.firstChild;
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
						} while (node = node.nextSibling);
					}
				};
				var addUser = function (user) {
					var div =$('U'+user.uid);  // Does this already exist?
					if(div) div.destroy();  //So remove him, because we need to recreate from scratch
					div = new Element('div', {'id': 'U'+user.uid}); 
					var span = displayUser(user,div);

					if (user.private && user.private.toInt() != 0  ) { 
						//This user is in a private room so maybe we don't display him
						if (user.uid != me.uid) {
							//Not me, but I might be in a whisper with them

							var whisperBox = $('W'+user.private);
							if (!whisperBox) {
								return null; //not in any whisper box, so don't display 
							}
						}
						span.addClass('priv');  //makes them Italic to show its private
					} else {
						if (room.type === 'M') {
							if (me.mod === 'M') {
								if (user.uid != me.uid) {
									if (user.question) {
										span.addClass('ask');
										div.store('question',user.question);
										div.addClass('hasQuestion');
									}
									// I am a moderator in a moderated room - therefore I need to be able to moderate others
									div.addEvents({
										'click' : function(e) {
											if (e.control) { //Promote to moderator
												if (user.role != 'M') { //but only if not already one
													var request = new Request.JSON({
														'url' : 'promote.php',
														'onComplete' : function (response,errorMsg) {
															if(response) {
																MBchat.updateables.poller.pollResponse(response)
															} else {
																displayErrorMessage(errorMsg);
															}
														}
													}).get($merge(myRequestOptions,{
														'lid':MBchat.updateables.poller.getLastId(),
														'rid':room.rid,
														'puid':user.uid}));
												}
											} else {
												var qtext = div.retrieve('question');
												if (qtext) { // only send one if there is one
													var request = new Request.JSON({
														'url' : 'release.php',
														'onComplete' : function (response,errorMsg) {
															if(response) {
																MBchat.updateables.poller.pollResponse(response)
															} else {
																displayErrorMessage(errorMsg);
															}
														}
													}).get($merge(myRequestOptions,{
														'lid':MBchat.updateables.poller.getLastId(),
														'rid':room.rid,
														'quid':user.uid}));
												}
											}
										},
										'mouseenter' : function(e) {
											var span = div.getElement('span');
											if (!(span.hasClass('M') || span.hasClass('H') 
												|| span.hasClass('G') || span.hasClass('S'))) {
												var question = new Element('div', {'id' : 'Q'+user.uid});
												var qtext = div.retrieve('question');
												if (qtext) {
													qtext = replaceHyperLinks (qtext);  //replace Hperlinks
													qtext = replaceEmoticons(qtext); //Then replace emoticons.
													question.set('html',
														'<p><b>Click to Release Question<br/>',
														'Control Click to Promote</b></p>',
														'<p>',qtext,'</p>'); 
													question.setStyles({'top': e.client.y, 'left':e.client.x - 200});
												} else {
													question.set('html','<p><b>Control Click to Promote</b></p>');
													question.setStyles({'top': e.client.y, 'left':e.client.x });
												}
												question.inject(document.body);
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
									div.firstChild.addClass('whisperer'); //Adds cursor pointer
								} else {
									div.addEvent('click', function(e) {
										e.stop();
										if(e.control && e.alt) {
											var request = new Request.JSON({
												'url' : 'demote.php',
												'onComplete' : function (response,errorMsg) {
													if(response) {
														MBchat.updateables.poller.pollResponse(response)
													} else {
														displayErrorMessage(errorMsg);
													}
												}
											}).get($merge(myRequestOptions,{
												'lid':MBchat.updateables.poller.getLastId(),
												'rid':room.rid}));
										}
									});
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
											if (!(span.hasClass('M') || span.hasClass('H') 
												|| span.hasClass('G') || span.hasClass('S'))) {
												var question = new Element('div', 
													{'id' :  'Q'+div.get('id').substr(1)});
												var qtext = div.retrieve('question');
												if (qtext) {
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
					if (user.uid != me.uid) {
						span.addEvent('mousedown',function (e) {
							MBchat.updateables.whispers.whisperWith(user,span,e);
						});
						div.firstChild.addClass('whisperer');
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
				request = new Request.JSON({
					url: 'online.php',
					onComplete: function(response,errorMsg) {
						if (response) {
							onlineList.removeClass('loading');
							onlineList.addClass(room.type);
							currentRid = loadingRid;
							loadingRid = -1;
							var users = response.online;
							if (users.length > 0 ) {
								users.each(function(user) {
									addUser(user);
								});
							}
							lastId = response.lastid.toInt();
							MBchat.updateables.poller.setLastId(lastId);
						} else {
							displayErrorMessage(errorMsg);
						}
					}
				});
				return {
					init: function () {
						onlineList = $('onlineList');		//Actual Display List
						lastId = null;
						currentRid = -1;
					},
					show : function (rid) {
						if (request.running) {//cancel previous request if running
							request.cancel(); 
						}
						onlineList.empty();
						onlineList.erase('class');
						onlineList.addClass('loading');
						currentRid = -1;
						loadingRid = rid;
						request.get($merge(myRequestOptions,{'rid':rid }));
					},
					getCurrentRid: function() {
						return currentRid;
					},
					processMessage: function (msg) {
						if(!lastId) return;	//not processing messages yet
						var lid = msg.lid.toInt();
						if (lastId < lid) {
							lastId = lid;
							userDiv = $('U'+msg.user.uid);
							var whisperer;
							switch (msg.type) {
							case 'LO' : 
							case 'LT' :
								if (userDiv && msg.rid == currentRid) {
									removeUser(userDiv)
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
											if (!userDiv.has('priv')) {
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
									if (!userDiv) {
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
											if (!userDiv.has('priv')) {
												removeUser(userDiv);  //Only remove if not in the private room with this person
											}
										}
									} 
								}
								break;
							case 'MQ' : // User asks a question
								if (room.type == 'M' && (me.mod == 'M' || me.uid == msg.user.uid)) {
									var user = msg.user;
									user.question = msg.message;
									userDiv = addUser(user);
								}
								var span = userDiv.getElement('span');
								span.addClass('ask');
								break;
							case 'MR' :
							case 'ME' :
								//A message from a user must mean he no longer has a question outstanding
								var span = userDiv.getElement('span');
								span.removeClass('ask');
								if (room.type == 'M' && (me.mod == 'M' || me.uid == msg.user.uid)) {
									addUser(msg.user); //there will be no question
								}
								break;
							case 'RM' : // becomes moderator
							case 'RN' : // stops being moderator
								if (me.uid == msg.user.uid) {
									if (msg.user.role == 'M') {
										me.mod = 'M'
									} else {
										me.mod = 'N'
									}
								}
								// Given user is changing from mod to not or visa vera, need to remove and then re-add
								addUser(msg.user);
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
									user.private = msg.rid;
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
								//need to make a whisper box with my whisperers in it.
									$('W'+privateRoom).setStyle('display','block');
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
						$('inputContainer').set('styles',{ 'display':'block'});
						$('emoticonContainer').set('styles',{ 'display':'block'});
						$('entranceHall').set('styles',{'display':'none'});	
						var exit = $('exit');
						exit.addClass('exit-r');
						exit.removeClass('exit-f');
						var request = new Request.JSON({
							url: 'room.php',
							onComplete : function(response,errorMsg) {
								if (response) {
									room = response.room;
									response.messages.each(function(msg) {
										if(!lastId) lastId = msg.lid.toInt() -1;
										MBchat.updateables.processMessage(msg);
									});
									lastId = response.lastid.toInt();
								//Ensure we get all message from here on in
									MBchat.updateables.poller.setLastId(lastId);
								//Display room name at head of page
									var el = new Element('h1')
										.set('text', room.name )
										.inject('roomNameContainer');
									if (room.type == 'M' && 
										(me.mod == 'M' || me.role == 'H' || 
										me.role == 'G' || me.role == 'S' )) { //Can't go to private room here
										var whisperBoxes = $$('.whisperBox');
										whisperBoxes.each ( function (whisperBox) {
											var privateBox = whisperBox.getElement('.private');
											privateBox.removeClass('private');
											privateBox.addClass('nonprivate');
											privateBox.removeEvent('click',goPrivate);
										});
									}
										
									MBchat.updateables.online.show(room.rid);	//Show online list for room	
								} else {
									displayErrorMessage(errorMsg);
								}
								$('messageText').focus();							
							}
						}).get($merge(myRequestOptions,{'rid' : rid}));
						MBchat.sounds.resetTimer();
					},
					leaveRoom: function () {
						lastId = null;
						var request = new Request.JSON ({
							url :'exit.php',
							onComplete : function(response,errorMsg) {
								if (response) {
									response.messages.each(function(msg) {
										if(!lastId) lastId = msg.lid.toInt() -1;
										MBchat.updateables.processMessage(msg);
									});
									lastId = response.lastid.toInt();
								//Ensure we get all message from here on in
									MBchat.updateables.poller.setLastId(lastId);
									MBchat.updateables.online.show(0);	//Show online list for entrance hall
								} else {
									displayErrorMessage(errorMsg);
								}
							}
						}).get($merge(myRequestOptions,{'rid' : room.rid}));
						//we might have been in a room that stopped me going to private room
						if (room.type == 'M' && 
							(me.mod == 'M' || me.role == 'H' || 
							me.role == 'G' || me.role == 'S' )) { //Can't go to private room here	
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
						room = entranceHall;   //Set up to be in the entrance hall 
						messageList.removeClass('chat');
						messageList.empty();
						messageList.addClass('whisper');
						$('roomNameContainer').empty();
						var el = new Element('h1')
							.set('text', room.name)
							.inject('roomNameContainer');
						$('inputContainer').set('styles',{ 'display':'none'});
						$('emoticonContainer').set('styles',{ 'display':'none'});
						$('entranceHall').set('styles',{'display':'block'});
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
								var whisperList;
								var whisperIdStr;
								//Must only display whispers for me
								var whisperBoxes = $$('.whisperBox');
								if(!whisperBoxes.every(function(whisperBox) {
									whisperIdStr = whisperBox.get('id');
									if(msg.rid == whisperIdStr.substr(1).toInt()) {
										whisperList = whisperBox.getElement('.whisperList');
										return false;
									}
									return true;
								})) {
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
										var whisperers = whisperList.getChildren();
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
								if(msg.user.uid != me.uid) {
									this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Joins your whisper box'));
								}
								break;
							case 'WL' :
								if(msg.user.uid != me.uid) {
									this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Leaves your whisper box'));
								}
								break;
							default:
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
										case 'RM' :
											this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Has been made a Moderator'));
											break;
										case 'RN' :
											this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Is no longer a moderator'));
											break;
										default:
											break;
										}
									}
								}
								break;
							}
						}
					},
					displayMessage: function(lid,time,user,msgText) {
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
						while (messageList.getChildren().length >= messageListSize) {
							messageList.getFirst().destroy();
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
					//Now need to get a full picture of who might be in this wid
					var request = new Request.JSON({
						url:'getwhisperers.php',
						onComplete: function(response,errorMsg) {
							whisperList.removeClass('loading');
							if(response) {
								response.whisperers.each(function(whisperer) {
									//inject a user element into box
									if(me.uid != whisperer.uid) {
										addUser(whisperer,whisper);
									}
								});
							} else { 
								displayErrorMessage(errorMsg);
							}
						}
					});
					request.get($merge(myRequestOptions,{'wid':wid}));	
					//Now we have to make the whole thing draggable.
					var closeBox = whisper.getElement('.closeBox');
					closeBox.addEvent('click', function(e) {
						var leaveWhisper = new Request.JSON({
							url:'leavewhisper.php',
							onComplete: function(response,errorMsg) {
								if(response) {
									whisper.destroy();
									$('content').setStyles(contentSize);
								} else { 
									displayErrorMessage(errorMsg);
								}
							}
						});
						leaveWhisper.get($merge(myRequestOptions,{'wid': wid}));
					});
					var privateBox = whisper.getElement('.private');
					//can't go private if a key character in a modded room
					if (room.type == 'M' && (me.mod == 'M' || me.role == 'H' || me.role == 'G' || me.role =='S')) {
						privateBox.removeClass('private');
						privateBox.addClass('nonprivate');
					} else {
						privateBox.addEvent('click', goPrivate);
					}
					whisper.getElement('form').addEvent('submit', function(e) {
						e.stop();
						whisperReq.get($merge(myRequestOptions,{
							'wid':wid,
							'rid':room.rid,
							'lid':MBchat.updateables.poller.getLastId(),
							'text':whisper.getElement('.whisperInput').value}));
						whisper.getElement('.whisperInput').value = '';
						MBchat.sounds.resetTimer();
					});
					whisper.addClass('wBactive');
					whisper.inject(document.body);
					activeWb = whisper;
					var position = whisper.getCoordinates();
					position.top = position.top + (Math.random()-0.5) * 50;
					position.left = position.left + (Math.random()-0.5) * 150;
					whisper.setStyles(position);
					whisper.getElement('.whisperInput').focus();
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
											var getNewWhisperReq = new Request.JSON({
												url:'newwhisper.php',
												onComplete: function(response,errorMsg) {
													if(response) {
														var whisper = createWhisperBox(response.wid,response.user);
														dragReturn.start(whisper.getCoordinates()); //move towards it
													} else {
														displayErrorMessage(errorMsg);
													}
												}
											});
											getNewWhisperReq.get($merge(myRequestOptions,{'wuid':user.uid}));
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
											var addUserToWhisperReq = new Request.JSON({
												url:'joinwhisper.php',
												onComplete: function(response,errorMsg) {
													if(!response) {
														displayErrorMessage(errorMsg);
													}
												}
											});
											addUserToWhisperReq.get($merge(myRequestOptions,{
												'wuid':user.uid,
												'wid':droppable.get('id').substr(1).toInt()}));
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
										createWhisperBox(msg.rid);  //but without (yet) any other user
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
					}
				};
			}(),
			logger : function () {
				return {
					init: function() {
					},
					startLog: function (rid) {
						updateables.poller.stop();
//TODO
					}
				};
			}()
		};
	}()
  }; 


}();
