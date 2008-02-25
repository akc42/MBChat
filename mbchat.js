var MBChat = new Class({

	me : null,			// the user who is connected at this browser

	whisperChannels: null,		// Currently Open Whisper Channels.
	

	initialize : function(user) {
		
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
// Create a user class
		var me = new MBChat.User ( user);
		this.me = me;
// Create an online list class
		var requests = new Array();
		var roomgroups = $$('.rooms');
		var roomTransition = new Fx.Transition(Fx.Transitions.Bounce, 6);
		var onlineList = $('onlineList');
		roomgroups.each( function (roomgroup,i) {
			var rooms = roomgroup.getElements('.room');
			var fx = new Fx.Elements(rooms, {wait: false, duration: 500, transition: roomTransition.easeOut });
			rooms.each( function(room, i){
				var request;
				room.addEvent('mouseenter', function(e){
					var obj = {};
					obj[i] = {
						'width': [room.getStyle('width').toInt(), 219],
					};
					rooms.each(function(otherRoom, j){
						if (otherRoom != room){
							var w = otherRoom.getStyle('width').toInt();
							if (w != 67) obj[j] = {'width': [w, 67]};
						}
					});
					fx.start(obj);
					onlineList.empty();			//get rid of previous online list
					onlineList.addClass('loading');
					request = new Request.JSON({
						url: 'online.php',
						oncomplete: function(response) {
							onlineList.removeClass('loading')
							//TODO build online list from response		
						}

					}).get({
						'user': me.id,
						'password': me.password,
						'rid': room.get('id').substr(1) });
					
//TODO: Get Online list displayed in list
				});
				room.addEvent('mouseleave', function(e){
					room.setStyle('overflow' , 'hidden');
					var obj = {};
					rooms.each(function(other, j){
						obj[j] = {'width': [other.getStyle('width').toInt(), 105]};
					});
					fx.start(obj);
					request.cancel();
					onlineList.removeClass('loading')
				});
			});
		});	
	},
	whisper: function(user) {
		if (this.whisperChannels) {
			var found = false;
			this.whisperChannels.each(function (channel) {
				if (!found) {
					if (channel.has(user.id)) {
						found = channel;
					}
				}
			});
			if (found) {
				found.setFocus();
				return;
			}
		} else {
			this.whisperChannels = new Hash({});
		};
		// We don't have a whisper channel with the uid, so we need to create one
		var channel = new MBChat.Channel(user)
		this.whisperChannels.extend(channel);
		channel.setfocus();
	},
	whisperAddUser: function (user, cid) {
//TODO
	}
});

MBChat.User = new Class({
	initialize : function (user) {
		this.id = user.id;
		this.name = user.name;
		this.role = user.role || 'R' ;
		this.participant = user.participant || 'P' ;  // is 'V' for visitor (ie can't speak) 'P' = can speak
		this.password = user.password || null;
	},
	display: function(container) {
		var user = this;
		var el = new Element('span',{
			'class' : this.role,
			'html' : this.name ,
			'events' : {
				'mousedown' : function (e) {
					e = new Event(e).stop();  //stop it propagating
					var dropZones = $$('div.whisperBox');
					var dropProhibit = $('onlineListContainer')
					var droppables = dropZones.extend([dropProhibit]);
					var clone = this.clone()
                        			.setStyles(this.getCoordinates()) // this returns an object with left/top/bottom/right, so its perfect
                        			.setStyles({'opacity': 0.7, 'position': 'absolute'})
						.addClass('userDrag')
                        			.addEvent('emptydrop', function() {
// This empty drop is strange, in that it really means we have dropped in a place where we need a new whisperbox
							droppables.removeEvents();
							MBChat.whisper(user);
							this.destroy();
                        			}).inject(document.body);
					var dragItem = new Drag.Move (clone,{
						container: $('#content'),
						droppables: droppables });
					dropProhibit.addEvents({
						'drop' : function() { 
							droppables.removeEvents();
							clone.destroy();
						}
					});
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
		})
		.setStyle('color', (this.participant == 'R' ? el.getStyle('color') : 
						new Color('#777').mix(el.getStyle('color'),50)))	
		.inject(container);
			
	}
});

MBChat.Channel = new Class({
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

MBChat.Online = new Class({
	users: null,
	initialize: function () {
		this.users = new Hash({});
	},
	addUser: function (user) {
		this.users.set(user.id, user);
	},
	removeUser: function (user) {
		this.users.erase(user.id);
	},
	display: function (container) {
		this.users.each(function (user,id) {
			var div = new Element('div', {
				'class' : 'online',
				'id' : 'U'+id	});
			user.display(div);
			div.inject(container);
		});
	}
});
MBChat.Room = new Class({
	online : null,
	initialize: function (rid) {
		this.rid = rid;
		this.online = new Online(rid);
		
//TODO
	}
});