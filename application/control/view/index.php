<?php
namespace application\control\view;

use system\core\view;
use system\core\image;
use application\helper\product;
use application\helper\user;

class index extends view
{
	function __construct()
	{
		$this->_csrf_token_refresh = false;
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
	
	function product()
	{
		$userHelper = new \application\helper\user();
		if($userHelper->isLogin())
		{
			$user = $this->model('user')->where('id=?',[$userHelper->isLogin()])->find();
			$this->assign('user', $user);
		}
		
		$id = $this->get('id',0,'intval');
		$product = $this->model('product')->where('id=?',[$id])->find();
		$productHelper = new \application\helper\product();
		$product['origin'] = $this->model('country')->get($product['origin']);
		$product['store'] = $this->model('store')->where('id=?',[$product['store']])->find();
		// 商品详情图
		$product['listImage'] = $productHelper->getListImage($id);
		$product['detailImage'] = $productHelper->getDetailImage($id);
		$product['tax'] = $productHelper->getTaxFields($id);
		$product['MeasurementUnit'] = $this->model('dictionary')->where('id=? and type=?',[$product['MeasurementUnit'],'MeasurementUnit'])->scalar('name');
		$product['package'] = $this->model('dictionary')->where('id=? and type=?',[$product['package'],'package'])->scalar('name');
		$this->assign('product', $product);
		
		$prototype = $this->model('prototype')->where('pid=? and type=? and isdelete=?',[$id,'text',0])->select();
		$this->assign('prototype', $prototype);
		
		$bind = $this->model('bind')->orderby('sort','asc')->where('pid=?',[$id])->select();
		$this->assign('bind', $bind);
		
		$this->assign('province', $this->model('province')->select());
		return $this;
	}
	
	/**
	 * 移动端地址二维码
	 */
	function mobileAddressEqcode()
	{
		$content = 'http://twillg.com/index.php?c=mobile&a=index';
		
		$image = new image();
		$size = empty($this->get->size) ? 4 : intval($this->get->size);
		$blank = empty($this->get->blank) ? 2 : intval($this->get->blank);
		
		//$logo = $this->model('system')->get('logo', 'system');
		//$logo = is_file($logo) ? $logo : NULL;
		$logo = ROOT.'/logo_small.jpg';
		
		$file = $image->QRCode($content, $logo, 'M', $size, $blank);
		$this->response->addHeader('Content-Type', 'image/png');
		if ($this->get->download == 'true') {
			$this->response->addHeader('Content-Disposition', 'attachment; filename="eqcode.png"');
		}
		return file_get_contents($file);
	}

	/**
	 * 图形验证码
	 */
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
	 * 修复订单表中erp字段错误的脚本
	 */
	function erpError()
	{
		$orders = $this->model('order')->select();
		
		foreach ($orders as $order)
		{
			$sending_num = $this->model('suborder_store')->where('main_orderno=? and erp=?',[$order['orderno'],1])->count();
			$not_sending_num = $this->model('suborder_store')->where('main_orderno=? and erp=?',[$order['orderno'],0])->count();
			if ($sending_num>0 && $not_sending_num==0)
			{
				$this->model('order')->where('orderno=?',[$order['orderno']])->limit(1)->update([
					'erp'=>1
				]);
			}
			if ($sending_num==0 && $not_sending_num>0)
			{
				$this->model('order')->where('orderno=?',[$order['orderno']])->limit(1)->update([
					'erp'=>0
				]);
			}
			if ($sending_num>0 && $not_sending_num>0)
			{
				$this->model('order')->where('orderno=?',[$order['orderno']])->limit(1)->update([
					'erp'=>2
				]);
			}
		}
	}
	
	/**
	 * 升级脚本
	 */
	function upgrade()
	{
		$this->model('order')->transaction();
		try {
			
			$sql = '
				ALTER TABLE `order`
				DROP `personal`,
				DROP `personal_time`,
				DROP `ordered`,
				DROP `ordered_time`,
				DROP `payed`,
				DROP `payed_time`,
				DROP `kouan`,
				DROP `kouan_time`,
				DROP `kouan_result`;
			';
			$this->model('order')->exec($sql);
		
			$sql = 'ALTER TABLE `product`
			  DROP `categoryCode`,
			  DROP `grossWeight`,
			  DROP `goodsItemNo`,
			  DROP `goodsModel`,
			  DROP `currencyType`,
			  DROP `purpose`,
			  DROP `firstUnit`,
			  DROP `productRecordNo`;';
			$this->model('product')->exec($sql);
			$sql = 'ALTER TABLE `product` ADD `weight` DOUBLE(10,2) NOT NULL COMMENT \'重量，单位KG\' ;';
			$this->model('product')->exec($sql);
			
			//admin表中删除role的外键
			$sql = 'ALTER TABLE `admin` DROP FOREIGN KEY `admin_ibfk_1`;';
			$sql = 'ALTER TABLE `admin` DROP `role`;';
			$sql = 'ALTER TABLE `admin` ADD `realname` VARCHAR(12) NOT NULL COMMENT \'姓名\' , ADD `telephone` CHAR(11) NOT NULL COMMENT \'手机号\' , ADD `create_aid` INT NOT NULL COMMENT \'创建人id\' , ADD `create_time` INT NOT NULL COMMENT \'创建时间\' , ADD `status` BOOLEAN NOT NULL DEFAULT \'1\' COMMENT \'状态，1有效，0无效\' ;';
			$sql = 'DROP TABLE role';
			$sql = 'CREATE TABLE IF NOT EXISTS `role` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `name` varchar(32) NOT NULL COMMENT \'角色名称\',
					  `description` varchar(256) NOT NULL COMMENT \'角色描述\',
					  PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
			$sql = 'CREATE TABLE IF NOT EXISTS `admin_role` (
					  `aid` int(11) NOT NULL,
					  `rid` int(11) NOT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
			
			
			
			$sql = 'ALTER TABLE `product` DROP `package`;';
			$this->model('product')->exec($sql);
			
			$sql = 'ALTER TABLE `order` ADD `erp_note` VARCHAR(256) NOT NULL COMMENT \'erp备注信息\' AFTER `erp_time`;';
			$this->model('order')->exec($sql);
			
			$sql = 'ALTER TABLE `order` ADD `refund_reason` VARCHAR(256) NOT NULL COMMENT \'退款原因\' AFTER `refundtime`, ADD `refund_note` VARCHAR(256) NOT NULL COMMENT \'退款备注\' AFTER `refund_reason`;';
			$this->model('order')->exec($sql);
			
			$sql = 'ALTER TABLE `order_package` ADD `ship_note` VARCHAR(256) NOT NULL COMMENT \'发货备注\' ;';
			$this->model('order_package')->exec($sql);
			
			$sql = 'ALTER TABLE `order_log` ADD `aid` INT NOT NULL COMMENT \'管理员id\' , ADD `note` VARCHAR(256) NOT NULL COMMENT \'相关备注抄送\' , ADD `status` VARCHAR(24) NOT NULL COMMENT \'操作前状态\' ;';
			$this->model('order')->exec($sql);
			
			$sql = 'ALTER TABLE `order` ADD `msg_to_user` VARCHAR(256) NOT NULL COMMENT \'给用户的留言\' ;';
			$this->model('order')->exec($sql);
			
			$sql = 'ALTER TABLE `order_log` CHANGE `aid` `aid` INT(11) NULL DEFAULT NULL COMMENT \'管理员id\';';
			$this->model('order')->exec($sql);
			
			//删除category的alias
			$sql = 'ALTER TABLE `category` drop `alias`';
			$this->model('category')->exec($sql);
			
			//task的活动开始时间和活动结束时间
			$sql = 'ALTER TABLE `task` ADD `starttime` DATE NULL DEFAULT NULL COMMENT \'活动开始时间\' , ADD `endtime` DATE NULL DEFAULT NULL COMMENT \'活动结束时间\' ;';
			$this->model('task')->exec($sql);
			
			$sql = 'ALTER TABLE `product` ADD `examine_description` VARCHAR(512) NOT NULL COMMENT \'审核拒绝的详细描述\' AFTER `examine_result`, ADD `examine_time` INT NOT NULL COMMENT \'审核状态变化时间\' AFTER `examine_description`;';
			$this->model('product')->exec($sql);
			
			$sql = 'ALTER TABLE `product` CHANGE `brand` `brand` INT(11) NULL DEFAULT NULL COMMENT \'品牌\';';
			$this->model('product')->exec($sql);
		}
		catch (\Exception $e)
		{
			var_dump("升级失败");
			var_dump($e);
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
