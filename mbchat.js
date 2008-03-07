MBchat = function () {
	var me;
	var myRequestOptions;
	var entranceHall;  //Entrance Hall Object
	var room;
	var chatBot;
	var displayUser = function(user,container) {
		var el = new Element('span',{'class' : this.role, 'text' : this.name });
		el.inject(container);
	};
	var displayErrorMessage = function(text) {
		var msg = {};
		var d = new Date();
		msg.time = d.UTC()
		msg.user = chatBot;
		msg.text = text;
		MBchat.message.displayMessage(msg);
	};
return {
	init : function(user,pollOptions,chatBotName, entranceHallName) {
		
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
			var fx = new Fx.Elements(rooms, {wait: false, duration: 500, transition: roomTransition.easeOut });
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
			});
		});

		var exit = $('exit');
		var exitfx = new Fx.Morph(exit, { duration: 500, transition: roomTransition.easeOut});
		exit.addEvent('mouseenter',function(e) {
			exitfx.start({width:100});
		});
		exit.addEvent('mouseleave', function(e) {
			exitfx.start({width:50});
		});
		exit.addEvent('click', function(e) {
			e.stop();
			if (MBchat.updateables.message.getRoom() === 0 ) {
				MBchat.logout();
				window.location = '/forum' ; //and go back to the forum
			}
			MBchat.updateables.message.leaveRoom();
		});
		var emoticons = $$('img.emoticon');
		emoticons.each(function(icon,i) {
			icon.addEvent('click', function(e) {
				e.stop();
				var msgText = $('messageText').value;
				if (msgText.lastIndexOf(' ') != (msgText.length-1)) {
					//last character of text is not a space so add one
					msgText += ' ';
				}
				msgText += icon.get('alt') + ' ';
				$('messageText').value = msgText;
			};
		});
		room = {rid:0, name: 'Entrance Hall, type = 'O'};   //Set up to be in the entrance hall
		MBchat.updateables.init(pollOptions);
		MBchat.updateables.online.show(0);	//Show online list for entrance hall
		
	},
	logout: function () {
		var logoutRequest = new Request ({url: 'logout.php'}).get(myRequestOptions);
	},
	updateables : function () {
		var processMessage = function(message) {
			online.processMessage(message);
			message.processMessage(message);
			whispers.processMessage(message);
		};
		return {
			init : function (pollOptions) {
				MBchat.updateables.online.init();
				MBchat.updateables.message.init();
				MBchat.updateables.poller.init(pollOptions);
				MBchat.updateables.whispers.init(pollOptions.lastid);
			},
			poller : function() {
				var lastId = null;
				var presenceInterval;
				var presenceCounter = 0;
				var pollInterval;
				var pollerId;
				var lastId;

				var pollRequest = new Request.JSON({
					url: 'poll.php',
					autoCancel: true,
					onComplete : function(response) {
						if(response) {
							response.messages.each(function(item) {
								lastId = (lastId < item.lid)? item.lid : lastId;
								updateables.processMessage(item);
							});
						}
					}
				});
				var poll = function () {
					if (this.online.getLastId() || this.message.getLastId() || this.whispers.getLastId()) {
						var pollRequestOptions = {'lid':lastId };
						presenceCounter++;
						if (presenceCounter > presenceInterval) {
							presenceCounter = 0;
							$extend(pollRequestOptions,{'presence': true });
						}
						if (this.message.getLastId()) {
							$extend(pollRequestOptions, {'rid' : this.message.getRoom().rid});
						}
						if (this.whispers.getLastid()) {
							$extend(pollRequestOptions, {'wids' : this.whispers.getWhisperIds() });
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
							pollerId = poll.periodical(pollInterval,updateables);
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
				var addUser = function (user) {
					var div = new Element('div', {'id': 'U'+user.uid});
					displayUser(user,div)
					if (room.type === 'M' && me.role === 'M') {
						var question = new Element('div' {
							'class': 'question hide',
							'text' : user.question}).inject(div);
								
					// I am a moderator in a moderated room - therefore I need to be able to moderate
						div.addEvents({
							'click' : function(e) {
								var request = new request.JSON({
									'url' : 'release.php',
									'onComplete' : function (response) {
										//Not interested in normal return as message will appear via poll
										if(response.error) {
											displayError(response.error);
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
							'controlclick':function(e) {
								e.stop();
								MBchat.whispers.startnewWhisper(user);
							},
							'mouseover' : function(e) {
								question.removeClass('hide');
							),
							'mouseleave' : function(e) {
								question.addClass('hide');
							}
						});

					} else {
						div.addEvent('click',function (e) {
							e.stop();
							MBchat.whispers.startNewWhisper(user);
						});
					}
					} 
					div.inject(onlineList);
				};
				request = new Request.JSON({
					url: 'online.php',
					onComplete: function(response) {
						if (response) {
							onlineList.removeClass('loading');
							onlineList.addClass(room.type);
							var users = response.online;
							if (users.length > 0 ) {
								users.each(function(user) {
									addUser(user);
								});
							}
							lastId = response.lastid;
							poller.setLastId(lastId);
						}
					}
				});
				return {
					init: function () {
						onlineList = $('onlineList');		//Actual Display List
						lastId = null;
					},
					show : function (rid) {
						if (request.running) {//cancel previous request if running
							request.cancel(); 
						}
						onlineList.empty();
						onlineList.erase('class');
						onlineList.addClass('loading');
						request.get($merge(myRequestOptions,{'rid':rid }));
					},
					getLastId: function () {
						return lastId;
					},
					processMessage: function (msg) {
						if (lastId < msg.lid) {
							lastId = msg.lid;
							userDiv = $('U'+user.id);
							switch (msgtype) {
							case 'LO' :
							case 'LT' :
							case 'RX' :
								if (userDiv) {
									userDiv.destroy(); //removes from list
								}
								break;
							case 'LI' :
							case 'RE' :
								if (!userDiv && rid === currentRid) {
									addUser(user);
								}
								break;
							case 'RV' :
	//TODO
								break;
							case 'RP' :
	//TODO
								break;
							default :
								break;
							}
						}
					}
				};

			}(),
			message : function () {
				var messageList; 
				var resizeML;
				var lastId;
				return {
					init: function () {
						messageList = $('chatList');
						resizeML = new Fx.Morph(messageList, {
							duration: 1000,
							transition: Fx.Transitions.Quad.easeOut,
							onComplete : function (e) {
								messageList.removeClass('whisper')  //This will only be here the first time, using transitions later
							}
						});
						lastId = null;
					},
					enterRoom: function(rid) {
						resizeML.start('div.chat');
						$('roomNameContainer').empty();
						$('inputContainer').set('styles',{ 'display':'block'});
						$('emoticonContainer').set('styles',{ 'display':'block'});
						$('entranceHall').set('styles',{'display':'none'});	
						var exit = $('exit');
						exit.addClass('exit-r');
						exit.removeClass('exit-f');
						var request = new Request.JSON({
							url: 'room.php',
							onComplete : function(response) {
								if (response) {
									room = response.room;
									response.messages.each(function(msg) {
										lastId = (lastId > msg.lid)? lastId : msg.lid;
										processMessage(msg);
									});
								//Ensure we get all message from here on in
									poller.setLastId(lastId);
								//Display room name at head of page
									var el = new Element('h1')
										.set('text', room.name )
										.inject('roomNameContainer');
								}							
							}
						}).get($merge(myRequestOptions,{'rid' : rid}));
					},
					leaveRoom: function () {
						lastId = null;
						var request = new Request.JSON ({
							url :'exit.php',
							onComplete : function(response) {
//Currently not doing anything - this function should send status OK but don't need to check it
							}
						}).get($merge(myRequestOptions,{'rid' : room.rid}));
						room = entranceHall;   //Set up to be in the entrance hall						var request = new Request.JSON ({
						
						resizeML.start('div.whisper');
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
						if (lastId < msg.lid) {
							lastId = msg.lid;
//TODO
						}
					}
				}
			}(),
			whispers : function () {
				var lastId = null;
				var channels = null;
				var request = new Request.JSON({
					url:'whisper.php',
					onComplete : function (response) {
						if (response) {
							lastId = response.lastid;
							poller.setLastId(lastId);
//TODO
						}
					}
				});	
				return {
					init: function (lid) {
						lastId = lid;
					},
					startNewWhisper : function (user) {
						request.get($merge(myRequestOptions,{'whisperer':user.uid}));
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
					getWhisperIds: function () {
						var wids = '';
						if (channels) {
							var commaNeeded = false;
							channels.each(function (whisper) {
								if (commaNeeded) {
									wids += ',';
								}
								commaNeeded = true;
								wids += whisper.get('id').substr(1);
							});
						}
						return wids;
					},	
					processMessage: function (msg) {
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

