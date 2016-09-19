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
	
	/* function upgrade()
	{
		if (file_exists('./upgrade') && file_get_contents('./upgrade')=='1')
		{
			exit('已经升级过了');
		}
		file_put_contents('./upgrade', '1');
		$this->model('product')->transaction();
		$products = $this->model('product')->where('selled>?',[1])->select();
		foreach ($products as $product)
		{
			if ($product['selled']!=1 && $product['oldprice']!=0 || $product['inprice']!=0 || $product['price']!=0 || $product['v1price']!=0 || $product['v2price']!=0)
			{
				if(!$this->model('product')->where('id=?',[$product['id']])->limit(1)->update([
					'oldprice' => $product['oldprice']/$product['selled'],//更改oldprice
					'price' => $product['price']/$product['selled'],//更改v0价格
					'v1price' => $product['v1price']/$product['selled'],//v1价格
					'v2price' => $product['v2price']/$product['selled'],//v2价格
					'inprice' => $product['inprice']/$product['selled'],//进价
				]))
				{
					$this->model('product')->rollback();
					unlink('./upgrade');
					var_dump($product);
					exit('错误1');
				}
			}
			
			//更改colleciton
			$collections = $this->model('collection')->where('pid=?',[$product['id']])->select();
			foreach ($collections as $collection)
			{
				if ($product['selled']!=1 && $collection['price']!=0 || $collection['v1price']!=0 || $collection['v2price']!=0)
				{
					if(!$this->model('collection')->where('pid=? and content=?',[$collection['pid'],$collection['content']])->limit(1)->update([
						'price' => $collection['price']/$product['selled'],//更改v0价格
						'v1price' => $collection['v1price']/$product['selled'],//v1价格
						'v2price' => $collection['v2price']/$product['selled'],//v2价格
					]))
					{
						$this->model('product')->rollback();
						unlink('./upgrade');
						exit('错误2');
					}
				}
			}
		}
		$this->model('product')->commit();
		unlink('./upgrade');
		echo "升级完成";
	} */
	
}
