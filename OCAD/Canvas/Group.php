<?php
class OCAD_Canvas_Group extends OCAD_Canvas_Model
{
	const JOIN_LEVEL_AUTO 		= 'parent_context_auto_join';
	const JOIN_LEVEL_REQUEST 	= 'parent_context_request';
	const JOIN_LEVEL_INVITE 	= 'invitation_only';
	
	public $id;
	public $name;
	public $description;
	public $is_public; 
	public $join_level;
	
	//public $account_id] => 1
	//public $followed_by_user] => 1
	//public $group_category_id] => 240
 	//public $role] => communities
	//public $members_count] => 1
	//public $context_type] => Account
	//public $avatar_url] => 
	
	public static function create($name, $description='', $is_public=false, $join_level=null)
	{
		($join_level === null) && $join_level = self::JOIN_LEVEL_INVITE;
		$is_public = $is_public ? 'true' : 'false';
		
		$client = self::_getClient();
		$client->setEndpoint('groups')
			   ->setParameterPost('name', $name)
			   ->setParameterPost('description', $description)
			   ->setParameterPost('is_public', $is_public)
			   ->setParameterPost('join_level', $join_level)
			   ->setMethod(Zend_Http_Client::POST);
		$response = $client->request();
		return OCAD_Canvas_Group::load($response);
	}
	
	public static function load($init)
	{
		$group = new OCAD_Canvas_Group();
		if(is_int($init))
		{
			$data = self::_getClient()
						 ->setEndpoint("groups/$init")
						 ->request();
		}
		elseif(is_array($init))
		{
			$data = $init;
		}
		$group->_loadFromData($data);
		return $group;
	}
	
	public function save()
	{
		$response = $this->_getClient()
						 ->setEndpoint("groups/{$this->id}")
						 ->setMethod(Zend_Http_Client::PUT)
						 ->setParameterPost('name', $this->name)
						 ->setParameterPost('description', $this->description)
						 ->setParameterPost('is_public', ($this->is_public) ? 'true':'false')
						 ->setParameterPost('join_level', $this->join_level)
						 //->setParameterPost('avatar_id', $this->avatar_id)
						 ->request();
	}
	
	protected function _loadFromData($data)
	{
		$this->id 			= $data['id'];
		$this->name 		= $data['name'];
		$this->description 	= $data['description'];
		$this->is_public 	= $data['is_public'];
		$this->join_level 	= $data['join_level'];
	}
	
	public function getMembers()
	{
		$response = $this->_getClient()
						 ->setEndpoint("groups/{$this->id}/memberships")
						 ->request();
		$users = array();
		foreach($response as $membership)
		{
			$users[] = OCAD_Canvas_User::load($membership['user_id']);
		}
		return $users;
	}

	/* Broken
	public function addMember(OCAD_Canvas_User $user, $moderator=false)
	{
		$client = $this->_getClient();
		$response = $client->setEndpoint("groups/{$this->id}/memberships")
						  ->setMethod(Zend_Http_Client::POST)
						  ->setParameterPost('user_id', $user->id)
						  ->request();
		
	}
	*/
}
