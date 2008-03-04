


MBchat = function () {
	var me;
	var myRequestOptions;
return {
	init : function(user,pollOptions) {
		
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
		me =  user;  Basic Info
		myRequestOptions = {'user': me.uid,'password': me.password};  //Used on every request to validate

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
		MBchat.updateables.init(pollOptions);
		MBchat.updateables.online.show(0);	//Show online list for entrance hall
		
	},
	whoAmI: function () {
		return me;
	},
	logout: function () {
		var logoutRequest = new Request ({url: 'logout.php'}).get(myRequestOptions);
	},
	updateables : function () {
		var processMessage = function(message) {
			(message.lid > online.getLastId()) && online.processMessage(message);
			(message.lid > message.getLastId()) && message.processMessage(message);
			(message.lid > whispers.getLastId()) && whispers.processMessage(message);
		};
		return {
			init : function (pollOptions) {
				online.init();
				message.init();
				poller.init(pollOptions);
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
					
					dragUser.display(div);
					div.inject(onlineList);
				};
				request = new Request.JSON({
					url: 'online.php',
					onComplete: function(response) {
						if (response) {
							onlineList.removeClass('loading');
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
						room = null;
					},
					show : function (rid) {
						if (currentRid !== rid) { 
							if (request.running) {//cancel previous request if running
								request.cancel(); 
							}
							onlineList.empty();
							onlineList.addClass('loading');
							request.get($merge(myRequestOptions,{'rid':rid }));
							currentRid=rid;
						};
					},
					getLastId: function () {
						return lastId;
					},
					processMessage: function (msg) {
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
				};

			}(),
			message : function () {
				var messageList; 
				var resizeML;
				var lastId;
				var room;
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
						room = null;
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
							url :'exit.php'
							onComplete : function(response) {
//Currently not doing anything - this function should send status OK but don't need to check it
							}
						}).get($merge(myRequestOptions,{'rid' : room.rid));
							
						resizeML.start('div.whisper');
						$('roomNameContainer').empty();
						var el = new Element('h1')
							.set('text', 'Entrance Hall')
							.inject('roomNameContainer');
						$('inputContainer').set('styles',{ 'display':'none'});
						$('emoticonContainer').set('styles',{ 'display':'none'});
						$('entranceHall').set('styles',{'display':'block'});
						var exit = $('exit');	
						exit.addClass('exit-f');
						exit.removeClass('exit-r');
						room=null;
					},
					getRoom: function () {
						return room;
					},
					processMessage: function (msg) {
//TODO
					}
				}
			}(),
			whispers : function () {
				lastId;
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
					init: function () {
						lastId = null;
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
						var channels = $$('.whisperBox');  //fake
						var commaNeeded = false;
						var wids = '';
						if (channels) {
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
//TODO
					}
				};
			}()	
		};
	}()
  }; 


}();

MBchat.User = new Class({
	extends : Element,
	initialize : function (user) {
		this.uid = user.uid;
		this.name = user.name;
		this.title = user.title || 'R' ;
		this.role = user.role || 'V' ;  // is 'V' for visitor (ie can't speak) 'P' = can speak
		this.parent('span',{
			'class' : this.title,
			'text' : this.name,
			'events' : {
				'mousedown' : function (e) {
					e = new Event(e).stop();  //stop it propagating
					if (this.uid !== MBchat.whoAmI().uid) { // only set up drag if its not you
						var user = this;
						var whispers = $$('div.whisperBox');
						var clone = this.clone()
							.setStyles(this.getCoordinates()) // this returns an object with left/top/bottom/right, so its perfect
							.setStyles({'opacity': 0.7, 'position': 'absolute'})
							.addClass('userDrag')
							.addEvent('emptydrop', function() {
	// This empty drop is strange, in that it really means we have dropped in a place where we need a new whisperbox
								droppables.removeEvents();
								var whisper = new Whisper({'uid':this.uid,'name':this.name,'title':this.title,'role':this.role},this.getCoordinates());
								whisper.inject(document.body);
								this.destroy();
							}).inject(document.body);
						var dragItem = new Drag.Move (clone,{
							container: $('#content'),
							droppables: whispers });
						dropZones.addEvents({
							'drop' : function() {
								droppables.removeEvents();
								MBChat.whisperAddUser(user,this.get('id'));
								clone.destroy();
							},
							'over' : function() {
	//TODO:  probably change color or opacity of this zone
							},
							'leave' : function() {
	//TODO: back to old setting
							}
						});
					}
				}
			}
		});
	}
});

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

