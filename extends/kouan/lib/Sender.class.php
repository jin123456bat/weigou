<?php
class Sender
{
	private $_debug = false;
	
	private $_url = 'http://122.224.69.179:8080/newyorkWS/ws/ReceivedDeclare?wsdl';
	
	private $_test_url = 'http://122.224.230.4:18003/newyorkWS/ws/ReceivedDeclare?wsdl';
	
	private $_soap;
	
	function __construct()
	{
		$this->_soap = new SoapClient($this->getUrl());
	}
	
	public function getUrl()
	{
		if ($this->_debug)
			return $this->_test_url;
		return $this->_url;
	}
	
	/**
	 * 发送商品订单数据到通关平台
	 */
	function business(Business $business)
	{
		$data = new stdClass();
		$data->xmlStr = $business->getData();
		$data->xmlType = $business->getType();
		$data->sourceType = '1';
		
		$result = $this->_soap->checkReceived($data);
		return $result->return;
	}
}