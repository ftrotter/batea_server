//Rick do you think you could finish making these

//Globally shared hopper client functions

        function runTimeago(){
		$('time.for_timeago').each(function() {
    			time_words = jQuery.timeago(parseInt($(this).attr('datetime')));
    			$(this).html(time_words);
		});
        }


//This function interfaces with a RESTish system that sets new values on the simple values in the rooms, good for any single key value pair..
function roomDataChanger(hotel_id,room_id,target,new_value){

	dataChangeUrl = 
		'/roomupdate?hotel_id=' + hotel_id + 
			'&room_id=' + room_id +
			'&target=' + target +
			'&new_value=' + new_value;


	$.getJSON(dataChangeUrl, function(data) {

		if(data.result){
			//then we were successful!!
			//we really need to nothing here..
		}else{
			alert("Communications with the hopper server is broken. Contact support and read them the following: roomDataChanger AJAX broken with" + JSON.stringify(data));
		}

	});
}

//This function interfaces with a RESTish system that subtracts values to subarrays on a given room.
function roomDataArraySubtractor(hotel_id,room_id,target,removed_value){

	dataChangeUrl = 
		'/roomsubtractor?hotel_id=' + hotel_id + 
			'&room_id=' + room_id +
			'&target=' + target +
			'&removed_value=' + removed_value;


	$.getJSON(dataChangeUrl, function(data) {

		if(data.result){
			//then we were successful!!
			//we really need to nothing here..
		}else{
			alert("Communications with the hopper server is broken. Contact support and read them the following: roomDataChanger AJAX broken with" + JSON.stringify(data));
		}

	});
}



//This function interfaces with a RESTish system that addes values to subarrays on a given room.
function roomDataArrayAdder(hotel_id,room_id,target,added_value){

	dataChangeUrl = 
		'/roomadder?hotel_id=' + hotel_id + 
			'&room_id=' + room_id +
			'&target=' + target +
			'&added_value=' + added_value;


	$.getJSON(dataChangeUrl, function(data) {

		if(data.result){
			//then we were successful!!
			//we really need to nothing here..
		}else{
			alert("Communications with the hopper server is broken. Contact support and read them the following: roomDataChanger AJAX broken with" + JSON.stringify(data));
		}

	});
}

//functions that rely on the getUrlParam jquery module to get the room_id and hotel_id 
// across differen libraries
        function safe_get_room_id(){
                var room_id = $(document).getUrlParam('room_id');

                if(room_id === null){
                        alert("no room id");
                        throw new Error("no room_id!");
                }
                return room_id;
        }

         function safe_get_hotel_id(){
                var hotel_id = $(document).getUrlParam('hotel_id');

                if(hotel_id === null){
                        alert("no hotel id");
                        throw new Error("no hotel_id!");
                }
                return hotel_id;
        }





	function datePickerBuild(){

		$('.date').each(function() {
			$(this).datetimepicker({
				numberOfMonths: 2,
				ampm: false,
				stepMinute: 10,
				hourGrid: 4,
				minuteGrid: 10,
				hour: 11,
				minute: 0,
				onClose: function(dateText, inst){
					console.log("fuck this is hard" + inst.id);
					myDate = $('#' + inst.id).datetimepicker("getDate");
					myTimestamp = myDate.getTime();
					console.log("please please god" +myTimestamp);
					id_array = inst.id.split('_');
					hotel_id = id_array[0];
					room_id = id_array[1];
					roomDataChanger(hotel_id,room_id,'check_out_expected_timestamp',myTimestamp);
				}

			});
		});

	}



        function globalReset(){
		//this function lives to perform any functions that work across 
		//all of the pages, like the runTimeago function
		runTimeago();        
        }

	//allows us to use timeAgo on future stuff...
	jQuery.timeago.settings.allowFuture = true;

function tsalb_message(){

    var message = $('#data').val();
    $('#data').val('');
    // tell server to execute 'sendchat' and send along one parameter
    var hotel_id = safe_get_hotel_id();
    var when = new Date().getTime();

    var json_event_to_send_to_server = {
        "user_id": hopUserName,
        "to_user_id": 'all',
        "type": 'chatmessage',
        "when": when,
        "hotel_id": hotel_id,
        "message": message,
    };

    socket.emit('sendchat', json_event_to_send_to_server);

}