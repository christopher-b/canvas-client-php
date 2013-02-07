<?php
class OCAD_Canvas_Client extends Zend_Http_Client
{
	/**
	 * INI config settings
	 * @var array
	 */
	public static $apiConfig;
	
	/**
	 * ID of default account
	 * @var int
	 */
	public $defaultAccount;
	
	public function __construct($uri = null, $config = null)
	{
		if($config == null)
		{
			$config = array();
		}
		
		if(!isset($config['useragent']) || empty($config['useragent']))
		{
			$config['useragent'] = 'OCAD U Canvas API Client';
		}
		
		parent::__construct($uri, $config);
		
		$token = self::$apiConfig['apiKey'];
		$this->setHeaders('Authorization', "Bearer $token");
		$this->defaultAccount = self::$apiConfig['defaultAccount'];
	}
	
	public function setEndpoint($endpoint)
	{
		$this->setUri(self::$apiConfig['url'].'api/v1/'.$endpoint);
		return $this;
	}
	
	public function request($method = null)
	{
		$response = parent::request();
		if($response->isError())
		{
			$e = new OCAD_Canvas_Client_Exception($response->getBody());
			$e->setResponse($response);
			$e->client = $this;
			throw $e;
		}
		
		// Do JSON decoding
		$contentType = $response->getHeader('Content-type');
		if(stristr($contentType, 'application/json'))
		{
			// Decode as array, not stdClass
			return json_decode($response->getBody(), true);
		}
		else 
		{
			$e = new OCAD_Canvas_Exception( 'Invalid Content-type: '.$contentType."\n Body: ".$response->getBody() );
			$e->response = $response;
			throw $e;
		}
		$this->resetParameters();
	}
}
