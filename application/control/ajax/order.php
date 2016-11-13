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

	function note()
	{
		$admin = $this->session->id;
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
				])
					->find('note');
				
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
			
			$this->model('order_log')->add($order['orderno'], '订单创建成功，等待支付');
			
			$this->model('order')->commit();
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
		$admin = $this->session->id;
		$orderno = $this->post('orderno');
		if (! empty($orderno))
		{
			if (! empty($this->model('task_user')
				->where('orderno=?', [
				$orderno
			])
				->find()))
			{
				$this->model("admin_log")->insertlog($admin, '取消订单失败（团购订单无法手动取消）');
				return new json(json::PARAMETER_ERROR, '团购订单无法手动取消');
			}
			
			$orderHelper = new \application\helper\order();
			
			$userHelper = new \application\helper\user();
			$uid = $userHelper->isLogin();
			if (empty($uid))
			{
				$adminHelper = new \application\helper\admin();
				$aid = $adminHelper->getAdminId();
				if (empty($aid))
				{
					return new json(json::NOT_LOGIN);
				}
				else
				{
					$order = $this->model('order')
						->where('orderno=?', [
						$orderno
					])
						->find();
					if ($order['pay_status'] == 1 || $order['pay_status'] == 4)
					{
						$roleModel = $this->model('role');
						$role = $adminHelper->getGroupId();
						if (! $roleModel->checkPower($role, 'refund', roleModel::POWER_ALL))
						{
							$this->model("admin_log")->insertlog($admin, '取消订单失败（权限不足）');
							return new json(json::PARAMETER_ERROR, '权限不足');
						}
						
						if (! $orderHelper->refund($orderno))
						{
							$this->model("admin_log")->insertlog($admin, '取消订单失败（订单退款失败）');
							return new json(json::PARAMETER_ERROR, '订单退款失败');
						}
					}
				}
			}
			
			$this->model('order')->transaction();
			
			if ($orderHelper->quitOrder($orderno, false))
			{
				$this->model('order')->commit();
				$this->model("admin_log")->insertlog($admin, '取消订单成功，订单号：' . $orderno, 1);
				return new json(json::OK);
			}
			else
			{
				$this->model('order')->rollback();
				$this->model("admin_log")->insertlog($admin, '取消订单失败（订单退款失败）');
				return new json(json::PARAMETER_ERROR, '取消失败');
			}
		}
		$this->model("admin_log")->insertlog($admin, '取消订单失败（参数错误）');
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
		if (! empty($orderno))
		{
			$erpSender = new erpSender();
			$result = $erpSender->doSendOrder($orderno);
			if ($result)
			{
				$this->model("admin_log")->insertlog($aid, '订单审核成功,订单号:' . $orderno, 1);
				return new json(json::OK);
			}
			else
			{
				return new json(json::PARAMETER_ERROR, '订单推送失败，没有需要推送的订单');
			}
		}
	}

	/**
	 * 推送支付单
	 */
	function payed()
	{
		$orderno = $this->post('orderno');
		$order = $this->model('order')
			->where('orderno=?', [
			$orderno
		])
			->find();
		if (! empty($order))
		{
			if ($order['status'] == '0')
				return new json(json::PARAMETER_ERROR, '无效订单无法发送支付单');
			
			if ($order['pay_status'] == '0')
				return new json(json::PARAMETER_ERROR, '尚未支付的订单无法推送支付单');
			
			$partner = $this->model('system')->get('partner', $order['pay_type']);
			$key = $this->model('system')->get('key', $order['pay_type']);
			
			$pay = new pay();
			$pay->setId($orderno);
			$pay->setCharset('utf-8');
			$pay->setSigntype('md5');
			$pay->setPartner($partner);
			$pay->setKey($key);
			$pay->setPayType($order['pay_type']);
			$pay->setPaynumber($order['pay_number']);
			$pay->setMoney($order['pay_money']);
			$pay->setUrl('https://mapi.alipay.com/gateway.do');
			$pay->createParameter([
				'customs_place' => $this->model('system')
					->get('customs_place', 'system'),
				'customs_code' => $this->model('system')
					->get('customs_code', 'system'),
				'customs_name' => $this->model('system')
					->get('customs_name', 'system')
			]);
			$result = $pay->payed();
			$result = xmlToArray($result);
			if (strtoupper($result['response']['alipay']['result_code']) == 'SUCCESS')
			{
				$this->model('order')
					->where('orderno=?', [
					$orderno
				])
					->limit(1)
					->update([
					'payed' => 1,
					'payed_time' => $_SERVER['REQUEST_TIME']
				]);
				
				if ($this->http->isAjax())
				{
					return new json(json::OK);
				}
				else
				{
					$this->response->setCode(302);
					$this->response->addHeader('Location', $this->http->referer());
				}
			}
			else
			{
				if ($this->http->isAjax())
				{
					return new json(json::PARAMETER_ERROR, $result['response']['alipay']['detail_error_des']);
				}
				else
				{
					echo $result['response']['alipay']['detail_error_des'];
				}
			}
		}
		return new json(json::PARAMETER_ERROR, '订单不存在');
	}

	function receive()
	{
		$orderno = $this->post('orderno');
		if (empty($orderno))
			return new json(json::PARAMETER_ERROR);
		
		$order = $this->model('order')
			->where('orderno=?', [
			$orderno
		])
			->find();
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
				$this->model('order_log')->add($orderno, '订单确认收货了');
				
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
		$admin = $this->session->id;
		$adminHelper = new admin();
		if (empty($adminHelper->getAdminId()))
		{
			$this->model("admin_log")->insertlog($admin, '订单商品退款失败（用户未登陆）');
			return new json(json::NOT_LOGIN, '请重新登陆');
		}
		
		$roleModel = $this->model('role');
		$role = $adminHelper->getGroupId();
		if (! $roleModel->checkPower($role, 'refund', roleModel::POWER_ALL))
		{
			$this->model("admin_log")->insertlog($admin, '订单商品退款失败（权限不足）');
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
		])
			->find();
		if (! empty($order))
		{
			$orderHelper = new \application\helper\order();
			
			if ($orderHelper->refund($orderno, $order_product_id))
			{
				if (empty($order_product_id))
				{
					// 订单取消
					if ($orderHelper->quitOrder($orderno))
					{
						$this->model("admin_log")->insertlog($admin, '订单商品退款成功,订单商品：' . $orderno, 1);
						return new json(json::OK, NULL, $order['pay_type'] == 'alipay' ? '正在退款' : '退款完成');
					}
					else
					{
						$this->model("admin_log")->insertlog($admin, '订单商品退款失败（订单取消失败）');
						return new json(json::PARAMETER_ERROR, '订单取消失败');
					}
				}
				else
				{
					$this->model("admin_log")->insertlog($admin, '订单商品退款成功,订单商品：' . $orderno, 1);
					return new json(json::OK, NULL, $order['pay_type'] == 'alipay' ? '正在退款' : '退款完成');
				}
			}
			$this->model("admin_log")->insertlog($admin, '订单商品退款失败（退款失败）' . $orderHelper->getRefundError());
			return new json(json::PARAMETER_ERROR, '退款失败:' . $orderHelper->getRefundError());
		}
		$this->model("admin_log")->insertlog($admin, '订单商品退款失败（参数错误）');
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

	function confirmSend()
	{
		$adminHelper = new admin();
		$admin = $adminHelper->getAdminId();
		$data = $this->post('data');
		$data = json_decode($data, true);
		$orderno = $this->post('orderno');
		if (! empty($data) && ! empty($orderno))
		{
			$order = $this->model('order')
				->where('orderno=?', [
				$orderno
			])
				->find();
			if (empty($order))
			{
				$this->model("admin_log")->insertlog($admin, '订单发货失败（订单不存在）');
				return new json(json::PARAMETER_ERROR, '订单不存在');
			}
			
			if ($order['status'] != 1)
			{
				$this->model("admin_log")->insertlog($admin, '订单发货失败（订单无效）');
				return new json(json::PARAMETER_ERROR, '订单无效');
			}
			
			if ($order['way_status'] == 1)
			{
				$this->model("admin_log")->insertlog($admin, '订单发货失败（订单已发货）');
				return new json(json::PARAMETER_ERROR, '订单已发货');
			}
			
			// 过滤掉空值
			$data = array_values(array_filter($data));
			
			$this->model('order_package')->transaction();
			foreach ($data as $ship)
			{
				if (empty($ship['ship_type']) || empty($ship['ship_number']))
				{
					$this->model('order_package')->rollback();
					$this->model("admin_log")->insertlog($admin, '订单发货失败（请填写所有的快递单号和配送方式）');
					return new json(json::PARAMETER_ERROR, '请填写所有的快递单号和配送方式');
				}
				
				if (! $this->model('order_package')
					->where('id=?', [
					$ship['id']
				])
					->limit(1)
					->update([
					'ship_type' => $ship['ship_type'],
					'ship_number' => $ship['ship_number'],
					'ship_time' => $_SERVER['REQUEST_TIME'],
					'ship_status' => 1
				]))
				{
					$this->model('order_package')->rollback();
					$this->model("admin_log")->insertlog($admin, '订单发货失败（参数错误）');
					return new json(json::PARAMETER_ERROR);
				}
			}
			
			if (! $this->model('order')
				->where('orderno=?', [
				$orderno
			])
				->limit(1)
				->update([
				'way_status' => 1,
				'way_type' => 1,
				'way_time' => $_SERVER['REQUEST_TIME']
			]))
			{
				$this->model('order_package')->rollback();
				$this->model("admin_log")->insertlog($admin, '订单发货失败（参数错误）');
				return new json(json::PARAMETER_ERROR);
			}
			
			$this->model('order_package')->commit();
			$this->model("admin_log")->insertlog($admin, '订单发货成功，订单号：' . $orderno, 1);
			return new json(json::OK);
			/*
			 * //获取现在所有的商品
			 * $pre_product = $this->model('order_package')
			 * ->table('order_product','left join','order_product.package_id=order_package.id')
			 * ->where('order_package.orderno=?',[$orderno])
			 * ->select([
			 * 'order_product.pid',
			 * 'order_product.content',
			 * 'order_product.num',
			 * 'order_product.price',
			 * ]);
			 *
			 * //检查商品是否正确和商品数量是否匹配
			 * //匹配总数
			 * if(array_sum(array_column($pre_product,'num')) != array_sum(array_column($data,'num')))
			 * {
			 * return new json(json::PARAMETER_ERROR,'参数错误，请刷新页面重试1');
			 * }
			 *
			 * //开始重新分配包裹
			 * //标识数据
			 * array_walk($data, function(&$value,$index,$pre_product){
			 * foreach ($pre_product as $product)
			 * {
			 * if ($product['pid'] == $value['pid'])
			 * {
			 * $value['price'] = $product['price'];
			 * }
			 * }
			 * },$pre_product);
			 *
			 * $this->model('order_package')->transaction();
			 * //清空原包裹
			 * if(!$this->model('order_package')->where('orderno=?',[$orderno])->delete())
			 * {
			 * $this->model('order_package')->rollback();
			 * return new json(json::PARAMETER_ERROR,'清空原包裹失败');
			 * }
			 * foreach ($data as $package)
			 * {
			 * if (empty($package['ship_type']))
			 * {
			 * $this->model('order_package')->rollback();
			 * return new json(json::PARAMETER_ERROR,'请选择配送方式');
			 * }
			 * if (empty($package['ship_number']))
			 * {
			 * $this->model('order_package')->rollback();
			 * return new json(json::PARAMETER_ERROR,'请输入配送编号');
			 * }
			 *
			 * $product = $this->model('product')->where('id=?',[$package['pid']])->find();
			 *
			 * $last_package = $this->model('order_package')->where('orderno=? and ship_status=? and ship_type=? and ship_number=? and store_id=?',[$orderno,1,$package['ship_type'],$package['ship_number'],$product['store']])->find();
			 * if (empty($last_package))
			 * {
			 * if(!$this->model('order_package')->insert([
			 * 'orderno' => $orderno,
			 * 'ship_status'=>1,
			 * 'ship_type' => $package['ship_type'],
			 * 'ship_time' => $_SERVER['REQUEST_TIME'],
			 * 'ship_number' => $package['ship_number'],
			 * 'ship_money' => 0,
			 * 'store_id' => $product['store'],
			 * ]))
			 * {
			 * $this->model('order_package')->rollback();
			 * return new json(json::PARAMETER_ERROR,'重新分配包裹失败1');
			 * }
			 * $package_id = $this->model('order_package')->lastInsertId('order_package');
			 * }
			 * else
			 * {
			 * $package_id = $last_package['id'];
			 * }
			 *
			 * if(!$this->model('order_product')->insert([
			 * 'package_id' => $package_id,
			 * 'pid' => $package['pid'],
			 * 'content' => $package['content'],
			 * 'num' => $package['num'],
			 * 'price' => $package['price'],
			 * ]))
			 * {
			 * $this->model('order_package')->rollback();
			 * return new json(json::PARAMETER_ERROR,'重新分配包裹失败2');
			 * }
			 * }
			 *
			 * if(!$this->model('order')->where('orderno=?',[$orderno])->update([
			 * 'way_status'=>1,
			 * 'way_time'=>$_SERVER['REQUEST_TIME']
			 * ]))
			 * {
			 * $this->model('order_package')->rollback();
			 * return new json(json::PARAMETER_ERROR,'订单发货失败');
			 * }
			 *
			 * $this->model('order_package')->commit();
			 * return new json(json::OK);
			 */
		}
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
