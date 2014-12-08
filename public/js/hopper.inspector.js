

// Load my JSON is what is getting called over and over, everytime there is a room
// state change.  The function is called when an 'updateroomstate' is heard
function load_room_json(hotel_data){

	//we need to add the hopUserData variable to our hotel data..
	hotel_data.hopUserName = hopUserName; //lets hope that works..


        //lets render this json using our dust template
        dust.render("inspector.next_room_grid.dust", hotel_data, function(err, new_html) {
                $('#next_room_grid').html(new_html);
        });

}


  
  	var socket = io.connect(window.location.hostname + ':3000');

	//This is the code that creates the websocket for us...
	
	// on connection to server, ask for user's name with an anonymous callback
	// this really should save the user_id somehow,
	// even putting into get/url somehow... cookie... something dammit...
	socket.on('connect', function(){
		// call the server-side function 'adduser' and send one parameter (value of prompt)
		socket.emit('adduser', hopUserName);
	});

	
	// listener, whenever the server emits 'updateusers', this updates the username list
	socket.on('updateusers', function(data) {
		$('#users').empty();
		$.each(data, function(key, value) {
			$('#users').append('<div>' + key + '</div>');
		});
	});

	
 	// listener, whenever the server emits 'updateroomstate', this updates the rooms state board
        // This function generates all the HTML and Javascript required  to interact with the board
        // by the lobby... Allowing the lobby to change the room state by pressing buttons.
        // The initial status of those buttons will be determined by the state of hotels.json at the time
        // of query.
                      

	socket.on('updateroomstate', function(data) {
               // $('#rooms').empty();
               // $.each(data, function(key, value) {
               //        $('#rooms').append('<div>' + key + '</div>');
               // });
               data_string = JSON.stringify(data, undefined, 2);
               //$('#hotel_grid_debug').html("<pre><code>" + data_string + '</code></pre>');
	       load_room_json(data);

        });

 // listener, whenever the server emits 'updatechat', this updates the chat body
          socket.on('updatechat', function (data) {
		if(data.type == 'chatmessage'){
                	dust.render("hotel_event_feed.dust", data, function(err, new_html) {
                		$('#conversation').prepend('<li>'  + new_html + '</li>');
			});
		}
	}
        );




	//this runs and assigns lots of functions to various elements of the html5 form elements..
	//and that it is what makes a particular button do a particular thing

	$(function(){
		// when the client clicks SEND
		// Assumes just text transfer migrate me to JSON pushes
		$('#rockstar_button').click( function() {
			

			var room_id = safe_get_room_id(); 
			var hotel_id = safe_get_hotel_id();
			var when = new Date().getTime();
			var message = "Rockstar room";
			var json_event_to_send_to_server = {


				"user_id": hopUserName,
				"to_user_id": 'system',
				"type": 'rock_star_room',
				"when": when,
				"hotel_id": hotel_id,
				"message": message,
				"room_id": room_id
			};
			// tell server to execute 'sendchat' and send along one parameter
			console.log(room_id);

			socket.emit('sendchat', json_event_to_send_to_server);

		});

		// Assumes just text transfer migrate me to JSON pushes
		$('#shampoo_button').click( function() {
			var message = "Need shampoo";
			var when = new Date().getTime();
			var room_id = safe_get_room_id();
                        var hotel_id = safe_get_hotel_id();
                        var json_event_to_send_to_server = {
				"user_id": hopUserName,
				"to_user_id": 'system',
                                "type": 'shampoo_room',
                                "when": when,
				"hotel_id": hotel_id,
				"room_id": room_id,
				"message": message
			}
			
			// tell server to execute 'sendchat' and send along one parameter
			socket.emit('sendchat', json_event_to_send_to_server);
		});

		// Assumes just text transfer migrate me to JSON pushes
		$('#cig_button').click( function() {
			var message = "This room has been smoked in!";
			var when = new Date().getTime();
		    	var room_id = safe_get_room_id();
                        var hotel_id = safe_get_hotel_id();	
                        var json_event_to_send_to_server = {
                                "type": 'cig_from_room',
				"user_id": hopUserName,
				"to_user_id": 'system',
                                "room_id": room_id,
				"hotel_id": hotel_id,
				"when": when,
				"message": message
			}
			// tell server to execute 'sendchat' and send along one parameter
			socket.emit('sendchat', json_event_to_send_to_server);
		});
	
		// Assumes just text transfer migrate me to JSON pushes
		$('#surprisepet_button').click( function() {
			var message = "Unexpected pet in room!";
			var room_id = safe_get_room_id();
                        var hotel_id = safe_get_hotel_id();
			var when = new Date().getTime();
                       	var json_event_to_send_to_server = {
                                "type": 'pet_from_room',
                                "message": message,
                        	"hotel_id" : hotel_id,
				"user_id": hopUserName,
				"to_user_id": 'system',
				"room_id": room_id,
				"when": when
			}
			// tell server to execute 'sendchat' and send along one parameter
			socket.emit('sendchat', json_event_to_send_to_server);
		});


		 // Assumes just text transfer migrate me to JSON pushes
                $('#maint_button').click( function() {
                        var message = "This room needs maintenance";
                        var room_id = safe_get_room_id();
                        var hotel_id = safe_get_hotel_id();
                        var when = new Date().getTime();
                        var json_event_to_send_to_server = {
                                "type": 'maint_from_room',
                                "message": message,
                                "hotel_id" : hotel_id,
                                "user_id": hopUserName,
				"to_user_id": 'system',
                                "room_id": room_id,
                                "when": when
                        }
                        // tell server to execute 'sendchat' and send along one parameter
                        socket.emit('sendchat', json_event_to_send_to_server);
                });


		// This is the first and only function that is using JSON pushes
		// Copy this logic to the other button handlers as appropriate...
		$('#roomsubmit_button').click( function() {

			var bathtowel_count = $('#bathtowel_input').val();
			var handtowel_count = $('#handtowel_input').val();
			var facetowel_count = $('#facetowel_input').val();
			var is_iron = $('#iron_checkbox').is(':checked');
			var is_remote = $('#remote_checkbox').is(':checked');
			var room_id = safe_get_room_id();
                        var hotel_id = safe_get_hotel_id();
				

			var message = "Finished Room!";

			var when = new Date().getTime();

			//var user_id = Damn it is not yet stored on the client....
			//var hotel_id = to keep things simple

			var json_event_to_send_to_server = {
				"type": "inspection_level_one", // this tells the server what to expect...
        			"message": message, // this is the text message that should appear on the lobby feed

				"user_id": hopUserName,
				"to_user_id": 'system',
				"room_id": room_id, // this is the room in question
				"hotel_id": hotel_id, // this is the hotel in question hardcoded to one for now		
				//"user_id": 1, // make this work...		
				"when": when, //this is a unique timestamp that I just created...   
				"data": { // this is where the data for the state change lives
					"bathtowel_count": bathtowel_count,		
					"handtowel_count": handtowel_count,		
					"facetowel_count": facetowel_count,		
					"is_iron": is_iron,		
					"is_remote": is_remote,		
			}
		};

			console.log(json_event_to_send_to_server);
			socket.emit('sendchat', json_event_to_send_to_server);
		});

        //$('#datasend').click(tsalb_message);
        $('#data').val('Inspector Start');
        //just call it once manually
        tsalb_message();


	});

