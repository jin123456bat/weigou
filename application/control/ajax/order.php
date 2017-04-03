<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;
use application\helper\erpSender;
use application\helper\pay;
use application\helper\admin;
use application\model\roleModel;

class order extends ajax
{

	/**
	 * 给订单添加备注信息，这个备注信息是给管理员看的
	 * @return \application\message\json
	 */
	function note()
	{
		$adminHelper = new \application\helper\admin();
		$admin = $adminHelper->getAdminId();
		if (empty($admin))
		{
			return new json(json::NOT_LOGIN);
		}
		
		$orderno = $this->post('orderno');
		if (! empty($orderno))
		{
			$note = $this->post('note');
			if ($note !== NULL)
			{
				$this->model('order')
					->where('orderno=?', [
					$orderno
				])
					->limit(1)
					->update('note', $note);
				$this->model("admin_log")->insertlog($admin, '订单备注成功，订单号：' . $orderno . "内容：" . $note, 1);
				return new json(json::OK, NULL, $note);
			}
			else
			{
				$note = $this->model('order')
					->where('orderno=?', [
					$orderno
				])->find('note');
				
				return new json(json::OK, NULL, $note);
			}
		}
	}

	/**
	 * 订单删除功能
	 */
	function delete()
	{
		$orderno = $this->post('orderno');
		
		$userHelper = new user();
		$uid = $userHelper->isLogin();
		if (empty($uid))
		{
			return new json(json::NOT_LOGIN);
		}
		
		if (empty($orderno))
		{
			return new json(json::PARAMETER_ERROR, '订单编号不能为空');
		}
		
		$orderHelper = new \application\helper\order();
		$order = $this->model('order')->where('orderno=?',[$orderno])->find();
		if (empty($order))
		{
			return new json(json::PARAMETER_ERROR);
		}
		
		if ($this->model('order')
			->where('orderno=?', [
			$orderno
		])
			->limit(1)
			->update([
			'isdelete' => 1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
			$this->model('order_log')->add($orderno,'订单删除',NULL,$orderHelper->convertStatus($order));
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR, '不要重复删除订单嘛');
	}

	/**
	 * 创建订单
	 * 
	 * @return \application\message\json
	 */
	function create()
	{
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		if (empty($uid))
			return new json(json::NOT_LOGIN);
			
			// 商品信息
		$product = $this->post('product', '');
		$product = json_decode($product, true);
		if (empty($product))
		{
			return new json(json::PARAMETER_ERROR, '请选择要购买的商品');
		}
		if (! is_array($product))
		{
			return new json(json::PARAMETER_ERROR, 'product参数错误');
		}
		
		// 是否是模拟订单
		$prepay = $this->post('prepay', 0, 'intval');
		
		// 优惠券
		$coupon = $this->post('coupon', '');
		
		// 收货地址
		$address = $this->post('address', '', 'intval');
		if (empty($address) && ! $prepay)
			return new json(json::PARAMETER_ERROR, '请选择收货地址');
			
			// 用户留言
		$msg = $this->post('msg', '');
		
		// 发票抬头
		$invoice = $this->post('invoice', '');
		
		// 使用余额
		$money = $this->post('money', 0, 'floatval');
		if ($money < 0)
			$money = 0;
		
		$orderHelper = new \application\helper\order();
		
		$productHelper = new \application\helper\product();
		foreach ($product as $p)
		{
			if (! is_array($p))
				return new json(json::PARAMETER_ERROR, 'product参数错误');
			if (! isset($p['id']) || ! isset($p['content']) || ! isset($p['num']))
				return new json(json::PARAMETER_ERROR, 'product参数错误');
			if (! $productHelper->canBuy($p['id'], $p['content']))
				return new json(json::PARAMETER_ERROR, '存在不可购买的商品,请删除重新下单');
		}
		
		$order = $orderHelper->createOrderData($uid, $product, $coupon, $address, $money, $msg, $invoice);
		$package = $orderHelper->createPackageData();
		
		if ($prepay)
		{
			// 预订单到这里结束了
			return new json(json::OK, NULL, $order);
		}
		
		// 检查订单中的收货地址是否需要填写身份证号码
		if ($order['need_kouan'] == 1)
		{
			$address = $this->model('address')
				->where('id=?', [
				$address
			])
				->find();
			if (empty($address['identify']))
			{
				return new json(json::PARAMETER_ERROR, '当前选择的收货地址中没有填写身份证号码，请填写身份证号码后在下单');
			}
		}
		
		// 一个订单中不允许多个类型的商品存在
		if ($orderHelper->hasProductOutside(0) || $orderHelper->hasProductOutside(1))
		{
			if ($orderHelper->hasProductOutside(2))
			{
				return new json(json::PARAMETER_ERROR, '普通商品和进口商品不能和直供商品同时支付，请选择部分商品支付');
			}
			if ($orderHelper->hasProductOutside(3))
			{
				return new json(json::PARAMETER_ERROR, '普通商品和进口商品不能和直邮商品同时支付，请选择部分商品支付');
			}
		}
		
		if ($orderHelper->hasProductOutside(2))
		{
			if ($orderHelper->hasProductOutside(3))
			{
				return new json(json::PARAMETER_ERROR, '直供商品不能和直邮商品同时支付，请选择部分商品支付');
			}
		}
		
		// 订单价格不允许为0
		if (floatval($order['orderamount']) == 0)
		{
			return new json(json::PARAMETER_ERROR, '订单创建失败');
		}
		
		$this->model('order')->transaction();
		
		if ($this->model('order')->insert($order))
		{
			// 减少库存
			foreach ($product as $p)
			{
				
				$selled = $productHelper->getSelled($p);
				
				if (! $productHelper->increaseStock($p['id'], $p['content'], - $p['num'] * $selled))
				{
					$this->model('order')->rollback();
					return new json(json::PARAMETER_ERROR, '库存不足');
				}
			}
			
			foreach ($package as $p)
			{
				$p['orderno'] = $order['orderno'];
				if (! $this->model('order_package')->insert($p))
				{
					$this->model('order')->rollback();
					return new json(json::PARAMETER_ERROR, '订单包裹错误');
				}
				
				$package_id = $this->model('order_package')->lastInsertId();
				foreach ($p['product'] as $temp_product)
				{
					$temp_product['package_id'] = $package_id;
					$name = $this->model("product")
						->table("store", "left join", "store.id=product.store")
						->table("publish", "left join", "publish.id=product.publish")
						->where('product.id=?', [
						$temp_product['pid']
					])
						->find([
						'product.name',
						'store.name as storename',
						'publish.name as publish',
						'product.selled',
						'product.inprice'
					]);
					$temp_product['name'] = $name['name'];
					// $temp_product['name'] = '123';
					$temp_product['store_name'] = $name['storename'];
					$temp_product['publish'] = $name['publish'];
					if ($name['selled'] == $temp_product['bind'])
					{
						$temp_product['inprice'] = $name['inprice'];
					}
					else
					{
						$bind = $this->model("bind")
							->where("pid=? and num=? and content=?", [
							$temp_product['pid'],
							$temp_product['bind'],
							$temp_product['content']
						])
							->find();
						if ($bind)
						{
							$temp_product['inprice'] = $bind['inprice'];
						}
					}
					
					if (! $this->model('order_product')->insert($temp_product))
					{
						$this->model('order')->rollback();
						return new json(json::PARAMETER_ERROR, '订单商品错误');
					}
				}
			}
			
			if ($order['money'] > 0)
			{
				if (! $this->model('user')
					->where('id=?', [
					$uid
				])
					->limit(1)
					->increase('money', - $order['money']))
				{
					$this->model('order')->rollback();
					return new json(json::PARAMETER_ERROR, '扣除余额失败');
				}
			}
			
			if ($orderHelper->usedCoupon())
			{
				if (! $this->model('coupon')
					->where('id=?', [
					$orderHelper->getCouponId()
				])
					->limit(1)
					->update([
					'used' => 1,
					'usedtime' => $_SERVER['REQUEST_TIME']
				]))
				{
					$this->model('order')->rollback();
					return new json(json::PARAMETER_ERROR, '优惠卷使用错误');
				}
			}
			
			// 是否清空购物车
			$clear = $this->post('clear', 0, 'intval');
			if ($clear)
			{
				foreach ($product as $p)
				{
					$selled = $productHelper->getSelled($p);
					
					if (! $this->model('cart')
						->where('uid=? and pid=? and content=? and bind=?', [
						$uid,
						$p['id'],
						$p['content'],
						$selled
					])
						->increase('num', - $p['num']))
					{
						
						$this->model('order')->rollback();
						return new json(json::PARAMETER_ERROR, '清空购物车失败');
					}
					
					$num = $this->model('cart')
						->where('uid=? and pid=? and content=? and bind=?', [
						$uid,
						$p['id'],
						$p['content'],
						$selled
					])
						->find();
					if ($num['num'] <= 0)
					{
						if (! $this->model('cart')
							->where('uid=? and pid=? and content=? and bind=?', [
							$uid,
							$p['id'],
							$p['content'],
							$selled
						])
							->delete())
						{
							$this->model('order')->rollback();
							return new json(json::PARAMETER_ERROR, '清空购物车失败');
						}
					}
				}
			}
			
			$this->model('order_log')->add($order['orderno'], '订单创建成功，等待支付',NULL,'待支付',$msg);
			
			$this->model('order')->commit();
			
			foreach ($product as $p)
			{
				$stock_limit = 10;
				$stock = $this->model('product')->where('id=?',[$p['id']])->scalar('stock');
				if ($stock<=$stock_limit)
				{
					$productHelper->cutPublish($p['id']);
				}
			}
			
			return new json(json::OK, NULL, $order);
		}
		$this->model('order')->rollback();
		return new json(json::PARAMETER_ERROR, '订单创建失败');
	}

	/**
	 * 取消订单
	 * 
	 * @return \application\message\json
	 */
	function quit()
	{
		$adminHelper = new \application\helper\admin();
		$admin = $adminHelper->getAdminId();
		$orderno = $this->post('orderno');
		if (! empty($orderno))
		{
			if (! empty($this->model('task_user')
				->where('orderno=?', [
				$orderno
			])
				->find()))
			{
				return new json(json::PARAMETER_ERROR, '团购订单无法手动取消');
			}
			
			$orderHelper = new \application\helper\order();
			
			$order = $this->model('order')
			->where('orderno=?', [
				$orderno
			])->find();
			$orderStatus = $orderHelper->convertStatus($order);
			
			$userHelper = new \application\helper\user();
			$uid = $userHelper->isLogin();
			if (empty($uid))
			{
				if (empty($admin))
				{
					return new json(json::NOT_LOGIN);
				}
				else
				{
					if ($order['pay_status'] == 1 || $order['pay_status'] == 4)
					{
						$roleModel = $this->model('role');
						$role = $adminHelper->getGroupId();
						if (! $roleModel->checkPower($role, 'refund', roleModel::POWER_ALL))
						{
							return new json(json::PARAMETER_ERROR, '权限不足');
						}
						
						if (! $orderHelper->refund($orderno))
						{
							return new json(json::PARAMETER_ERROR, '订单退款失败');
						}
					}
				}
			}
			
			$this->model('order')->transaction();
			
			if ($orderHelper->quitOrder($orderno, false))
			{
				$this->model('order')->commit();
				//add($orderno,$content,$aid,$status,$note = '')
				
				if (!empty($admin))
				{
					$this->model("admin_log")->insertlog($admin, '取消订单成功，订单号：' . $orderno, 1);
					$this->model('order_log')->add($orderno,'订单取消',$admin,$orderStatus);
				}
				return new json(json::OK);
			}
			else
			{
				$this->model('order')->rollback();
				return new json(json::PARAMETER_ERROR, '取消失败');
			}
		}
		return new json(json::PARAMETER_ERROR);
	}

	/**
	 * 将订单推送到erp
	 */
	function erp()
	{
		/* 权限 */
		$adminHelper = new \application\helper\admin();
		$aid = $adminHelper->getAdminId();
		if (empty($aid))
		{
			return new json(json::NOT_LOGIN);
		}
		else
		{
			$roleModel = $this->model('role');
			$role = $adminHelper->getGroupId();
			if (! $roleModel->checkPower($role, 'order', roleModel::POWER_ALL))
			{
				$this->model("admin_log")->insertlog($aid, '订单审核失败（权限不足）');
				return new json(json::PARAMETER_ERROR, '权限不足');
			}
		}
		
		$orderno = $this->post('orderno');
		$note = $this->post('note','','htmlspecialchars');
		if (! empty($orderno))
		{
			//添加备注信息
			if (!empty($note))
			{
				$this->model('order')->where('orderno=?',[$orderno])->limit(1)->update('erp_note',$note);
			}
			
			$orderHelper = new \application\helper\order();
			$order = $this->model('order')->where('orderno=?',[$orderno])->find();
			$orderStatus = $orderHelper->convertStatus($order);
			
			$erpSender = new erpSender();
			$result = $erpSender->doSendOrder($orderno);
			if ($result)
			{
				$this->model("admin_log")->insertlog($aid, '订单审核成功,订单号:' . $orderno, 1);
				$this->model('order_log')->add($orderno,'订单取消',$aid,$orderStatus,$note);
				return new json(json::OK);
			}
			else
			{
				return new json(json::PARAMETER_ERROR, '订单推送失败，没有需要推送的订单');
			}
		}
		else
		{
			return new json(json::PARAMETER_ERROR);
		}
	}

	function receive()
	{
		$userHelper = new \application\helper\user();
		if(empty($userHelper->isLogin()))
		{
			return new json(json::NOT_LOGIN);
		}
		
		$orderno = $this->post('orderno');
		if (empty($orderno))
		{
			return new json(json::PARAMETER_ERROR);
		}
		
		$order = $this->model('order')
			->where('orderno=?', [
			$orderno
		])->find();
			
		$orderHelper = new \application\helper\order();
		$convertStatus = $orderHelper->convertStatus($order);
		
		if (! empty($order))
		{
			if ($order['way_status'] != 1)
				return new json(json::PARAMETER_ERROR, '尚未发货呢');
			
			if ($order['receive'] != 0)
				return new json(json::PARAMETER_ERROR, '订单已经收货了');
			
			if ($this->model('order')
				->where('orderno=?', [
				$orderno
			])
				->update([
				'receive' => 1,
				'receive_time' => $_SERVER['REQUEST_TIME']
			]))
			{
				$this->model('order_log')->add($orderno, '订单确认收货',NULL,$convertStatus);
				
				return new json(json::OK);
			}
		}
		return new json(json::PARAMETER_ERROR);
	}

	/**
	 * 订单退款
	 */
	function refund()
	{
		$adminHelper = new admin();
		$admin = $adminHelper->getAdminId();
		if (empty($admin))
		{
			return new json(json::NOT_LOGIN, '请重新登陆');
		}
		
		$roleModel = $this->model('role');
		$role = $adminHelper->getGroupId();
		if (! $roleModel->checkPower($role, 'refund', roleModel::POWER_ALL))
		{
			return new json(json::PARAMETER_ERROR, '权限不足');
		}
		
		$orderno = $this->post('orderno');
		$order_product_id = $this->post('order_product_id');
		if (empty($order_product_id))
		{
			$order_product_id = NULL;
		}
		
		$order = $this->model('order')
			->where('orderno=?', [
			$orderno
		])->find();
		
		if (! empty($order))
		{
			$orderHelper = new \application\helper\order();
			$orderStatus = $orderHelper->convertStatus($order);
			
			if ($orderHelper->refund($orderno, $order_product_id))
			{
				if (empty($order_product_id))
				{
					// 订单取消
					if ($orderHelper->quitOrder($orderno))
					{
						//纪录退款原因和退款备注
						$this->model('order')->where('orderno=?',[$orderno])->limit(1)->update([
							'refund_reason' => $order['refund_reason'].'|'.$this->post('refund_reason','','htmlentities'),
							'refund_note' => $order['refund_reason'].'|'.$this->post('refund_note','','htmlentities'),
						]);
						
						$this->model("admin_log")->insertlog($admin, '订单商品退款成功,订单商品：' . $orderno, 1);
						
						$this->model('order_log')->add($orderno, '订单确认收货',$admin,$orderStatus);
						return new json(json::OK, NULL, $order['pay_type'] == 'alipay' ? '正在退款' : '退款完成');
					}
					else
					{
						return new json(json::PARAMETER_ERROR, '订单取消失败');
					}
				}
				else
				{
					//纪录退款原因和退款备注
					$this->model('order')->where('orderno=?',[$orderno])->limit(1)->update([
						'refund_reason' => $order['refund_reason'].'|'.$this->post('refund_reason','','htmlentities'),
						'refund_note' => $order['refund_reason'].'|'.$this->post('refund_note','','htmlentities'),
					]);
					
					$this->model("admin_log")->insertlog($admin, '订单商品退款成功,订单商品：' . $orderno, 1);
					
					$this->model('order_log')->add($orderno, '订单确认收货',NULL,$orderStatus);
					return new json(json::OK, NULL, $order['pay_type'] == 'alipay' ? '正在退款' : '退款完成');
				}
			}
			return new json(json::PARAMETER_ERROR, '退款失败:' . $orderHelper->getRefundError());
		}
		return new json(json::PARAMETER_ERROR);
	}

	/**
	 * 更改子订单的配送信息
	 * 
	 * @return \application\message\json
	 */
	function changePackage()
	{
		$adminHelper = new admin();
		$aid = $adminHelper->getAdminId();
		if (empty($aid))
		{
			return new json(json::NOT_LOGIN);
		}
		
		$id = $this->post('id');
		$ship_type = $this->post('ship_type');
		$ship_number = $this->post('ship_number');
		if (! empty($id))
		{
			$package = $this->model('order_package')
				->where('id=?', [
				$id
			])
				->find();
			if (! empty($package))
			{
				$this->model('order')->transaction();
				if ($this->model('order_package')
					->where('id=?', [
					$id
				])
					->update([
					'ship_type' => $ship_type,
					'ship_number' => $ship_number,
					'ship_status' => 1,
					'ship_time' => $_SERVER['REQUEST_TIME']
				]))
				{
					if (empty($this->model('order_package')
						->where('orderno=? and ship_status=?', [
						$package['orderno'],
						0
					])
						->find()))
					{
						if (! $this->model('order')
							->where('orderno=?', [
							$package['orderno']
						])
							->update([
							'way_status' => 1,
							'way_type' => 1,
							'way_time' => $_SERVER['REQUEST_TIME']
						]))
						{
							$this->model('order')->rollback();
							$this->model("admin_log")->insertlog($aid, '包裹发货失败(包裹id:' . $id . '，数据库order更新失败)');
						}
					}
					else
					{
						if (! $this->model('order')
							->where('orderno=?', [
							$package['orderno']
						])
							->update([
							'way_status' => 2,
							'way_type' => 1,
							'way_time' => $_SERVER['REQUEST_TIME']
						]))
						{
							$this->model('order')->rollback();
							$this->model("admin_log")->insertlog($aid, '包裹发货失败(包裹id:' . $id . '，数据库order更新失败)');
						}
					}
					$this->model("admin_log")->insertlog($aid, '包裹发货成功(包裹id:' . $id . ')');
					$this->model('order')->commit();
					return new json(json::OK);
				}
				else
				{
					$this->model('order')->rollback();
					$this->model("admin_log")->insertlog($aid, '包裹发货失败(包裹id:' . $id . '，数据库order_package更新失败)');
					return new json(json::PARAMETER_ERROR, '包裹发货失败');
				}
			}
		}
	}
	
	/**
	 * 订单中的商品单独发货，顺便修改供货商和发货仓库
	 */
	function sendProduct()
	{
		$adminHelper = new \application\helper\admin();
		$admin = $adminHelper->getAdminId();
		if (empty($admin))
		{
			return new json(json::NOT_LOGIN);
		}
		$order_product_id = $this->post('order_product_id');
		$publish = $this->post('publish');
		$store = $this->post('store');
		$ship_type = $this->post('ship_type','','trim');
		$ship_number = $this->post('ship_number','','trim');
		$ship_note = $this->post('ship_note','','htmlspecialchars');
		
		$store_info = $this->model('store')->where('id=?',[$store])->find();
		$publish_info = $this->model('publish')->where('id=?',[$publish])->find();
		
		$order_product = $this->model('order_product')->where('id=?',[$order_product_id])->find();
		$order_package = $this->model('order_package')->where('id=?',[$order_product['package_id']])->find();
		
		//假如要更改供货商或者仓库的时候需要检查一下是否匹配
		if ($store != $order_package['store_id'] || $publish_info['name'] != $order_product['publish'])
		{
			$product_publish = $this->model('product_publish')->where('product_id=? and publish_id=? and store=?',[$order_product['pid'],$publish,$store])->find();
			if (empty($product_publish))
			{
				return new json(json::PARAMETER_ERROR,'供货商和仓库不匹配');
			}
			
			if ($product_publish['stock'] < $order_product['num'] * $order_product['bind'])
			{
				return new json(json::PARAMETER_ERROR,'库存不足');
			}
		}
		
		$orderHelper = new \application\helper\order();
		$orderStatus = $orderHelper->convertStatus($order_package['orderno']);
		
		
		//应该首先判断是否需要更改发货仓库
		if ($store!=$order_package['store_id'])
		{
			//假如更改了发货仓库，那么应该判断这个包裹下面是否只有这一个商品，假如只有这一个商品直接更改就好了，不是的话需要新建一个包裹
			//退款了的商品不要计算进去
			$package_products_num = $this->model('order_product')->where('package_id=? and refund=?',[$order_product['package_id'],0])->count();
			if ($package_products_num==1)
			{
				//开启事务
				$this->model('order')->transaction();
				
				//减少库存
				if(!$this->model('product_publish')->where('product_id=? and publish_id=? and store=?',[$order_product['pid'],$publish,$store])->increase('stock',-$order_product['num'] * $order_product['bind']))
				{
					$this->model('order')->rollback();
					return new json(json::PARAMETER_ERROR,'系统繁忙');
				}
				$this->model('product')->where('id=? and store=? and publish',[$order_product['pid'],$store,$publish])->increase('stock',-$order_product['num'] * $order_product['bind']);
				
				//更改order_package中的发货仓库
				if(!$this->model('order_package')->where('id=?',[$order_product['package_id']])->limit(1)->update([
					'store_id' => $store,
					'ship_type' => $ship_type,
					'ship_number' => $ship_number,
					'ship_status' => 1,
					'ship_time' => time(),
					'ship_note' => $ship_note,
				]))
				{
					$this->model('order')->rollback();
					return new json(json::PARAMETER_ERROR,'系统繁忙');
				}
				//更改商品信息中的发货仓库 供货商 和 sku
				if(!$this->model('order_product')->where('id=?',[$order_product_id])->limit(1)->update([
					'store_name'=>$store_info['name'],
					'publish' => $publish_info['name'],
					'sku' => $product_publish['sku'],
				]))
				{
					$this->model('order')->rollback();
					return new json(json::PARAMETER_ERROR,'系统繁忙');
				}
				//去子订单中更改发货仓库
				$suborder_store_product = $this->model('suborder_store_product')->where('order_product_id=?',[$order_product_id])->limit(1)->find();
				//$suborder_store = $this->model('suborder_store')->where('id=?',[$suborder_store_product['suborder_id']])->find();
				if(!$this->model('suborder_store')->where('id=?',[$suborder_store_product['suborder_id']])->limit(1)->update([
					'store'=>$store,
					'erp' => 0,
					'erptime' => 0,
				]))
				{
					$this->model('order')->rollback();
					return new json(json::PARAMETER_ERROR,'系统繁忙');	
				}
				//更改完毕
				$this->model('order')->commit();
				
				
				//更改订单中的way_status  注意可能有退款商品
				$not_send_product_num = 0;
				$not_send_packages = $this->model('order_package')->where('orderno=? and ship_status=?',[$order_package['orderno'],0])->select();
				foreach ($not_send_packages as $package)
				{
					$not_send_product_num += $this->model('order_product')->where('package_id=? and refund=?',[$package['id'],0])->count();
				}
				if ($not_send_product_num>0)
				{
					$this->model('order')->where('orderno=?',[$order_package['orderno']])->limit(1)->update([
						'way_status'=>2,
						'way_time' => time(),
						'way_type' => 1
					]);
				}
				else
				{
					$this->model('order')->where('orderno=?',[$order_package['orderno']])->limit(1)->update([
						'way_status'=>1,
						'way_time' => time(),
						'way_type' => 1
					]);
				}
				
				if (!empty($store_info['erp']))
				{
					//推送ERP
					$erpSender = new erpSender();
					$erpSender->doSendOrder($order_package['orderno']);
				}
				
				$this->model('order_log')->add($order_package['orderno'], '订单中商品单独发货，包裹中只有一个商品，ship_type:'.$ship_type.',ship_number:'.$ship_number.',store:'.$store.',.publish:'.$publish,$admin,$orderStatus);
				return new json(json::OK);
			}
			elseif ($package_products_num>1)
			{
				//开启事务
				$this->model('order')->transaction();
				
				if($this->model('order_package')->insert([
					'orderno' => $order_package['orderno'],
					'ship_status' => 1,
					'ship_type' => $ship_type,
					'ship_time' => time(),
					'ship_number' => $ship_number,
					'ship_money' => 0,
					'store_id' => $store,
					'ship_note' => $ship_note,
				]))
				{
					$new_order_package_id = $this->model('order_package')->lastInsertId();
					$this->model('order_product')->where('id=?',[$order_product_id])->update([
						'package_id'=>$new_order_package_id,
						'store_name' => $store_info['name'],
						'publish' => $publish_info['name'],
						'sku' => $product_publish['sku'],
					]);
					
					//减少库存
					if(!$this->model('product_publish')->where('product_id=? and publish_id=? and store=?',[$order_product['pid'],$publish,$store])->increase('stock',-$order_product['num'] * $order_product['bind']))
					{
						$this->model('order')->rollback();
						return new json(json::PARAMETER_ERROR,'系统繁忙');
					}
					$this->model('product')->where('id=? and store=? and publish',[$order_product['pid'],$store,$publish])->increase('stock',-$order_product['num'] * $order_product['bind']);
					
					//获取原来的子订单的信息
					$suborder_store_product = $this->model('suborder_store_product')->where('order_product_id=?',[$order_product_id])->find();
					$suborder_store = $this->model('suborder_store')->where('id=?',[$suborder_store_product['suborder_id']])->find();
					$order = $this->model('order')->where('orderno=?',[$suborder_store['main_orderno']])->find();
					
					//更改新的子订单的信息，这里有个bug啊，旧的子订单的基本信息没有修改
					unset($suborder_store['id']);
					$suborder_store['date'] = date('Y-m-d');
					$suborder_store['pay_money'] = $order_product['price'] * $order_product['num'] - $order_product['price'] * $order_product['num']/$order['goodsamount']*$order['discount'];
					$suborder_store['orderamount'] = $suborder_store['pay_money'];
					$suborder_store['goodsamount'] = $order_product['price'] * $order_product['num'];
					$suborder_store['discount'] = $order_product['price'] * $order_product['num']/$order['goodsamount']*$order['discount'];
					$suborder_store['feeamount'] = $order_product['fee'];
					$suborder_store['taxamount'] = $order_product['tax'];
					$suborder_store['erp'] = 0;
					$suborder_store['erptime'] = 0;
					$suborder_store['store'] = $store;
					//创建一个子订单
					if($this->model('suborder_store')->insert($suborder_store))
					{
						$new_suborder_store_id = $this->model('suborder_store')->lastInsertId();
						if(!$this->model('suborder_store_product')->where('order_product_id=?',[$order_product_id])->update([
							'suborder_id' => $new_suborder_store_id,
						]))
						{
							$this->model('order')->rollback();
							return new json(json::PARAMETER_ERROR,'创建子订单失败');
						}
					}
					
					$this->model('order')->commit();
					
					//更改订单中的way_status  注意可能有退款商品
					$not_send_product_num = 0;
					$not_send_packages = $this->model('order_package')->where('orderno=? and ship_status=?',[$order_package['orderno'],0])->select();
					foreach ($not_send_packages as $package)
					{
						$not_send_product_num += $this->model('order_product')->where('package_id=? and refund=?',[$package['id'],0])->count();
					}
					if ($not_send_product_num>0)
					{
						$this->model('order')->where('orderno=?',[$order_package['orderno']])->limit(1)->update([
							'way_status'=>2,
							'way_time' => time(),
							'way_type' => 1
						]);
					}
					else
					{
						$this->model('order')->where('orderno=?',[$order_package['orderno']])->limit(1)->update([
							'way_status'=>1,
							'way_time' => time(),
							'way_type' => 1
						]);
					}
					
					if (!empty($store_info['erp']))
					{
						//推送ERP
						$erpSender = new erpSender();
						$erpSender->doSendOrder($order_package['orderno']);
					}
					
					$this->model('order_log')->add($order_package['orderno'], '订单中商品单独发货，包裹中有多个商品，ship_type:'.$ship_type.',ship_number:'.$ship_number.',store:'.$store.',.publish:'.$publish,$admin,$orderStatus);
					return new json(json::OK);
				}
				else
				{
					$this->model('order')->rollback();
					return new json(json::PARAMETER_ERROR,'创建包裹失败');
				}
			}
			else
			{
				return new json(json::PARAMETER_ERROR);
			}
		}
		else if ($order_product['publish'] != $publish_info['name'])//只是简单的更改供应商
		{
			$this->model('order_product')->where('id=?',[$order_product_id])->limit(1)->update([
				'publish'=>$publish_info['name']
			]);
			//判断快递方式是否需要更改
			if ($order_package['ship_type'] != $ship_type || $order_package['ship_number'] != $ship_number)
			{
				$this->model('order_package')->where('id=?',[$order_product['package_id']])->limit(1)->update([
					'ship_type' => $ship_type,
					'ship_note'=>$ship_note,
					'ship_number'=>$ship_number,
					'ship_status'=>1,
					'ship_time' =>time(),
				]);
			}
			//更改订单中的way_status  注意可能有退款商品
			$not_send_product_num = 0;
			$not_send_packages = $this->model('order_package')->where('orderno=? and ship_status=?',[$order_package['orderno'],0])->select();
			foreach ($not_send_packages as $package)
			{
				$not_send_product_num += $this->model('order_product')->where('package_id=? and refund=?',[$package['id'],0])->count();
			}
			if ($not_send_product_num>0)
			{
				$this->model('order')->where('orderno=?',[$order_package['orderno']])->limit(1)->update([
					'way_status'=>2,
					'way_time' => time(),
					'way_type' => 1
				]);
			}
			else
			{
				$this->model('order')->where('orderno=?',[$order_package['orderno']])->limit(1)->update([
					'way_status'=>1,
					'way_time' => time(),
					'way_type' => 1
				]);
			}
			$this->model('order_log')->add($order_package['orderno'], '更改了供应商，ship_type:'.$ship_type.',ship_number:'.$ship_number.',store:'.$store.',.publish:'.$publish,$admin,$orderStatus);
			return new json(json::OK);
		}
		else//更改快递方式
		{
			//判断快递方式是否需要更改
			if ($order_package['ship_type'] != $ship_type || $order_package['ship_number'] != $ship_number)
			{
				$this->model('order_package')->where('id=?',[$order_product['package_id']])->limit(1)->update([
					'ship_type' => $ship_type,
					'ship_note'=>$ship_note,
					'ship_number'=>$ship_number,
					'ship_status'=>1,
					'ship_time' =>time(),
				]);
				$this->model('order_log')->add($order_package['orderno'], '订单中商品单独发货，只是更改了物流信息，ship_type:'.$ship_type.',ship_number:'.$ship_number.',store:'.$store.',.publish:'.$publish,$admin,$orderStatus);
				
			}
			//更改订单中的way_status  注意可能有退款商品
			$not_send_product_num = 0;
			$not_send_packages = $this->model('order_package')->where('orderno=? and ship_status=?',[$order_package['orderno'],0])->select();
			foreach ($not_send_packages as $package)
			{
				$not_send_product_num += $this->model('order_product')->where('package_id=? and refund=?',[$package['id'],0])->count();
			}
			if ($not_send_product_num>0)
			{
				$this->model('order')->where('orderno=?',[$order_package['orderno']])->limit(1)->update([
					'way_status'=>2,
					'way_time' => time(),
					'way_type' => 1
				]);
			}
			else
			{
				$this->model('order')->where('orderno=?',[$order_package['orderno']])->limit(1)->update([
					'way_status'=>1,
					'way_time' => time(),
					'way_type' => 1
				]);
			}
			return new json(json::OK);
		}
	}
	
	/**
	 * 单个订单手动发货
	 * @return \application\message\json
	 */
	function send()
	{
		$orderno = $this->post('orderno','');
		if (empty($orderno))
		{
			return new json(json::PARAMETER_ERROR);
		}
		$adminHelper = new admin();
		$admin = $adminHelper->getAdminId();
		if (empty($admin))
		{
			return new json(json::NOT_LOGIN);
		}
		
		$order = $this->model('order')->where('orderno=?',[$orderno])->find();
		$orderHelper = new \application\helper\order();
		$orderStatus = $orderHelper->convertStatus($order);
		
		if (empty($order))
		{
			return new json(json::PARAMETER_ERROR,'订单不存在');
		}
		
		if ($order['status'] != 1)
		{
			return new json(json::PARAMETER_ERROR, '订单无效');
		}
		
		if ($order['way_status']==1)
		{
			return new json(json::PARAMETER_ERROR,'订单已经发货');
		}
		
		$send_order = $this->model('order_package')->where('orderno=? and ship_status=?',[$orderno,1])->find();
		if (!empty($send_order))
		{
			return new json(json::PARAMETER_ERROR,'有已经发货了的订单');
		}
		
		$this->model('order')->transaction();
		
		if(!$this->model('order')->where('orderno=?',[$orderno])->limit(1)->update([
			'way_status'=>1,
			'way_time'=>$_SERVER['REQUEST_TIME'],
			'way_type' => 1,
		]))
		{
			$this->model('order')->rollback();
			return new json(json::PARAMETER_ERROR,'订单状态更改失败');
		}
		
		if (!$this->model('order_package')->where('orderno=?',[$orderno])->update([
			'ship_status'=>1,
			'ship_type' => $this->post('ship_type',''),
			'ship_time' => $_SERVER['REQUEST_TIME'],
			'ship_number' => $this->post('ship_number',''),
			'ship_note' => $this->post('ship_note',''),
		]))
		{
			$this->model('order')->rollback();
			return new json(json::PARAMETER_ERROR,'包裹状态更改失败');
		}
		$this->model('order')->commit();
		$this->model("admin_log")->insertlog($admin, '订单发货成功，订单号：' . $orderno, 1);
		$this->model('order_log')->add($orderno,'订单手动发货',$admin,$orderStatus);
		return new json(json::OK);
	}

	function importWay()
	{
		$admin = $this->session->id;
		ini_set('memory_limit', '512M');
		$config = config('file');
		// 文件类型
		$config->type = [
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.ms-excel',
			'application/vnd.ms-office',
			'application/zip'
		];
		// 允许文件的最大值
		$config->size = 1024 * 1024 * 10;
		$file = $this->file->receive($_FILES['file'], $config);
		if (is_file($file))
		{
			$phpexcel_root = ROOT . '/extends/PHPExcel';
			include $phpexcel_root . '/PHPExcel/IOFactory.php';
			
			$objReader = \PHPExcel_IOFactory::createReader('Excel2007');
			if ($objReader->canRead($file))
			{
				try
				{
					// 读取excel中的数据
					$objPHPExcel = $objReader->load($file);
					$sheet = $objPHPExcel->getSheet(0);
					$rowNum = $sheet->getHighestRow();
					// $colNum = $sheet->getHighestColumn();
					if ($rowNum > 1000)
					{
						$rowNum = 1000;
					}
				}
				catch (\Exception $e)
				{
					$this->model("admin_log")->insertlog($admin, '订单导入物流信息失败（无法做为一个excel文件解析）');
					return new json(json::PARAMETER_ERROR, '无法做为一个excel文件解析');
				}
				
				$data = [];
				for ($row = 2; $row <= $rowNum; $row ++)
				{ // 行数是以第2行开始
					$dataset = [];
					for ($column = 'A'; $column <= 'D'; $column ++)
					{ // 列数是以A列开始
					                                                 // $dataset[] = $sheet->getCell($column.$row)->getValue();
						$dataset[] = $sheet->getCell($column . $row)->getCalculatedValue();
					}
					
					$data[] = $dataset;
				}
				
				if (empty($data))
				{
					$this->model("admin_log")->insertlog($admin, '订单导入物流信息失败（该文档中不包含任何信息）');
					return new json(json::PARAMETER_ERROR, '该文档中不包含任何信息');
				}
				
				// 订单导入结果
				$result_order = [];
				// 快递公司编码和中文名称对照表 key:代码 value:中文名
				$ship_code = [];
				$ship = $this->model('ship')->select();
				foreach ($ship as $temp_ship)
				{
					$ship_code[$temp_ship['code']] = $temp_ship['name'];
				}
				
				// 快递公司编码和中文名称对照表 key:中文名 value:代码
				$ship_code_name = array_flip($ship_code);
				
				$import_orderno_array = [];
				
				// 遍历数据
				foreach ($data as $order)
				{
					if (empty($order))
					{
						continue;
					}
					
					if (count($order) != 4)
					{
						$this->model("admin_log")->insertlog($admin, '订单导入物流信息失败（上传文件内容格式错误）');
						return new json(json::PARAMETER_ERROR, '上传文件内容格式错误');
					}
					
					// 获取订单号
					$orderno = trim((string) $order[0]);
					
					// 获取包裹号
					$package = trim((string) $order[1]);
					// 获取发货时间
					/*
					 * $ship_time = trim((string)$order[2]);
					 * if (empty($ship_time))
					 * {
					 * $ship_time = $_SERVER['REQUEST_TIME'];
					 * }
					 * else
					 * {
					 * $ship_time = strtotime($ship_time);
					 * }
					 * if ($ship_time == false || $ship_time == -1)
					 * {
					 * $ship_time = $_SERVER['REQUEST_TIME'];
					 * }
					 */
					$ship_time = $_SERVER['REQUEST_TIME'];
					
					// 获取快递公司 中文名称
					$ship_type = trim((string) $order[2]);
					// 获取快递公司代码
					if (isset($ship_code_name[$ship_type]))
					{
						$ship_type_code = $ship_code_name[$ship_type];
					}
					else
					{
						$ship_type_code = '';
					}
					
					// 获取快递单号
					$ship_number = trim((string) $order[3]);
					
					// 对于不存在订单号或者包裹号的 过滤掉
					if (empty($orderno))
					{
						continue;
					}
					
					// 修改
					/*
					 * if (empty($package))
					 * {
					 * continue;
					 * }
					 */
					
					// 订单信息
					if ($orderno[0] == 1)
					{
						$t_order = $this->model('order')
							->where('orderno=?', [
							$orderno
						])
							->find();
					}
					else if ($orderno[0] == 2 && strlen($orderno) > 8)
					{
						$sub_id = substr($orderno, 8);
						$suborder = $this->model('suborder_store')
							->where('id=?', [
							$sub_id
						])
							->find();
						if (! empty($suborder))
						{
							$orderno = $suborder['main_orderno'];
							$t_order = $this->model('order')
								->where('orderno=?', [
								$orderno
							])
								->find();
						}
						else
						{
							continue;
						}
					}
					// 包裹信息
					/*
					 * $t_package = $this->model('order_package')->where('id=? and orderno=?',[$package,$orderno])->find();
					 */
					// 导入结果
					$result = [
						'orderno' => $orderno, // 订单号
						'package' => $package, // 包裹号
						'ship_time' => date('Y-m-d H:i:s', $ship_time), // 发货时间
						'ship_type' => $ship_type, // 快递公司
						'ship_number' => $ship_number, // 快递单号
						'success' => false,
						'result' => '导入失败'
					];
					
					if ($t_order['way_status'] != 0)
					{
						$result['result'] = '订单已经发货';
						$result_order[] = $result;
						continue;
					}
					
					if (empty($ship_type_code))
					{
						$result['result'] = '无法读取到快递公司或者这个快递公司不支持';
						$result_order[] = $result;
						continue;
					}
					
					if (empty($ship_number))
					{
						$result['result'] = '快递单号为空';
						$result_order[] = $result;
						continue;
					}
					
					if (empty($t_order))
					{
						$result['result'] = '不存在该订单';
						$result_order[] = $result;
						continue;
					}
					
					/*
					 * if (empty($t_package))
					 * {
					 * $result['result'] = '不存在该包裹';
					 * $result_order[] = $result;
					 * continue;
					 * }
					 */
					
					if ($t_order['status'] == 0)
					{
						$result['result'] = '订单已经取消';
						$result_order[] = $result;
						continue;
					}
					
					if ($t_order['pay_status'] == 0)
					{
						$result['result'] = '订单尚未支付';
						$result_order[] = $result;
						continue;
					}
					
					// 更改包裹的发货状态
					/*
					 * $this->model('order_package')->where('id=? and orderno=?',[$package,$orderno])->limit(1)->update([
					 * 'ship_status' => 1,
					 * 'ship_type' => $ship_type_code,
					 * 'ship_number' => $ship_number,
					 * 'ship_time' => $ship_time,
					 * ]);
					 */
					
					/*
					 * 7-11 解决了更新只更新第一个包裹的快递信息
					 */
					$this->model('order_package')
						->where('orderno=?', [
						$orderno
					])
						->update([
						'ship_status' => 1,
						'ship_type' => $ship_type_code,
						'ship_number' => $ship_number,
						'ship_time' => $_SERVER['REQUEST_TIME']
					]);
					
					$import_orderno_array[] = $orderno;
					
					$result['success'] = true;
					$result['result'] = '导入成功';
					
					$result_order[] = $result;
				}
				
				// 删除上传的文件
				unlink($file);
				
				$import_orderno_array = array_unique($import_orderno_array);
				$this->model('order')->transaction();
				foreach ($import_orderno_array as $orderno)
				{
					// 判断下是否存在尚未发货的包裹
					if (empty($this->model('order_package')
						->where('orderno=? and ship_status=?', [
						$orderno,
						0
					])
						->find()))
					{
						// 不存在没有尚未发货的包裹，订单状态更改为已发货
						if (! $this->model('order')
							->where('orderno=?', [
							$orderno
						])
							->update([
							'way_status' => 1,
							'way_type' => 2,
							'way_time' => $_SERVER['REQUEST_TIME']
						]))
						{
							$this->model('order')->rollback();
							$this->model("admin_log")->insertlog($admin, '订单导入物流信息失败（订单发货失败，请重试）');
							return new json(json::PARAMETER_ERROR, '订单发货失败，请重试');
						}
					}
					else
					{
						// 订单还有部分包裹没有发货，物流状态更改为 部分发货
						if (! $this->model('order')
							->where('orderno=?', [
							$orderno
						])
							->update([
							'way_status' => 2,
							'way_type' => 2,
							'way_time' => $_SERVER['REQUEST_TIME']
						]))
						{
							$this->model('order')->rollback();
							$this->model("admin_log")->insertlog($admin, '订单导入物流信息失败（订单发货失败，请重试）');
							return new json(json::PARAMETER_ERROR, '订单发货失败，请重试');
						}
					}
				}
				$this->model('order')->commit();
				$this->model("admin_log")->insertlog($admin, '订单导入物流信息成功,订单id：' . $result_order, 1);
				return new json(json::OK, NULL, $result_order);
			}
			else
			{
				$this->model("admin_log")->insertlog($admin, '订单导入物流信息失败（无法读取该文件）');
				return new json(json::PARAMETER_ERROR, '无法读取该文件');
			}
		}
		else
		{
			$this->model("admin_log")->insertlog($admin, '订单导入物流信息失败（文件上传失败，请检查文件类型或文件大小）');
            return new json(json::PARAMETER_ERROR, '文件上传失败，请检查文件类型或文件大小');
        }
    }
}
