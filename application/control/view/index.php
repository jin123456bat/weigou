<?php
namespace application\control\view;
use system\core\view;
use application\helper\erpSender;
use application\helper\erp\oms;
class index extends view
{
	function __construct()
	{
		parent::__construct();
	}
	
	function index()
	{
		$this->response->setCode(302);
		if ($this->http->isMobile())
		{
			$this->response->addHeader('Location',$this->http->url('','mobile','index'));
		}
		else
		{
			$this->response->addHeader('Location','http://willg.cn');
		}
	}
	
	function clearCookieAndSession()
	{
		session_destroy();
		setcookie('source_time',NULL,$_SERVER['REQUEST_TIME'] - 3600,NULL,'twillg.com');
		setcookie('source_time',NULL,$_SERVER['REQUEST_TIME'] - 3600,NULL,'www.twillg.com');
		$this->response->setCode(302);
		$this->response->addHeader('Location',$this->http->referer());
	}
	
	function downloadApp()
	{
		$this->response->setCode(302);
		$this->response->addHeader('Location','http://a.app.qq.com/o/simple.jsp?pkgname=com.lianhai.MicroBuy');
	}
	
	function __404()
	{
		return "404";
	}
	
	function test()
	{
		$erpSender = new erpSender();
		
		$barcode = 'XL00223';
		$response = $erpSender->doAction(2, 'QueryGoods',[$barcode]);
		var_dump(($response));
		//var_dump($erpSender->doAction(2, 'AddGoods',[639]));
		
		//var_dump($erpSender->doAction(2, 'QueryPlatform',[222]));
		
		
		/* $orderno = '1607301700203608548';
		$erpSender = new erpSender();
		var_dump($erpSender->doSendOrder($orderno)); */
	}
	
}
