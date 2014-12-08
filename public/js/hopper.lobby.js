
var socket = io.connect(window.location.hostname + ':3000');


// Load my JSON is what is getting called over and over, everytime there is a room
// state change.  The function is called when an 'updateroomstate' is heard
function load_lobby_json(hotel_data){
        //lets render this json using our dust template
        dust.render("lobby.hotel_grid.dust", hotel_data, function(err, new_html) {
                $('#hotel_grid').html(new_html);
                $('#hotel_table').tablesorter();
        });

        dust.render("lobby.assignment_grid.dust", hotel_data, function(err, new_html) {
                $('#room_assignment_grid').html(new_html);
        });

	runTimeago(); //turn all of my timestamps to strings!!
	datePickerBuild();

	$(':checkbox').change(function() {
	    	var $this = $(this);
    		// $this will contain a reference to the checkbox 
    		room_id = $this.data('room_id');
    		username = $this.data('username');
    		
    		if ($this.is(':checked')) {
			roomDataArrayAdder(hotel_id,room_id,'users_currently_assigned',username);
    		} else {
			roomDataArraySubtractor(hotel_id,room_id,'users_currently_assigned',username);
    		}	
});

}


        // We use this function to let the server know what username this client represents
        socket.on('connect', function(){
                // call the server-side function 'adduser' and send one parameter (value of prompt)

                socket.emit('adduser', hopUserName);
        });
 
 // listener, whenever the server emits 'updatechat', this updates the chat body
          socket.on('updatechat', function (data) {
         	dust.render("hotel_event_feed.dust", data, function(err, new_html) {
		$('#conversation').prepend('<li>'  + new_html + '</li>');
        });


		 });



      // listener, whenever the server emits 'updateusers', this updates the username list
        socket.on('updateusers', function(data) {
                $('#users').empty();
                $.each(data, function(key, value) {
                        $('#users').append("<div class='grid_8'>" + key + '</div>');
                });
        });

        // listener, whenever the server emits 'updateroomstate', this generates the HTML
        socket.on('updateroomstate', function(data) {
                 data_string = JSON.stringify(data, undefined, 2);
                 $('#hotel_grid_debug').html("<pre><code>" + data_string + '</code></pre>');
		 load_lobby_json(data);
        });





        // on load of page
        $(function(){

                $('#datasend').click(tsalb_message);

                // when the client hits ENTER on their keyboard
                $('#data').keypress(function(e) {
                        if(e.which == 13) {
                                $(this).blur();
                                $('#datasend').focus().click();
                        }
                });

            $('#data').val('Lobby Start');
            tsalb_message();

        });


