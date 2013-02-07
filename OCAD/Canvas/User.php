<?php
class OCAD_Canvas_User extends OCAD_Canvas_Model
{
	public $id;
	public $sisUserId;
	public $name;
	public $sortableName;
	public $shortName;
	public $loginId;
	public $sisLoginId;
	public $avatarUrl;
	public $primaryEmail;

	/**
	 * Loads data for a user
	 * @param array|int|string
	 * @throws OCAD_Canvas_Exception
	 * @return OCAD_Canvas_User
	 */
	public static function load($init)
	{
		$user = new OCAD_Canvas_User();
		
		if(is_array($init))
		{
			// $init is an array of data
			$user->_loadFromData($init);
		}
		elseif( is_int($init) || is_string($init))
		{
			// $init is a canvas user id, OCAD username or OCAD user object. Load data from the API
			$client = self::_getClient();
			$client->setMethod(Zend_Http_Client::GET);
			
			if(is_int($init))
			{
				$client->setEndpoint("users/$init/profile");
			}
			elseif(is_string($init))
			{
				$client->setEndpoint("users/sis_user_id:{$init}/profile");
			}
			
			$response = $client->request();
			$user->_loadFromData($response);
		}
		else 
		{
			throw new OCAD_Canvas_Exception('Invalid initialization parameters for loading user data');
		}
		
		return $user;
	}
	
	/**
	 * Initialize the user param from an array of data
	 * Data will generally be a response from the API
	 * @param array $data
	 */
	protected function _loadFromData($data)
	{
		$this->id 	    = $data['id'];
		$this->name 	    = $data['name'];
		$this->sortableName = $data['sortable_name'];
		$this->shortName    = $data['short_name'];
		$this->loginId 	    = $data['login_id'];
		
		// Optional params
		$this->sisLoginId   = isset($data['sis_login_id']) ? $data['sis_login_id'] : null;
		$this->avatarUrl    = isset($data['avatar_url']) ? $data['avatar_url'] : null;
		$this->primaryEmail = isset($data['primary_email']) ? $data['primary_email'] : null;
	}
	
	/**
	 * Load all logins for this user
	 * @return array An arry of OCAD_Canvas_Login objects
	 */
	public function getLogins()
	{
		$logins = array();
		$response = self::_getClient()->setMethod(Zend_Http_Client::GET)
			                      ->setEndpoint('users/'.$this->id.'/logins')
		 			      ->request();
		foreach($response as $item)
		{
			$logins[] = OCAD_Canvas_Login::load($item);
		}
		
		return $logins;
	}
	
	/**
	 * Load todo items for this user
	 */
	public function getTodos()
	{
		return self::_getClient()->setMethod(Zend_Http_Client::GET)
								 ->setEndpoint('users/self/todo')
								 ->setParameterGet('as_user_id', $this->id)
								 ->request();
	}
	
	/**
	 * Load activity stream items
	 */
	public function getActivityStream()
	{
		return self::_getClient()->setMethod(Zend_Http_Client::GET)
					 ->setEndpoint('users/self/activity_stream')
					 ->setParameterGet('as_user_id', $this->id)
					 ->request();
	}

	/**
	 * Get courses for this user
	 * @todo use as_user_id instead?
	 */
	public static function getCourses($token)
	{
		try 
		{		 							
			return self::_staticRequest('courses', $token);
		}
		catch (OCAD_Canvas_Client_Exception $e)
		{
			$response = $e->getResponse();
			return array();
			
			/**
			 * @todo handle this error better. 
			 */
			//$response = $e->response;
			//if($response->getStatus() == 401)
			//{
				//echo 'unauthorized';
			//}
		}
	}

	/**
	 * Create a new Canvas user from the given OCAD user
	 * @param $user An objects representing the user. See todo note
	 * @throws OCAD_Exception

	 * @todo There are some assumptions about $user. It should really implement an interface that enforces
	 *    - getUsername()
	 *    - getRealName()
	 *    - getEmail()
	 */
	public static function create($user)
	{
		// First, check if the user already exists
		if(self::userExists($user))
		{
			throw new OCAD_Canvas_Exception("User {$user->getUsername()} already exists");			
		}
		
		// Create user
		$client = self::_getClient();
		$client->setMethod(Zend_Http_Client::POST);
		$client->setEndpoint('accounts/'.$client->defaultAccount.'/users');
									
		// Initially, set unique_id to email address, because this is the only way to set an email address
		// We will update unique id to the login username later
		$client->setParameterPost(
			array(
				'user[name]'			=> $user->getRealName(),  	// The full name of the user. This name will be used by teacher for grading.
				//'pseudonym[unique_id]'	=> $user->getUsername(),	// UserÕs login ID.
				'pseudonym[unique_id]'		=> $user->getEmail(),		// UserÕs login ID.
				'pseudonym[sis_user_id]'  	=> $user->getUsername(), 	// [Integer] SIS ID for the userÕs account. To set this parameter, the caller must be able to manage SIS permissions.
				'pseudonym[:send_confirmation]'	=> '0',				// 0|1 [Integer] Send user notification of account creation if set to 1.
				//'user[short_name]'		=> '',				// UserÕs name as it will be displayed in discussions, messages, and comments.
				//'user[sortable_name]'		=> '',				// UserÕs name as used to sort alphabetically in lists.
				//'pseudonym[password]'		=> '',				// UserÕs password.
				//'user[time_zone]'		=> '',				// The time zone for the user. Allowed time zones are listed [here](http://rubydoc.info/docs/rails/2.3.8/ActiveSupport/TimeZone).
			)
		);
		
		// Make the request to create the user
		$response = $client->request();
		$canvasUser = OCAD_Canvas_User::load($response);
		
		// Get the user's logins
		$logins = $canvasUser->getLogins();

		// At this point the user will only have one login. 
		// Grab it and reset the unique_id to the users' login username
		$login = array_pop($logins);
		$login->uniqueId = $user->getUsername();
		$login->save();
		
		return $canvasUser;
	}
	
	/**
	 * Check if the specified user exists
	 * @param array|int $init @see OCAD_Canvas_User::load
	 * @throws OCAD_Canvas_Exception
	 * @return bool
	 */
	public static function userExists($init)
	{
		$userExists = false;
		try 
		{
			$testUser = OCAD_Canvas_User::load($init);
			$userExists = true;
		}
		catch (OCAD_Canvas_Client_Exception $e)
		{
			// If the error wasn't a 404 (user not found), re-throw the exception
			if($e->getCode() !== 404)
			{
				throw $e;
			}
		}
		return $userExists;
	}
	
	
	protected static function _staticRequest($endpoint, $token)
	{
		return  self::_getClient()->setMethod(Zend_Http_Client::GET)
					  ->setEndpoint($endpoint)
					  ->setParameterGet('access_token', null)
					  ->setHeaders('Authorization', "Bearer $token")
					  ->request();
	}
}
