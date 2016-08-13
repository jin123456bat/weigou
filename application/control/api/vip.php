<?php
namespace application\control\api;
use application\message\json;
use application\helper\user;
class vip extends common
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
		
		$type = $this->data('type');
		switch($type)
		{
			case '0-1':
				$payprice = 200;
				break;
			case '1-2':
				$payprice = 600;
				break;
			case '0-2':
				$payprice = 800;
				break;
			default:
				return new json(json::PARAMETER_ERROR,'会员类型错误');
		}
		
		list($from,$to) = explode('-', $type);
		
		$vipHelper = new \application\helper\vip();
		$vip_order = $vipHelper->createOrderData($uid ,$payprice, $from, $to);
		
		if($this->model('vip_order')->insert($vip_order))
		{
			$vip_order['id'] = $this->model('vip_order')->lastInsertId();
			return new json(json::OK,NULL,$vip_order);
		}
		return new json(json::PARAMETER_ERROR);
	}
}