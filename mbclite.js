MBchat = function () {
	var version = 'v1.0';
	var me;
	var myRequestOptions;
	var Room = new Class({
		initialize: function(rid,name,type) {
			this.rid = rid || 0;
			this.name = name || 'Entrance Hall';
			this.type = type || 'O';
		},
		set: function(room) {
			this.rid = room.rid;
			this.name = room.name;
			this.type = room.type;
		} 
	});
	var room;
	var entranceHall;
	var setRoom
	var privateRoom;
	var chatBot;
	var messageListSize;
	var hyperlinkRegExp;
	var emoticonSubstitution;
	var emoticonRegExpStr;
	var logOptions;
	var ServerReq = new Class({
		initialize: function(url,process) {
			this.request = new Request.JSON({url:url,link:'cancel',onComplete: function(response,errorMessage) {
				if(response) {
					process(response);
				} else {
					displayErrorMessage(errorMessage);
				}
			}});
		},
		transmit: function (options) {
			this.request.get($merge(myRequestOptions,options));
		}		
	});
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
	var messageReq = new ServerReq('message.php',function (response) {MBchat.updateables.poller.pollResponse(response.messages)});
	var whisperReq = new ServerReq('whisper.php',function (response) {MBchat.updateables.poller.pollResponse(response.messages)});
	var privateReq = new ServerReq('private.php',function (response) {MBchat.updateables.poller.pollResponse(response.messages)});
	var goPrivate = function() {
		privateReq.transmit({
			'wid': this.getParent().get('id').substr(1).toInt(),
			'lid' : MBchat.updateables.poller.getLastId(),
			'rid' : room.rid});
	};
	var contentSize;
return {
	init : function(user,pollOptions,logOptionParameters, chatBotName, entranceHallName, msgLstSz) {
		var span = $('version');
		span.set('text', version);
		
// Save key data about me
		me =  user; 
		myRequestOptions = {'user': me.uid,'password': me.password};  //Used on every request to validate
		var loginReq = new ServerReq('login.php',function(response) {
			MBchat.updateables.init(pollOptions,response.lastid.toInt());
			MBchat.updateables.online.show(0);	//Show online list for entrance hall
		});
		loginReq.transmit($merge({'mbchat':version},MooTools,
			{'browser':Browser.Engine.name+Browser.Engine.version,'platform':Browser.Platform.name}));
		privateRoom = 0;
		chatBot = {uid:0, name : chatBotName, role: 'C'};  //Make chatBot like a user, so can be displayed where a user would be
		messageListSize = msgLstSz;  //Size of message list
		logOptions = logOptionParameters;
// We need to setup all the entrance hall
		entranceHall = new Room(0,entranceHallName,'O');
		room = new Room();
		room.set(entranceHall);
		var messageSubmit = function(event) {
			event.stop();
			if (privateRoom == 0 ) {
				messageReq.transmit({
					'rid':room.rid,
					'lid':MBchat.updateables.poller.getLastId(),
					'text':$('messageText').value});
			} else {
				whisperReq.transmit({
					'wid':privateRoom,
					'rid':room.rid,
					'lid':MBchat.updateables.poller.getLastId(),
					'text':$('messageText').value});
			}

			$('messageText').value = '';
		}
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
							MBchat.logout();
							window.location = '/forum' ;
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
		hyperlinkRegExp = new RegExp('(^|\\s|>)(((http)|(https)|(ftp)|(irc)):\\/\\/[^\\s<>]+)(?!<\\/a>)','gm');
		//Set up emoticons
		$('messageForm').addEvent('submit', function(e) {
			messageSubmit(e);
			return false;
		});
		contentSize = $('content').getCoordinates();
		window.addEvent('resize', function() {
			contentSize = $('content').getCoordinates();
		});
	},
	logout: function () {
		var logoutRequest = new Request ({url: 'logout.php',autoCancel:true}).get($merge(myRequestOptions,
				{'mbchat':version},MooTools,
				{'browser':Browser.Engine.name+Browser.Engine.version,'platform':Browser.Platform.name}));
	},
	updateables : function () {
		var replaceHyperLinks = function(text) {
			return text.replace(hyperlinkRegExp,function(str, p1, p2) {
				return p1 + '<a href="' + p2 
					+ '" onclick="window.open(this.href); return false;">' + p2 + '</a>';
			});
		};
		return {
			init : function (pollOptions,lastid) {
				MBchat.updateables.online.init();
				MBchat.updateables.message.init();
				MBchat.updateables.poller.init(pollOptions);
				MBchat.updateables.whispers.init(lastid);
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
				var fullPoll=true;
				var wid;

				var pollRequest = new ServerReq('poll.php',function(response) {
					MBchat.updateables.poller.pollResponse(response.messages)
				});
				var presenceReq = new ServerReq('presence.php', function(r) {});
				var poll = function () {
					var pollRequestOptions = {'lid':lastId};
					presenceCounter++;
					if (presenceCounter > presenceInterval) {
						presenceCounter = 0;
						if (fullPoll) {
							$extend(pollRequestOptions,{'presence':true});
						} else {
							presenceReq.transmit({});  //say here (also timeout others)
						}
					}
					if (fullPoll) {
						pollRequest.transmit(pollRequestOptions);  //go get data
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

					start : function () {
						fullPoll=true;
					},

					pollResponse : function(messages) {
						messages.each(function(item) {
							item.lid = item.lid.toInt();
							item.rid = item.rid.toInt();
							item.user.uid = item.user.uid.toInt();
							var lid = item.lid;
							lastId = (lastId < lid)? lid : lastId; //This should throw away messages if lastId is null
							if ( fullPoll) MBchat.updateables.processMessage(item);
						});
					},
					stop : function() {
						fullPoll=false;
					}
				};
			}(),
			online : function() {	//management of the online users list
				var onlineList ;		//Actual Display List
				var lastId;
				var loadingRid;
				var currentRid = -1;
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
							if (user.question) {
								span.addClass('ask');
							}
							if (me.uid == user.uid) {
								if (user.question) {
									div.store('question',user.question);
									div.addClass('hasQuestion');
								}
							} 
						} 
					}
					if (user.uid != me.uid) {
						div.firstChild.addClass('whisperer');
						div.addEvent('keydown', function(e) {
							if(!e.control) return;
							if(e.key == 'w') {
								e.stop();
								var whisperBoxes = $$('.whisperBox');
								if (whisperBoxes.every(function(whisperBox,i) {
									var widStr = whisperBox.get('id');
									var whisperers = whisperBox.getElement('.whisperList').getChildren();   //gets users in whisper
									if (whisperers.length == 1) { //we only want to worry about this if only other person
										if (whisperers[0].get('id').substr(widStr.length+1).toInt() == user.uid) {
											return false;
										}
									}
									return true;
								})){
								//If we get here we have not found that we already in a one on one whisper with this person, so now we have to create a new Whisper
									var getNewWhisperReq = new ServerReq('newwhisper.php',function(response) {
										if(response.wid != 0) {
											var user = response.user
											user.uid = user.uid.toInt();
											var whisper = createWhisperBox(response.wid.toInt(),user);
										}
									});
									getNewWhisperReq.transmit({'wuid':user.uid});
								}
							}
						});
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
				onlineReq = new ServerReq('online.php',function(response) {
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
					lastId = response.lastid.toInt();
					MBchat.updateables.poller.setLastId(lastId);
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
								if (userDiv) {
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
									if (room.type == 'M' && (me.mod == 'M' || me.uid == msg.user.uid)) {
										var user = msg.user;
										user.question = msg.message;
										userDiv = addUser(user);
									}
									var span = userDiv.getElement('span');
									span.addClass('ask');
								}
								break;
							case 'MR' :
							case 'ME' :
								if(msg.rid == currentRid) {
									//A message from a user must mean he no longer has a question outstanding
									var span = userDiv.getElement('span');
									span.removeClass('ask');
									if (room.type == 'M' && (me.mod == 'M' || me.uid == msg.user.uid)) {
										addUser(msg.user); //there will be no question
									}
								}
								break;
							case 'RM' : // becomes moderator
							case 'RN' : // stops being moderator
								if(msg.rid == currentRid) {
									if (me.uid == msg.user.uid) {
										if (msg.user.role == 'M') {
											me.mod = 'M'
										} else {
											me.mod = 'N'
										}
									}
									// Given user is changing from mod to not or visa vera, need to remove and then re-add
									addUser(msg.user);
								}
								break;
							case 'PE' :
								var whisperBox = $('W' + msg.rid);
								if(userDiv) { //only relevent if we have this user
									if (msg.user.uid != me.uid) {
										//Not me, but I might be in a whisper with them
										if (!whisperBox) {
											MBchat.updateables.message.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Leaves the Room'));
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
											$('entranceHall').set('styles',{'display':'none'});	
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
										whisperBox.setStyle('display','none');
										$('content').setStyles(contentSize);
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
										$('entranceHall').set('styles',{'display':'block'});
									}
								//need to make a whisper box with my whisperers in it.
									$('W'+privateRoom).setStyle('display','block');
									$('content').setStyles(contentSize);
									privateRoom = 0;
								} else {
									MBchat.updateables.message.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Enters the Room'));
									addUser(msg.user);
								}
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
						$('entranceHall').set('styles',{'display':'none'});	
						room = new Room(rid,'Loading','I'); //set upi room first so random calls afterwards don't screw me
						var request = new ServerReq('room.php',function(response) {
							response.room.rid = response.room.rid.toInt();
							room.set(response.room);
							response.messages.each(function(item) {
								item.lid = item.lid.toInt();
								item.rid = item.rid.toInt();
								item.user.uid = item.user.uid.toInt();
								if(!lastId) lastId = item.lid - 1;
								MBchat.updateables.processMessage(item);
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
							$('messageText').focus();							
						}).transmit({'rid' : rid});
					},
					leaveRoom: function () {
						lastId = null;
						var request = new ServerReq ('exit.php',function(response) {
							response.messages.each(function(item) {
								item.lid = item.lid.toInt();
								item.rid = item.rid.toInt();
								item.user.uid = item.user.uid.toInt();
								if(!lastId) lastId = item.lid -1;
								MBchat.updateables.processMessage(item);
							});
							lastId = response.lastid.toInt();
						//Ensure we get all message from here on in
							MBchat.updateables.poller.setLastId(lastId);
							MBchat.updateables.online.show(0);	//Show online list for entrance hall
						}).transmit({'rid' : room.rid});
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
						room.set (entranceHall);;   //Set up to be in the entrance hall 
						messageList.removeClass('chat');
						messageList.empty();
						messageList.addClass('whisper');
						$('roomNameContainer').empty();
						var el = new Element('h1')
							.set('text', room.name)
							.inject('roomNameContainer');
						$('inputContainer').set('styles',{ 'display':'none'});
						$('entranceHall').set('styles',{'display':'block'});
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
								if(privateRoom == 0) {
									if (msg.rid == room.rid) {
										switch(msg.type) {
										case 'ME' :
											this.displayMessage(lastId,msg.time,msg.user,msg.message);
											break;
										case 'LT' :
											this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Logs Out (timeout)'));
											break;
										case 'LI' :
											this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Logs In to Chat'));
											break;
										case 'LO' :
											this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Logs Out'));
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
						mlScroller.toBottom();
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
					var getWhisperersReq = new ServerReq('getwhisperers.php',function(response) {
						whisperList.removeClass('loading');
						response.whisperers.each(function(whisperer) {
							whisperer.uid = whisperer.uid.toInt();
							if(me.uid != whisperer.uid) addUser(whisperer,whisper);
						});
					});
					getWhisperersReq.transmit({'wid':wid});
					//Now we have to make the whole thing draggable.
					var closeBox = whisper.getElement('.closeBox');
					var leaveWhisper = new ServerReq('leavewhisper.php',function(response) {
						whisper.destroy();
						$('content').setStyles(contentSize);
					});
					closeBox.addEvent('click', function(e) {
						leaveWhisper.transmit({'wid': wid});
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
						whisperSubmit(whisper,wid);
					});
					whisper.addEvent('keydown',function(e) {
						if(!e.alt) return;
						switch (e.key) {
						case 'p' :
							if (room.type != 'M' || (me.mod != 'M' && me.role != 'H' && me.role != 'G' && me.role !='S')) {
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
			}()
		};
	}(),
	goToRoom: function(roomId) {
		MBchat.updateables.message.enterRoom(roomId);
	},
	exit: function() {
		if (privateRoom != 0) {
			privateReq.transmit({
				'wid':  0, 
				'lid' : MBchat.updateables.poller.getLastId(),
				'rid' : room.rid }); 
		} else {
			if (room.rid == 0 ) {
				MBchat.logout();
				//and go back to the forum
				window.location = '/forum' ;
			} else {
				MBchat.updateables.message.leaveRoom();
			}
		}
	}
  }; 
}();
