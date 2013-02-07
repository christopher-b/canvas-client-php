<?php
class OCAD_Canvas_Course extends OCAD_Canvas_Model
{
	const STATE_CREATED 	= 'created';
	const STATE_CLAIMED 	= 'claimed';
	const STATE_AVAILABLE 	= 'available';
	const STATE_COMPLETED 	= 'completed';
	const STATE_DELETED 	= 'deleted';
	
	public $id;
	public $accountId;
	public $name;
	public $courseCode;
	public $sisCourseId;
	public $calendar;
	public $enrollments;
	
	public $students = array();
	public $teachers = array();
	public $tas	 = array();
	public $obervers = array();
	
	public $grades;
	
	protected $_usersLoaded = false;
	
	public static function load($init)
	{
		$course = new OCAD_Canvas_Course();
		
		if(is_array($init))
		{
			// $init is an array of data
			$course->_loadFromData($init);
		}
		elseif( is_string($init) || is_int($init))
		{
			// $init is an SIS id (string) or Canvas native ID (int). Load from API
			$client = self::_getClient();
			$client->setMethod(Zend_Http_Client::GET);
			
			if(is_int($init))
			{
				$client->setEndpoint("courses/$init");
			}
			else
			{
				$init = urlencode($init); // account for strange character in SIS ID (#'s in tutorial section ids)
				$client->setEndpoint("courses/sis_course_id:$init");
			}
			
			$response = $client->request();
			$course->_loadFromData($response);
		}
		else 
		{
			throw new OCAD_Canvas_Exception('Invalid initialization parameters for loading course data');
		}
		
		return $course;
	}
	
	/**
	 * Initialize the course params from an array of data
	 * Data will generally be a response from the API
	 * @param array $data
	 */
	protected function _loadFromData($data)
	{
		$this->id 	   = $data['id'];
		$this->accountId   = $data['account_id']; 
		$this->name	   = $data['name'];
		$this->courseCode  = $data['course_code'];
		$this->sisCourseId = $data['sis_course_id'];
		$this->calendar	   = $data['calendar'];
		//$this->enrollments 	= (array_key_exists('enrollments', $data)) ? $data['enrollments'] : null;
	}
	
	protected function _loadUsers()
	{
		if($this->_usersLoaded) return;
		
		$client = self::_getClient();
		$client->setEndpoint("courses/{$this->id}/users");
		$client->setParameterGet(array('include[]'=>'enrollments'));
		$client->setParameterGet('per_page',50);
		
		$next = true;
		while($next)
		{
			$response = $client->request();
			foreach($response as $userData)
			{
				$user = OCAD_Canvas_User::load($userData);
				foreach($userData['enrollments'] as $enrollment)
				{
					switch($enrollment['type'])
					{
						case 'StudentEnrollment':
							$this->students[$user->id] 	= $user;
							break; 
						case 'TeacherEnrollment':
							$this->teachers[$user->id] 	= $user;
							break; 
						case 'TaEnrollment':
							$this->tas[$user->id] 		= $user;
							break; 
						case 'ObserverEnrollment':
							$this->obervers[$user->id] 	= $user;
							break; 
					}
				}
			}
			$links = $this->_parsePagination($client->getLastResponse());
			$next = array_key_exists('next', $links);
			if($next)
			{
				$client->setEndpoint($links['next']);
			}
		}
		
		$this->_usersLoaded = true;
	}
		
	public function publish()
	{
		// Make a put request 
		$client = self::_getClient();
		$client->setMethod(OCAD_Canvas_Client::PUT);
		$client->setEndpoint("courses/{$this->id}");
		$client->setParameterPost( array(
			'offer'=> true,
			'course[name]' => $this->name, // required because the request must have at least one "course" param
		));
		
		$client->request();	
	}
	
	public function getStudents()
	{
		$this->_loadUsers();
		return $this->students;
	}
	
	/**
	 * @return OCAD_Canvas_User
	 */
	public function getStudent($id)
	{
		$this->_loadUsers();
		if(!array_key_exists($id, $this->students))
		{
			throw new Exception("There is no student with ID $id in this course");
		}
		return $this->students[$id];
	}
	
	public function getGrades($includeMuted = true)
	{
		if(null === $this->grades)
		{
			$client = self::_getClient();
			$client->setEndpoint("courses/{$this->id}/students/submissions");
			// Need to do awkward workaround to get this to work
			// Because of multiple instances of &students_ids[]
			$uri  = $client->getUri(true).'?';
			$uri .= 'access_token='.OCAD_Canvas_Client::$apiConfig['apiKey'];
			$uri .= '&include[]=total_scores';
			$uri .= '&grouped=1';
			
			$students = $this->getStudents();
			foreach($students as $student)
			{
				$uri .= '&student_ids[]='.$student->id;
			}
			$response = file_get_contents($uri);
			$data = json_decode($response, true);
			
			if(isset($data['status']) && $data['status'] === 'not_found')
			{
				$message = "{$data['message']} (Canvas Error ID {$data['error_report_id']} )";
				throw new Exception("There was an error loading grades for this course: $message");
			}
			
			$grades = array();
			
			foreach ($data as $datum)
			{
				$grade 		= ($includeMuted) ? $datum['computed_final_score_with_muted'] : $datum['computed_final_score']; 
				$student 	= $students[$datum['user_id']];
				$grades[$datum['user_id']] 	= new OCAD_Canvas_Grade($grade, $student, $this);
			}
			
			$this->grades = $grades;
		}
		return $this->grades;
			
		/*
		// This won't work because zend only lets us set one student_id[] param 
		$client->setParameterGet('grouped', '1');
		$client->setParameterGet('include[]', 'total_scores');
		
		$studentIds = array();
		foreach($this->getStudents() as $student)
		{
			$studentIds[] = $student->id;
			//$client->setParameterGet('student_ids[]', $student->id);
		}
		$response = $client->request();
		$submissions = array();
		$this->submissions = $submissions;
		*/
	}
	
	/*
	public function getSections()
	{
		if($this->sections === NULL)
		{
			$client = self::_getClient();
			$client->setEndpoint("courses/{$this->id}/sections");
			//$client->setParameterGet(array('include[]'=>'students'));
			$response = $client->request();
			OCAD_Helper::p($response);die;
		}
		
		return $this->sections;
	}
	*/
	
	public function getGradeForStudent(OCAD_Canvas_User $student)
	{
		// Make sure grades are loaded
		$this->getGrades();
		
		if(array_key_exists($student->id, $this->grades))
		{
			return $this->grades[$student->id];
		}
		else 
		{
			return false;
		}
	}
}
