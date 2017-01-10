<?php
namespace application\control\view;

use system\core\view;
use system\core\image;
use application\helper\productSearchEngine;
use application\helper\product;
use application\helper\erpSender;

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
	
	/**
	 * 升级脚本
	 */
	function upgrade()
	{
		$recover = $this->get('recover',false,'intval');
		
		$this->model('order')->transaction();
		try {
			if ($recover)
			{
				$sql = '
					ALTER TABLE  `order` DROP  `address_province` ,
					DROP  `address_city` ,
					DROP  `address_county` ,
					DROP  `address_address` ,
					DROP  `address_name` ,
					DROP  `address_telephone` ,
					DROP  `address_zcode` ,
					DROP  `address_identify` ,
					DROP  `address_ishost` ;
				';
				$this->model('order')->exec($sql);
			}
			
			$sql = 
			'
				ALTER TABLE  `order` ADD  `address_province` varchar(32) NOT NULL,
				add `address_city` varchar(32) not null,
				add `address_county` varchar(32) not null,
				add `address_address` varchar(256) not null,
				add `address_name` varchar(32) not null,
				add `address_telephone` char(11) not null,
				add `address_zcode` char(6) not null,
				add `address_identify` char(18) not null,
				add `address_ishost` tinyint(1) not null;
			';
			$this->model('order')->exec($sql);
			
			if ($recover)
			{
				$sql = '
					ALTER TABLE  `suborder_store` DROP  `address_province` ,
					DROP  `address_city` ,
					DROP  `address_county` ,
					DROP  `address_address` ,
					DROP  `address_name` ,
					DROP  `address_telephone` ,
					DROP  `address_zcode` ,
					DROP  `address_identify` ,
					DROP  `address_ishost` ;
				';
				$this->model('order')->exec($sql);
			}
			
			$sql =
			'
				ALTER TABLE  `suborder_store` ADD  `address_province` varchar(32) NOT NULL,
				add `address_city` varchar(32) not null,
				add `address_county` varchar(32) not null,
				add `address_address` varchar(256) not null,
				add `address_name` varchar(32) not null,
				add `address_telephone` char(11) not null,
				add `address_zcode` char(6) not null,
				add `address_identify` char(18) not null,
				add `address_ishost` tinyint(1) not null;
			';
			$this->model('order')->exec($sql);
			
			//更新订单中的地址信息
			$orders = $this->model('order')->select();
			foreach ($orders as $order)
			{
				$address = $this->model('address')->where('id=?',[$order['address']])->find();
				$this->model('order')->where('orderno=?',[$order['orderno']])
				->limit(1)->update([
					'address_province'=>$this->model('province')->where('id=?',[$address['province']])->scalar('name'),
					'address_city' => $this->model('city')->where('id=?',[$address['city']])->scalar('name'),
					'address_county' => $this->model('county')->where('id=?',[$address['county']])->scalar('name'),
					'address_address' => $address['address'],
					'address_name' => $address['name'],
					'address_telephone' => $address['telephone'],
					'address_zcode' => $address['zcode'],
					'address_identify' => $address['identify'],
					'address_ishost' => $address['host'],
				]);
			}
			
			
			//更新子订单中的地址信息
			$orders = $this->model('suborder_store')->select();
			foreach ($orders as $order)
			{
				$address = $this->model('address')->where('id=?',[$order['address']])->find();
				$this->model('suborder_store')->where('id=?',[$order['id']])
				->limit(1)->update([
					'address_province'=>$this->model('province')->where('id=?',[$address['province']])->scalar('name'),
					'address_city' => $this->model('city')->where('id=?',[$address['city']])->scalar('name'),
					'address_county' => $this->model('county')->where('id=?',[$address['county']])->scalar('name'),
					'address_address' => $address['address'],
					'address_name' => $address['name'],
					'address_telephone' => $address['telephone'],
					'address_zcode' => $address['zcode'],
					'address_identify' => $address['identify'],
					'address_ishost' => $address['host'],
				]);
			}
			
			if ($recover)
			{
				$sql = 
				'
					ALTER TABLE `order_product`
					  DROP `sku`,
					  DROP `barcode`;
				';
				$this->model('order_product')->exec($sql);
			}
			
			//商品信息中添加sku和barcode
			$sql = 
			'
				ALTER TABLE  `order_product` ADD  `sku` VARCHAR( 32 ) NOT NULL ,
				ADD  `barcode` VARCHAR( 32 ) NOT NULL ;
			';
			$this->model('order_product')->exec($sql);
			$product = $this->model('order_product')->select();
			foreach ($product as $p)
			{
				$sku_barcode = $this->model('product')->where('id=?',[$p['pid']])->find('sku,barcode');
				$this->model('order_product')->where('id=?',[$p['id']])->update(array(
					'sku' => $sku_barcode['sku'],
					'barcode' => $sku_barcode['barcode'],
				));
			}
		}
		catch (\Exception $e)
		{
			$this->model('order')->rollback();
			var_dump($e);
			exit('升级失败');
		}
		$this->model('order')->commit();
	}
	
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
