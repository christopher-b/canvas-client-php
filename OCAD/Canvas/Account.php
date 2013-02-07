<?php
class OCAD_Canvas_Account extends OCAD_Canvas_Model
{
	public $id;
	public $name;
	public $rootAccountId;
	public $parentAccountId;
	public $sisAccountId;
	
	public static function load($init)
	{
		$account = new OCAD_Canvas_Account();
		
		if(is_array($init))
		{
			// $init is an array of data
			$account->_loadFromData($init);
		}
		elseif( is_int($init) )
		{
			// $init is an account ID
			$client = self::_getClient();
			$client->setMethod(Zend_Http_Client::GET);
			if(is_int($init))
			{
				$client->setEndpoint("accounts/$init");
			}
			$response = $client->request();
			$account->_loadFromData($response);
		}
		else 
		{
			throw new OCAD_Canvas_Exception('Invalid account initialization parameters');
		}
		
		return $account;
	}
	
	/**
	 * Initialize the course params from an array of data
	 * Data will generally be a response from the API
	 * @param array $data
	 */
	protected function _loadFromData($data)
	{
		$this->id 		= $data['id'];
		$this->name		= $data['name'];
		$this->rootAccountId 	= $data['root_account_id'];
		$this->parentAccountId	= $data['parent_account_id'];
		$this->sisAccountId 	= (array_key_exists('sis_account_id', $data)) ? $data['sis_account_id'] : null;
	}

	/**
	 * Returns this account's parent account, or null
	 * @return OCAD_Canvas_Account | null
	 */
	public function getParent()
	{
		return self::load($this->parentAccountId);
	}
	
	/**
	 * Returns this account's root account, or null
	 * @return OCAD_Canvas_Account | null
	 */
	public function getRoot()
	{
		return self::load($this->rootAccountId);
	}

	public function getCourses($states = array(), $limit = 0)
	{
		if(!is_array($states))
		{
			$states = array($states);
		}
		
		$client = self::_getClient();
		$client->setMethod(Zend_Http_Client::GET);
		$client->setEndpoint("accounts/{$this->id}/courses");
		$client->setParameterGet('per_page',50);
		foreach($states as $state)
		{
			$client->setParameterGet('state[]', $state);
		}
		
		$courses = array();
		$count = 0;
		$next = true;
		while ($next)
		{
			$response = $client->request();
			foreach($response as $courseData)
			{
				$courses[] = OCAD_Canvas_Course::load($courseData);
				if(++$count === $limit)
				{
					break 2;
				}
			}
			
			$links = $this->_parsePagination($client->getLastResponse());
			$next = array_key_exists('next', $links);
			if($next)
			{
				$client->setEndpoint($links['next']);
			}
		}
		
		return $courses;
	}
}
