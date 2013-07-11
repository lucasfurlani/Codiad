<?
	// TODO Close all the sections that are open and passed the timeout
	
	
	require_once('../../../common.php');
    require_once('../class.userlog.php');
	require_once('../../user/class.user.php');
	require_once('../../project/class.project.php');
	
	
	$MainUserlog = new Userlog();
	$MainUserlog->CloseAllOpenSectionsThatReachedTimeout();
	
	$User = new User();
	//$User->users = getJSON('users.php');
	// Connect
	$mongo_client = new MongoClient();
	// select the database
	$database = $mongo_client->codiad_database;
	// Select the collection 
	$collection = $database->users;
	
	$users = $User->GetUsersInTheSameCoursesOfUser($_SESSION['user']);
	
	$user_types = $User->GetUsersTypes();
	$student_user_type = $user_types[0];
	
	foreach($users as $user) {
		if ($user['type'] == $student_user_type) {
			$Userlog = new Userlog();
			echo "<br><br>";
			echo "<h1>User: " . $user['username'] . "</h1>";
			$Userlog->username = $user['username'];
			$sessions = $Userlog->GetAllSessionsForUser();
			$total_time_system = new DateTime('0000-00-00 00:00:00');
			$total_time_system_helper = clone $total_time_system;
			
			echo "<h2>Sessions:</h2>";
			
			foreach($sessions as $session) {
				//$total_time_system += (strtotime($session['last_update_timestamp']) - strtotime($session['start_timestamp']));
				$date1 = new DateTime($session['start_timestamp']);
				$date2 = new DateTime($session['last_update_timestamp']);
				
				$interval = $date1->diff($date2);
				
				$total_time_system->add($interval);
				echo "Total time user spend in session " . $session['_id'] . ": <br>";
				printf("&nbsp;&nbsp;&nbsp; %d years, %d months, %d days, %d hours, %d minutes, %d seconds <br>", $interval->y, $interval->m, $interval->d, $interval->h, $interval->i, $interval->s);
							
			}	
			$total_time_system_interval = $total_time_system_helper->diff($total_time_system);
			
			
			echo "<h3>Total time the user spend in the system is:<br>";
			printf("&nbsp;&nbsp;&nbsp; %d years, %d months, %d days, %d hours, %d minutes, %d seconds <br>", 
					$total_time_system_interval->y, 
					$total_time_system_interval->m, 
					$total_time_system_interval->d, 
					$total_time_system_interval->h, 
					$total_time_system_interval->i, 
					$total_time_system_interval->s);
			echo "</h3>";
			
			//$projects = $user['projects'];
			// Get all the user's projects
			$Project = new Project();
			$projects = $Project->GetProjectsForUser($user['username']);
			
			echo "<h2>Projects:</h2>";
			
			foreach($projects as $project) {
				echo "<h4>This is project " . $project['name']. "</h4>";
				$Userlog->path = $project['path'];
				$project_sessions = $Userlog->GetAllLogsForProject();
				
				
				$total_time_project = new DateTime('0000-00-00 00:00:00');
				$total_time_project_helper = clone $total_time_project;
			
				foreach ($project_sessions as $project_session) {
					$date1 = new DateTime($project_session['start_timestamp']);
					$date2 = new DateTime($project_session['last_update_timestamp']);
					$interval = $date1->diff($date2);
					
					$total_time_project->add($interval);
					echo "Total time user spend in a session of project " . $project['path'] . ": <br>";
					printf("&nbsp;&nbsp;&nbsp; %d years, %d months, %d days, %d hours, %d minutes, %d seconds <br>", $interval->y, $interval->m, $interval->d, $interval->h, $interval->i, $interval->s);
				}
				
				$total_time_project_interval = $total_time_project_helper->diff($total_time_project);
				
				
				echo "<h3>Total time the user spend in the project is:<br>";
				printf("&nbsp;&nbsp;&nbsp; %d years, %d months, %d days, %d hours, %d minutes, %d seconds <br>", 
						$total_time_project_interval->y, 
						$total_time_project_interval->m, 
						$total_time_project_interval->d, 
						$total_time_project_interval->h, 
						$total_time_project_interval->i, 
						$total_time_project_interval->s);
				echo "</h3>";
			}
		}
	}

	
	function pp($arr){
	    $retStr = '<ul>';
	    if (is_array($arr)){
	        foreach ($arr as $key=>$val){
	            if (is_array($val)){
	                $retStr .= '<li>' . $key . ' => ' . pp($val) . '</li>';
	            }else{
	                $retStr .= '<li>' . $key . ' => ' . $val . '</li>';
	            }
	        }
	    }
	    $retStr .= '</ul>';
	    return $retStr;
	}
		
?>