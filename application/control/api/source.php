<?php
namespace application\control\api;
use application\message\json;
use application\helper\user;
class source extends common
{
	private $_response;
	
	function __construct()
	{
		parent::__construct();
		$this->_response = $this->init();
	}
	
	/**
	 * 购买vip生成订单
	 */
	function order()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		$userHelper = new user();
		$uid = $userHelper->isLogin();
		if (empty($uid))
			return new json(json::NOT_LOGIN);
		

	}
}