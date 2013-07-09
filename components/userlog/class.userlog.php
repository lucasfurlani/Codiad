<?php

/*
 *  Copyright (c) UPEI lrosa@upei.ca sbateman@upei.ca
 */

class Userlog {

    //////////////////////////////////////////////////////////////////
    // PROPERTIES
    //////////////////////////////////////////////////////////////////
	
	// The user which this log is related to
    public $username    = '';
	// Type of Log
	/*
	 * logged_in
	 * logged_out
	 * session
	 */
    public $type 					= '';
	// The timestamp that this log was registered
	public $start_timestamp			= '';
	// The timestamp of the last update
	public $last_update_timestamp 	= '';
	// The last timestamp
	public $end_timestamp			= '';
	// Identify if the session is open
	public $is_open					= '';
	// Path of the subject: can be a file path or a project name (path)
	public $path					= '';
	// Session timeout
	private $session_timeout		= 10; // minutes
	// File timeout
	private $file_timeout			= 5; // seconds
	// The session ID to be saved in the file
	public $session_id				= '';
	
    //////////////////////////////////////////////////////////////////
    // METHODS
    //////////////////////////////////////////////////////////////////

    // -----------------------------||----------------------------- //
	
    //////////////////////////////////////////////////////////////////
    // Save
    //////////////////////////////////////////////////////////////////

    public function Save(){
		$collection = $this->GetCollection();
			
		$new_log = array( 	
							"username" => $this->username,
							"type" => $this->type,
							"start_timestamp" => date("Y-m-d H:i:s")
						 );
		
		// Insert the log in the database:
		return $collection->insert($new_log);
    }
	
	//////////////////////////////////////////////////////////////////
    // Save Session
    //////////////////////////////////////////////////////////////////

    public function SaveAsSession(){
		$collection = $this->GetCollection();
			
		$new_log = array( 	
							"username" => $this->username,
							"type" => "session",
							"start_timestamp" => date("Y-m-d H:i:s"),
							"last_update_timestamp" => date("Y-m-d H:i:s"),
							"is_open" => 'TRUE'
						 );
		
		// Insert the log in the database:
		return $collection->insert($new_log);
    }
	
	//////////////////////////////////////////////////////////////////
    // Save as File
    //////////////////////////////////////////////////////////////////

    public function SaveAsFile(){
		$collection = $this->GetCollection();
			
		$new_log = array( 	
							"username" => $this->username,
							"type" => "file",
							"start_timestamp" => date("Y-m-d H:i:s"),
							"last_update_timestamp" => date("Y-m-d H:i:s"),
							"is_open" => 'TRUE',
							"session_id" => $this->GetCurrentSessionId(),
							"path" => $this->path
						 );
		
		// Insert the log in the database:
		return $collection->insert($new_log);
    }
	
	//////////////////////////////////////////////////////////////////
    // Update current session
    //////////////////////////////////////////////////////////////////
    
	public function UpdateCurrentSession(){
		$collection = $this->GetCollection();
			
		$log = $collection->findOne(array("username" => $this->username, "is_open" => 'TRUE', "type" =>"session"));
		if (isset($log['username'])) {
			$now = strtotime(date("Y-m-d H:i:s"));
			$last_update_timestamp = strtotime($log['last_update_timestamp']);
			$time_difference =  $this->DateMinuteDifference ($now, $last_update_timestamp);
			
			if ($time_difference >= $this->session_timeout) {
				$log['is_open']			= 'FALSE';
				
				// Overwrite the log in the database:
				$collection->update(array("username" => $this->username, "is_open" => 'TRUE', "type" =>"session"), $log);
				
				$this->SaveAsSession();
			} else {
				$log['last_update_timestamp'] = date("Y-m-d H:i:s");
				// Overwrite the log in the database:
				return $collection->update(array("username" => $this->username, "is_open" => 'TRUE', "type" =>"session"), $log);	
			}
		} else {
			$this->SaveAsSession();
		}
    }
	
	//////////////////////////////////////////////////////////////////
    // Update current file session
    //////////////////////////////////////////////////////////////////
	
	public function UpdateCurrentFile(){
		$collection = $this->GetCollection();
		$current_type = "file";
		
		$log = $collection->findOne(array("username" => $this->username, "is_open" => 'TRUE', "type" => $current_type, 'path' => $this->path));
		if (isset($log['username'])) {
			$now = strtotime(date("Y-m-d H:i:s"));
			$last_update_timestamp = strtotime($log['last_update_timestamp']);
			$time_difference =  $this->DateSecondDifference($now, $last_update_timestamp);
			
			error_log("Time difference for file : " . $time_difference);
			
			if ($time_difference >= $this->file_timeout) {
				
				//$log['is_open']	= 'FALSE';
				
				// Overwrite the log in the database:
				//$collection->update(array("username" => $this->username, "is_open" => 'TRUE', "type" => $current_type, 'path' => $this->path), $log);
				
				// Update all the other logs for files to closed
				$collection->update(
				    array("username" => $this->username, "is_open" => 'TRUE', "type" => $current_type),
				    array('$set' => array('is_open' => "FALSE")),
				    array("multiple" => true)
				);
				
				$this->SaveAsFile();
			} else {
				$log['last_update_timestamp'] = date("Y-m-d H:i:s");
				// Overwrite the log in the database:
				return $collection->update(array("username" => $this->username, "is_open" => 'TRUE', "type" => $current_type, 'path' => $this->path), $log);	
			}
		} else {
			$collection->update(
			    array("username" => $this->username, "is_open" => 'TRUE', "type" => $current_type),
			    array('$set' => array('is_open' => "FALSE")),
			    array("multiple" => true)
			);
				
			$this->SaveAsFile();
		}
    }
	
	//////////////////////////////////////////////////////////////////
    // GetCollection
    //////////////////////////////////////////////////////////////////
    
	private function GetCollection() {
		// Connect
		$mongo_client = new MongoClient();
		// select the database
		$database = $mongo_client->codiad_database;
		// Select the collection 
		$collection = $database->logs;
		// Return the collection
		return $collection;
	}
	
	private function GetCurrentSessionId() {
		$collection = $this->GetCollection();
		$log = $collection->findOne(array("username" => $this->username, "is_open" => 'TRUE', "type" =>"session"));
		
		if (!isset($log['username'])) {
			$this->SaveAsSession();
			$log = $collection->findOne(array("username" => $this->username, "is_open" => 'TRUE', "type" =>"session"));
		} 
		
		return $log['_id'];
	}
	
	private function DateMinuteDifference ($date1timestamp, $date2timestamp) {
		
		$all = round(($date1timestamp - $date2timestamp) / 60);
		$day = floor ($all / 1440);
		$hour = floor (($all - $day * 1440) / 60);
		$minute = $all - ($day * 1440) - ($hour * 60);
		
		return $minute;
	}
	
	private function DateSecondDifference ($date1timestamp, $date2timestamp) {
		return ($date1timestamp - $date2timestamp);
	}
}