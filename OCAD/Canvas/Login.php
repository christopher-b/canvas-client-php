<?php
class OCAD_Canvas_Login extends OCAD_Canvas_Model
{
	public $id;
	public $uniqueId;
	public $sisUserId;
	public $accountId;
	public $userId;
    
	public static function load($init)
	{
		$login = new OCAD_Canvas_Login();
		if(is_array($init))
		{
			// $init is an array of data
			$login->id 	  = $init['id'];
			$login->uniqueId  = $init['unique_id'];
			$login->sisUserId = $init['sis_user_id'];
			$login->accountId = $init['account_id'];
			$login->userId 	  = $init['user_id'];
		}
		elseif(is_int($init))
		{
			// $init is a canvas login id. Load data from the API
			// @TODO
		}
		
		return $login;
	}

	
	public function save()
	{
		// Get client
		$client = self::_getClient();
		$client->setMethod(Zend_Http_Client::PUT);
		$client->setEndpoint('/accounts/'.$client->defaultAccount.'/logins/'.$this->id);
		$client->setParameterPost(
			array(
				'login[unique_id]'   => $this->uniqueId,
				'login[sis_user_id]' => $this->sisUserId,
			)
		);
		
		// Make the request to create the user
		$response = $client->request();
	}
}
