<?php

//My attempt to creat a mongo abstraction library that accepts 
//data as an array (using data_array) (which is clearly the wrong way to do it..
//and has the capacity to both save with and without versioning
class VeryMongo{

        var $mongo;
	var $data_array = array();

	var $mongo_url;
	var $MongoClass;


        function __construct($db_array = null){

		if(!is_array($db_array) && !is_null($db_array)){
			echo "VeryMongo: I can read a config from the Laravel mongodb config, or can have a db_config array passed in... but this... this.. is something else";
			exit();
		}


		//I want this to be testable...
		if(is_null($db_array)){
			$db_array = Config::get('database.connections.mongodb',false);
			if(!$db_array){
				echo "VeryMongo: There is no database configuration\n";
				exit();
			}
		}

		if(isset($db_array['host'])){
			$host = $db_array['host'];
		}else{
			if(isset($db_array['hostname'])){
				$host = $db_array['hostname'];
			}else{
				echo "VeryMongo: Missing host or hostname configuration";
				exit();
			}
		}
		
		$port = $db_array['port'];
                if(isset($db_array['db'])){
                        $dbname = $db_array['db'];
                }else{
                        if(isset($db_array['database'])){
                                $dbname = $db_array['database'];
                        }else{
                                echo "VeryMongo: Missing host or hostname configuration";
                                exit();
                        }
                }


		if(isset($db_array['username'])){
			$user = $db_array['username'];
			$passwd = $db_array['password'];
			$this->mongo_url = "mongodb://$user:$passwd@$host:$port/$dbname";
		}else{
			$this->mongo_url = "mongodb://$host:$port/$dbname";
		}
	

		if(!class_exists('MongoClient')){
			$this->MongoClass = 'Mongo';	
		}else{
			$this->MongoClass = "MongoClient";
		}

		try{
			$db = new $this->MongoClass($this->mongo_url);
		}
		catch( MongoCursorException $e){
			//this usually means a poor connection
			$this->_mongoFail($e);
		} catch (Exception $e) {
			$this->_mongoFail($e);
			
		}
                $this->mongo = $db->$dbname;
        }

function _mongoFail($e){

                        echo "VeryMongo: Died with ". $e->getMessage(). "\n";
                        echo "VeryMongo: Using PECL class $this->MongoClass\n";
                        echo "VeryMongo: Tried to connect with \n ".$this->mongo_url."\n";
                        exit();

}

//returns the cursor to all records
function get_all(){

	$cursor = $this->get_all_cursor();
        return(iterator_to_array($cursor));

}

function get_all_cursor(){

	$name = strtolower(get_class($this));
        $collection = $this->mongo->$name;
        $cursor = $collection->find();
	return $cursor;

}

function get_all_reverse(){

	$cursor = $this->get_all_reverse_cursor();
        return(iterator_to_array($cursor));
	
}

function get_all_reverse_cursor(){

        $name = strtolower(get_class($this));
        $collection = $this->mongo->$name;
        $cursor = $collection->find();
	$cursor = $cursor->sort(array("_id" => -1)); 
        return($cursor);

}

//this function puts sub arrays last in the JSON for more readable Mongo documents
//stuff that is not in a sub array is much more important can be easily indexed etc etc..
//for now, we do not use this...

function _arrays_last($a,$b){

	if(!is_array($a) or !is_array($b)){
		return(0);//dont care
	}

	$a_size = count($a);
	$b_size = count($b);

	if($a_size > $b_size){
		return(1);
	}else{
		return(-1);
	}
	return(0); //they are the same size
}

/**
 * sync takes all of the data that you have placed in data_array and saves it to the mongo database
 * if their is data in the database, it copies it out... so it is the load function...
 * then it takes all of the data that is user created and in data_array and smashes it on top of the current array
 * Everything in data_array overwrites anything currently in the database... but otherwise the contents of the db are 
 * maintained...
**/
function sync($id = 0, $versioning = false){


        if(is_numeric($id)){
		$id = (int) $id;
	}else{
		//its a string, like an email that potentially might have special characters
		$id = (string) $id;
	}

	$name = strtolower(get_class($this));
	$local_id = $name.'_id';
        $collection = $this->mongo->$name; //mongo comes from the MongoObject class...

	$new_data = false;
        if($id !== 0){
		$find = array($name.'_id' => $id);
		//for whatever reason the '@' symbol in emails causes fits with findOne.
		//so we use find and getNext to make our own findOne
                $db_cursor = $collection->find($find);
		try{
			$db_array = $db_cursor->getNext();
		} catch( MongoCursorException $e){
                        //this usually means a poor connection
			$this->_mongoFail($e);
                }

		if(is_null($db_array)){ //sometimes I will have an id, but the db will not know it yet...
			$db_array = array($local_id => $id); //lets make an array with just the id in it..
			$new_data = true;
		}
        }else{
                $db_array = array();
		$new_data = true;
        }

        
        if(isset($this->data_array)){
                foreach($this->data_array as $key => $data){
                        $db_array[$key] = $data;

                }
        }

        $merged_array = $db_array; // after the for loop above we have merged the two data sources.

        $merged_array[$local_id] = $id; //just to be sure the id stays the same...


	//Begin array beautification!!
	//
	//
	uksort($merged_array, 'strnatcasecmp'); //alphabetical sort caseinsentitive, by keys

	$tmp_array = array();
	foreach($merged_array as $key => $sub){
		if(is_array($sub)){
			unset($merged_array[$key]); //take it away from where it is
			$tmp_array[$key] = $sub; //put it somewhere safe
		}
	}

	uksort($tmp_array, 'strnatcasecmp'); //alphabetical sort caseinsentitive, by keys
	foreach($tmp_array as $key => $sub){
		//now lets put them all at the end of the 
		$merged_array[$key] =$sub;
	}
	//
	//
	//End array beautification

	$which_to_upsert = [$local_id => $id];

	if(isset($merged_array['_id']) && $id = 0){
		echo "VeryMongo Error: 
			You are trying to save an existing mongo document as a new document. 
			You either need to not have the Mongo '_id' variable,
			or you need to be passing in an existing $local_id";
		exit(); 
	}

        //mongo magic that saves our data...
        $collection->update($which_to_upsert,$merged_array,array('upsert' => true, 'fsync' => true));
        //save it for the other functions...
        $this->data_array = $merged_array;
        return($merged_array);
}

//my delete function
function remove($id = 0){

	if($id === 0){
		echo "VeryMongo: Calling remove with no argument. Fail. $id";
		die();
	}

        if(is_numeric($id)){
                $id = (int) $id;
        }else{
                //its a string, like an email that potentially might have special characters
                $id = (string) $id;
        }

        $name = strtolower(get_class($this));
        $collection = $this->mongo->$name; //mongo comes from the MongoObject class...

        $remove = array($name.'_id' => $id);
                //for whatever reason the '@' symbol in emails causes fits with findOne.
                //so we use find and getNext to make our own findOne
        $collection->remove($remove,array("justOne" => true));

}

//Lovely function borrowed from http://stackoverflow.com/questions/3876435/recursive-array-diff
function arrayRecursiveDiff($aArray1, $aArray2) {
  $aReturn = array();

  foreach ($aArray1 as $mKey => $mValue) {
    if (array_key_exists($mKey, $aArray2)) {
      if (is_array($mValue)) {
        $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]);
        if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
      } else {
        if ($mValue != $aArray2[$mKey]) {
          $aReturn[$mKey] = $mValue;
        }
      }
    } else {
      $aReturn[$mKey] = $mValue;
    }
  }
  return $aReturn;
} 

function fromJSON($json){
	$this->data_array = json_decode($json,true);
	$this->sync();
}

function toJSON(){

	$json = json_encode($this->data_array);
	return($json);
}



}
?>
