<?php
namespace application\control\view;
use system\core\view;
use application\helper\erpSender;
use application\helper\erp\oms;
use system\core\image;
use application\helper\jpush;
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
	
	function code()
	{
		$image = new image();
		$image->code();
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
		$jpush = new jpush('240594b6ccdf89fe91209e6b','802960b6f8210bd4c77afed8');
		$jpush->push("1","2");
	}
	
	function import()
	{
		$file = $this->fil->receive($_FILES['file']);
		if (is_file($file))
		{
			
		}
	}
	
}
