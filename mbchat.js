MBchat = function () {
	var me;
	var myRequestOptions;
	var entranceHall;  //Entrance Hall Object
	var room;
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
		var msg = '<span class="errorMessage">'+txt+'</spam>';
		var d = new Date();
		MBchat.message.displayMessage(0,d.UTC()/1000,chatBot,msg);  //need to convert from millisecs to secs
	};
return {
	init : function(user,pollOptions,chatBotName, entranceHallName, msgLstSz) {
		
/*		soundManager.onload = function() {
			soundManager.createSound({
				id : 'entrance',
				url : '/static/sounds/mfv.mp3',
				autoLoad : true ,
				autoPlay : false ,
				onfinish : function () {
					soundManager.play('entrance');
				},
				volume : 10
			});
			soundManager.play('entrance');
		};
*/
// Save key data about me
		me =  user; 
		myRequestOptions = {'user': me.uid,'password': me.password};  //Used on every request to validate
		entranceHall = {rid:0, name: entranceHallName, type: 'O'};
		chatBot = {uid:0, name : chatBotName, role: 'C'};  //Make chatBot like a user, so can be displayed where a user would be
		messageListSize = msgLstSz;  //Size of message list
		Element.Events.shiftclick = {
			base: 'click', //we set a base type
			condition: function(event){ //and a function to perform additional checks.
				return (event.shift == true); //this means the event is free to fire
			}
		}
		Element.Events.controlclick = {
			base: 'click', //we set a base type
			condition: function(event){ //and a function to perform additional checks.
				return (event.control == true); //this means the event is free to fire
			}
		}
		Element.Events.altclick = {
			base: 'click', //we set a base type
			condition: function(event){ //and a function to perform additional checks.
				return (event.alt == true); //this means the event is free to fire
			}
		}
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
				if (me.role == 'A' || me.role == 'L' or room.hasClass('committee') ) {
					room.addEvent('controlclick', function(e) {
						e.stop();
						MBchat.updateables.logger.startLog(room.get('id').substr(1).toInt());
					});
				});
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
			if (MBchat.updateables.message.getRoom().rid == 0 ) {
				window.location = '/forum' ; //and go back to the forum
			} else {
				MBchat.updateables.message.leaveRoom();
			}
		});
		if (me.role == 'A') {
			exit.addEvent('altclick',function(e) {
				e.stop();
				MBchat.updateables.logger.startLog(MBchat.updateables.message.getRoom().rid,true);
			});
		}
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
			$('messageRoom').value = room.rid;
			this.send();
			$('messageText').value = '';
		});
		room = {rid:0, name: 'Entrance Hall', type : 'O'};   //Set up to be in the entrance hall
		MBchat.updateables.init(pollOptions);
		MBchat.updateables.online.show(0);	//Show online list for entrance hall
		
	},
	logout: function () {
		var logoutRequest = new Request ({url: 'logout.php'}).get(myRequestOptions);
	},
	updateables : function () {
		return {
			init : function (pollOptions) {
				MBchat.updateables.online.init();
				MBchat.updateables.message.init();
				MBchat.updateables.poller.init(pollOptions);
				MBchat.updateables.whispers.init(pollOptions.lastid);
				MBchat.updateables.logger.init();
			},
			processMessage : function(message) {
				MBchat.updateables.online.processMessage(message);
				MBchat.updateables.message.processMessage(message);
				MBchat.updateables.whispers.processMessage(message);
			},
			poller : function() {
				var lastId = null;
				var presenceInterval;
				var presenceCounter = 0;
				var pollInterval;
				var pollerId;

				var pollRequest = new Request.JSON({
					url: 'poll.php',
					autoCancel: true,
					onComplete : function(response,errorMsg) {
						if(response) {
							response.messages.each(function(item) {
								lastId = (lastId < item.lid)? item.lid : lastId;
								MBchat.updateables.processMessage(item);
							});
						} else {
							MBchat.displayErrorMessage(errorMsg);
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
					stop : function() {
						$clear(pollerId);
						lastId = null;
					}
				};
			}(),
			online : function() {	//management of the online users list
				var onlineList ;		//Actual Display List
				var lastId;
				var loadingRid;
				var currentRid;
				var addUser = function (user) {
					var div = new Element('div', {'id': 'U'+user.uid});
					displayUser(user,div)
					if (room.type === 'M' && me.role === 'M') {
						var question = new Element('div' , {
							'class': 'question hide',
							'text' : user.question}).inject(div);
								
					// I am a moderator in a moderated room - therefore I need to be able to moderate
						div.addEvents({
							'click' : function(e) {
								var request = new request.JSON({
									'url' : 'release.php',
									'onComplete' : function (response,errorMsg) {
										//Not interested in normal return as message will appear via poll
										if(!response) {
											displayError(errorMsg);
										}
									}
								}).get($merge(myRequestOptions,{'rid':room.rid,'quid':user.uid, 'ques':user.question}));
							},
							'altclick': function(e) {
//TODO - make moderator
							},
							'shiftclick':function(e) {
//TODO - downgrade self
							},
							'mouseover' : function(e) {
								question.removeClass('hide');
							},
							'mouseleave' : function(e) {
								question.addClass('hide');
							}
						});
						if (user.uid != me.uid) {
							div.addEvent('controlclick', function(e) {
								e.stop();
								MBchat.updateables.whispers.whisperWith(user);
							});
							div.firstChild.addClass('whisperer');
						}
					} else {
						if (user.uid != me.uid) {
							div.addEvent('click',function (e) {
								e.stop();
								MBchat.updateables.whispers.whisperWith(user);
							});
							div.firstChild.addClass('whisperer');
						}
					} 
					div.inject(onlineList); //Forces onlineList to have children
					if ((onlineList.getChildren().length % 2) == 0 ) {
						div.addClass('rowEven');
					} else {
						div.addClass('rowOdd');
					}
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
							lastId = response.lastid;
							MBchat.updateables.poller.setLastId(lastId);
						} else {
							MBchat.displayErrorMessage(errorMsg);
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
//					getLastId: function () {
//						return lastId;
//					},
					getCurrentRid: function() {
						return currentRid;
					},
					processMessage: function (msg) {
						if(!lastId) return;	//not processing messages yet
						if (lastId < msg.lid) {
							lastId = msg.lid;
							if (msg.rid == currentRid) {
								userDiv = $('U'+msg.user.uid);
								switch (msg.type) {
								case 'LO' : //Logout, timeout or room exist are all the same
								case 'LT' :
								case 'RX' :
									if (userDiv) {
										userDiv.destroy(); //removes from list
										var node = onlineList.firstChild;
										if (node) {
											var i = 0;
											do {	
												node.erase('class');
												if( i%2 == 0) {
													node.addClass('rowEven');
												} else {
													node.addClass('rowOdd');
												}
												i++;
											} while (node = node.nextSibling);
										}
									}			 
									break;
								case 'LI' : //Login (to room) and room entry are the asme
								case 'RE' :
									if (!userDiv) {
										addUser(msg.user);
									}
									break;
								case 'RM' : // becomes moderator
//TODO
									break;
								case 'RN' : // stops being moderator
//TODO
									break;
								case 'MQ' : // User asks a question
//TODO
									break;
								case 'MR' : //User removes question
//TODO
									break;
								default :  // ignore anything else
									break;
								}
							}
						}
					}
				};

			}(),
			message : function () {
				var messageList; 
				var mlScroller;
				var lastId;
				var insertEmoticons = function (msg) {
//TODO
					return msg;
				};
				var chatBotMessage = function (msg) {
					return '<span class="chatBotMessage">'+msg+'</spam>';
				};
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
										if(!lastId) lastId = msg.lid -1;
										MBchat.updateables.processMessage(msg);
									});
									lastId = response.lastid;
								//Ensure we get all message from here on in
									MBchat.updateables.poller.setLastId(lastId);
								//Display room name at head of page
									var el = new Element('h1')
										.set('text', room.name )
										.inject('roomNameContainer');
									MBchat.updateables.online.show(room.rid);	//Show online list for room	
								} else {
									displayErrorMessage(errorMsg);
								}
								$('messageText').focus();							
							}
						}).get($merge(myRequestOptions,{'rid' : rid}));
					},
					leaveRoom: function () {
						lastId = null;
						var request = new Request.JSON ({
							url :'exit.php',
							onComplete : function(response,errorMsg) {
								if (response) {
									response.messages.each(function(msg) {
										if(!lastId) lastId = msg.lid -1;
										MBchat.updateables.processMessage(msg);
									});
									lastId = response.lastid;
								//Ensure we get all message from here on in
									MBchat.updateables.poller.setLastId(lastId);
									MBchat.updateables.online.show(0);	//Show online list for entrance hall
								} else {
									MBchat.displayErrorMessage(errorMsg);
								}
							}
						}).get($merge(myRequestOptions,{'rid' : room.rid}));
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
					},
					getRoom: function () {
						return room;
					},
					processMessage: function (msg) {
						if (!lastId) return;	// not processing messages until set

						if (lastId < msg.lid) {
							lastId = msg.lid;
							switch(msg.type) {
							case 'ME' :

								this.displayMessage(lastId,msg.time,msg.user,msg.message);
								break;
							case 'WH' :
								var whisper ='<span class="whisper" onclick="javascript:MBchat.whispers.join('
									+ msg.rid.toString() + ');>(whispers)</span>' +msg.message ;
								this.displayMessage(lastId,msg.time,msg.user,whisper);
								break;
							case 'RE' :
								this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Enters the Room'));
								break;
							case 'RX' :
								this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Leaves the Room'));
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
							case 'WJ' :
								this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Joins your whisper room'));
								break;
							case 'WL' :
								this.displayMessage(lastId,msg.time,chatBot,chatBotMessage(msg.user.name+' Leaves your whisper room'));
								break;
							default:
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
				var request = new Request.JSON({
					url:'whisper.php',
					onComplete : function (response,errorMsg) {
						if (response) {
							lastId = response.lastid;
							MBchat.updateables.poller.setLastId(lastId);
//TODO
						} else {
							MBchat.displayErrorMessage(errorMsg);
						}
					}
				});
				var getNewWhisperReq = new Request.JSON({
					url:'newwhisper.php'
					onComplete: function(response,errorMsg) {
						if(response) {
							var whisper = $('whisperBoxTemplate').clone();
							whisper.set('id','W'+response.wid);
							var whisperList = whisper.firstchild;
							//inject a user element into box
							var user displayUser(response.user,whisperList);
							user.setClass('whisperer');
							user.set('id', 'W'+wid+'U'+response.user.uid);
							//Now we have to make the whole thing draggable.
							whisper.setStyle('display','block');
							whisper.draggable();
							whisper.inject(document.body);
							whisper.getChildren('.whisperInput').focus();
						} else {
							MBchat.displayErrorMessage(errorMsg);
						}
					}
				});
	
				return {
					init: function (lid) {
						lastId = lid;
					},
					whisperWith : function (user) {
//See if we are already in a whisper with this user
						var thisWhisper = false;
						var whispersBoxes = $$('.whisperBox);
						whisperBoxes.each(function(whisperBox,i) {
							if (thisWhisper) return;
							var widStr = whisperBox.get('id');
							var whisperers = whisperBox.firstChild.getChildren('.whispering');   //gets users in whisper
							if (whisperers.length == 1) { //we only want to worry about this if only other person
								whisperers.each(function(whisperer,i) {
									if (thisWhisper) return;
									if (whisperer.get('id').substr(widStr.length+1).toInt() == user.uid) (
										thisWhisper = whisperBox;
										thisWhisper.getChildren('.whisperInput').focus();
									}
								});
							}		 
						});
						if (thisWhisper) return;
//If we get here we have not found that we already in a one on one whisper with this person, so now we have to create a new Whisper					
						getNewWhisperReq.get($merge(myRequestOptions,{'whisperer':user.uid}));
					},
					join: function(wid) {
//TODO
					},
					addUser : function (user,wid) {
//TODO
					},
					leaveWhisper : function (wid) {
//TODO
					},
					getLastId : function () {
						return lastId;
					},
					processMessage: function (msg) {
						if(!lastId) return;
						if (lastId < msg.lid) {
							lastId = msg.lid;
							switch(msg.type) {
							case 'WE':
//TODO
								break;
							case 'WJ' :
//TODO
								break;
							case 'WX' :
//TODO
								break;
							case 'WH' :
//TODO
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
					startLog: function (rid,ww) {
//TODO
					}
				};
			}()
		};
	}()
  }; 


}();

MBchat.Whisper = new Class({
	initialize: function (user) {
//TODO
	},
	addUser: function (user) {
//TODO
	},
	removeUser: function (user) {
//TODO
	},
	display: function() {
//TODO
	}
});

