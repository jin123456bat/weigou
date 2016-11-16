<?php
namespace application\control\view;

use system\core\view;
use system\core\image;
use application\helper\productSearchEngine;

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
			$this->response->addHeader('Location', $this->http->url('', 'mobile', 'index'));
		}
		else
		{
			$this->response->addHeader('Location', 'http://willg.cn');
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
		setcookie('source_time', NULL, $_SERVER['REQUEST_TIME'] - 3600, NULL, 'twillg.com');
		setcookie('source_time', NULL, $_SERVER['REQUEST_TIME'] - 3600, NULL, 'www.twillg.com');
		$this->response->setCode(302);
		$this->response->addHeader('Location', $this->http->referer());
	}

	function downloadApp()
	{
		$this->response->setCode(302);
		$this->response->addHeader('Location', 'http://a.app.qq.com/o/simple.jsp?pkgname=com.lianhai.MicroBuy');
	}

	function __404()
	{
		return "404";
	}
		
	/*
	 * function upgrade()
	 * {
	 * if (file_exists('./upgrade') && file_get_contents('./upgrade')=='1')
	 * {
	 * exit('已经升级过了');
	 * }
	 * file_put_contents('./upgrade', '1');
	 * $this->model('product')->transaction();
	 * $products = $this->model('product')->where('selled>?',[1])->select();
	 * foreach ($products as $product)
	 * {
	 * if ($product['selled']!=1 && $product['oldprice']!=0 || $product['inprice']!=0 || $product['price']!=0 || $product['v1price']!=0 || $product['v2price']!=0)
	 * {
	 * if(!$this->model('product')->where('id=?',[$product['id']])->limit(1)->update([
	 * 'oldprice' => $product['oldprice']/$product['selled'],//更改oldprice
	 * 'price' => $product['price']/$product['selled'],//更改v0价格
	 * 'v1price' => $product['v1price']/$product['selled'],//v1价格
	 * 'v2price' => $product['v2price']/$product['selled'],//v2价格
	 * 'inprice' => $product['inprice']/$product['selled'],//进价
	 * ]))
	 * {
	 * $this->model('product')->rollback();
	 * unlink('./upgrade');
	 * var_dump($product);
	 * exit('错误1');
	 * }
	 * }
	 *
	 * //更改colleciton
	 * $collections = $this->model('collection')->where('pid=?',[$product['id']])->select();
	 * foreach ($collections as $collection)
	 * {
	 * if ($product['selled']!=1 && $collection['price']!=0 || $collection['v1price']!=0 || $collection['v2price']!=0)
	 * {
	 * if(!$this->model('collection')->where('pid=? and content=?',[$collection['pid'],$collection['content']])->limit(1)->update([
	 * 'price' => $collection['price']/$product['selled'],//更改v0价格
	 * 'v1price' => $collection['v1price']/$product['selled'],//v1价格
	 * 'v2price' => $collection['v2price']/$product['selled'],//v2价格
	 * ]))
	 * {
	 * $this->model('product')->rollback();
	 * unlink('./upgrade');
	 * exit('错误2');
	 * }
	 * }
	 * }
	 * }
	 * $this->model('product')->commit();
	 * unlink('./upgrade');
	 * echo "升级完成";
	 * }
	 */
	/*
	 * function cartdown(){
	 * $product=$this->model("order_product")->where("bind>1")->select();
	 * $this->model('product')->transaction();
	 * foreach($product as $p){
	 * $price=$p['price']/$p['bind'];
	 * if(!$this->model("order_product")->where("id=?",[$p['id']])->update(["price"=>$price])){
	 * $this->model('product')->rollback();
	 *
	 * exit('错误2');
	 * }
	 * }
	 * $this->model('product')->commit();
	 * exit('ok');
	 *
	 * }
	 */
	/*
	 * function sendupdate()
	 * {
	 *
	 *
	 * $uid = $this->model('system')->get('uid', 'sms');
	 * $key = $this->model('system')->get('key', 'sms');
	 * $sign = $this->model('system')->get('sign', 'sms');
	 * $template = '淘微购1.0.9正式上线，本次调整涉及购物方式变更，为不影响您的购物体验，还请及时进行版本更新。退订回复TD';
	 *
	 * $sms = new sms($uid, $key, $sign);
	 * $ucount = $this->model("user")->where("send=0")->find(['count(1)']);
	 * $ucount=$ucount['count(1)'];
	 *
	 * $j= ceil($ucount/100);
	 * for($i=0;$i<$j;$i++) {
	 * $user = $this->model("user")->where("send=0")->limit($i*100, 100)->select();
	 * $uw = '';
	 * foreach ($user as $u) {
	 * $uw[] = $u['telephone'];
	 * }
	 * $uw = implode(',', $uw);
	 * echo $uw . "<br />";
	 *
	 *
	 * //循环发送
	 *
	 * $num = $sms->send($uw, $template);
	 * if ($num > 0) {
	 * foreach ($user as $u) {
	 * $this->model("user")->where("telephone=?", [$u['telephone']])->update(["send" => '1']);
	 * echo $u['telephone'] . "发送成功<br />";
	 * }
	 * continue;
	 *
	 * } else {
	 * switch ($num) {
	 * case '-1':
	 * return new json(json::PARAMETER_ERROR, '没有该用户账户');
	 * case '-2':
	 * return new json(json::PARAMETER_ERROR, '接口密钥不正确');
	 * case '-21':
	 * return new json(json::PARAMETER_ERROR, 'MD5接口密钥加密不正确');
	 * case '-11':
	 * return new json(json::PARAMETER_ERROR, '该用户被禁用');
	 * case '-14':
	 * return new json(json::PARAMETER_ERROR, '短信内容出现非法字符');
	 * case '-41':
	 * return new json(json::PARAMETER_ERROR, '手机号码为空');
	 * case '-42':
	 * return new json(json::PARAMETER_ERROR, '短信内容为空');
	 * case '-51':
	 * return new json(json::PARAMETER_ERROR, '短信签名格式不正确');
	 * case '-6':
	 * return new json(json::PARAMETER_ERROR, 'IP限制');
	 * }
	 * }
	 *
	 * }
	 * }
	 */
	
	function orderoff()
	{
		
		$orders = $this->model("order_package")
			->table('`order`', 'left join', 'order_package.orderno=`order`.orderno')
			->where("`order`.status=1 and `order`.pay_status=0 and  unix_timestamp(now())-`order`.createtime>=3600 and (select task_user.orderno from task_user where task_user.orderno=`order`.orderno) is null")
			->
		// ->where("`order`.status=1 and `order`.pay_status=0 and unix_timestamp(now())-`order`.createtime>=3600 ")
		// ->where("`order`.orderno=?", ['1606132140142742120'])
		select([
			'order_package.id',
			'order_package.orderno'
		]);
		if ($orders)
		{
			foreach ($orders as $o)
			{
				// 取商品数据 判断是否有content 不存在直接加库存 存在加另一个库存
				$order_pro1 = $this->model("order_product")
					->where("package_id=?", [
					$o['id']
				])
					->select();
				
				foreach ($order_pro1 as $order_pro)
				{
					$num = $order_pro['num'] * $order_pro['bind'];
					
					if ($order_pro['content'] != '')
					{
						$stock = $this->model("collection")
							->where("pid=? and content=?", [
							$order_pro['pid'],
							$order_pro['content']
						])
							->find('stock');
						$stock = $stock['stock'] + $num;
						$this->model("collection")
							->where("pid=? and content=?", [
							$order_pro['pid'],
							$order_pro['content']
						])
							->update([
							'stock' => $stock
						]);
					}
					else
					{
						$stock = $this->model("product")
							->where("id=?", [
							$order_pro['pid']
						])
							->find([
							'stock'
						]);
						$stock = $stock['stock'] + $num;
						$this->model("product")
							->where("id=?", [
							$order_pro['pid']
						])
							->update([
							'stock' => $stock
						]);
					}
				}
				
				if ($this->model("order")
					->where("orderno=?", [
					$o['orderno']
				])
					->update([
					"status" => 0,
					'quittime' => time()
				]))
				{
					echo $o['orderno'] . "关闭成功<br />";
				}
				else
				{
					echo $o['orderno'] . "关闭失败<br />";
					foreach ($order_pro1 as $order_pro)
					{
						$num = $order_pro['num'] * $order_pro['bind'];
						
						if ($order_pro['content'] != '')
						{
							$stock = $this->model("collection")
								->where("pid=? and content=?", [
								$order_pro['pid'],
								$order_pro['content']
							])
								->find('stock');
							$stock = $stock['stock'] - $num;
							$this->model("collection")
								->where("pid=? and content=?", [
								$order_pro['pid'],
								$order_pro['content']
							])
								->update([
								'stock' => $stock
							]);
						}
						else
						{
							$stock = $this->model("product")
								->where("id=?", [
								$order_pro['pid']
							])
								->find([
								'stock'
							]);
							$stock = $stock['stock'] - $num;
							$this->model("product")
								->where("id=?", [
								$order_pro['pid']
							])
								->update([
								'stock' => $stock
							]);
						}
					}
				}
			}
		}
		else
		{
			echo '无记录';
		}
	}

	function uporder()
	{
		
		$order = $this->model("order_product")->select();
		foreach ($order as $o)
		{
			// 获取商品的进价 商品名，店铺名，供应商名
			$product = $this->model("product")
				->table("store", "left join", "store.id=product.store")
				->table("publish", "left join", "publish.id=product.publish")
				->where('product.id=?', [
				$o['pid']
			])
				->find([
				'product.name',
				'store.name as storename',
				'publish.name as publish',
				"product.inprice"
			]);
			if ($this->model("order_product")
				->where("id=?", [
				$o['id']
			])
				->update([
				'name' => $product['name'],
				'store_name' => $product['storename'],
				'publish' => $product['publish'],
				'inprice' => $product['inprice']
			]
			))
			{
                echo $o['pid']."成功<br />";
            }else{
                echo $o['pid'] . "失败<br />";
            }

        }

    }
}
