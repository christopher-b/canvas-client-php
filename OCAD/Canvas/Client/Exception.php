<?php
class OCAD_Canvas_Client_Exception extends OCAD_Canvas_Exception
{
	const STATUS_NOT_FOUND		= 'not_found';
	const STATUS_UNAUTHORIZED 	= 'unauthorized';
	
	/**
	 * @var Zend_Http_Response
	 */
	protected $_response;
	protected $_status;
	protected $_errorReportId;
	
	public function __construct($message = '', $code = 0, Exception $previous = null)
	{
		$responseData = json_decode($message);
		if($responseData === null)
		{
			// Could not decode json, or json contained only null
			$message = 'Error during Canvas API request';
		}
		else 
		{
			(isset($responseData->message)) && $message = $responseData->message;
			(isset($responseData->message)) && $this->_status = $responseData->status;
			(isset($responseData->error_report_id)) && $this->_errorReportId = $responseData->error_report_id;
		}
		
		parent::__construct($message, $code, $previous);
	}
	
	public function setResponse(Zend_Http_Response $response)
	{
		$this->_response = $response;
		$this->code = $response->getStatus();
		$this->message .= " ({$this->code} {$response->getMessage()})";
	}
	
	public function getResponse()
	{
		return $this->_response;
	}
	
	public function getStatus()
	{
		return $this->_status;
	}
	
	public function getErrorReportId()
	{
		return $this->_errorReportId;
	}
}
