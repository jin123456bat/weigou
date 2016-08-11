<?php
namespace application\callback;
use system\core\base;
class order extends base
{
	/**
	 * 整个订单退款的回调
	 * @param unknown $orderno
	 * @param unknown $refundno
	 * @return boolean
	 */
	function refundOrder($refundno)
	{
		$refund = $this->model('refund')->where('refundno=?',[$refundno])->find();
		if (empty($refund))
		{
			return false;
		}
		
		$orderno = $refund['orderno'];
		
		$order = $this->model('order')->where('orderno=?',[$orderno])->find();
		
		//标记退款单完成状态
		if(!$this->model('refund')->where('refundno=?',[$refundno])->limit(1)->update([
			'status' => 1,
			'completetime' => $_SERVER['REQUEST_TIME'],
		]))
		{
			return false;
		}
		
		//查找订单已经退款的金额
		$extra_money = $this->model('order_package')
		->table('order_product','left join','order_product.package_id=order_package.id')
		->where('order_package.orderno=?',[$orderno])
		->where('order_product.refund!=?',[0])
		->find('sum(order_product.refundmoney) as extra_money');
		$extra_money = isset($extra_money['extra_money'])?$extra_money['extra_money']:0;
		
		//退还相关金额
		$swifts = $this->model('swift')
		->where('orderno=? and order_type=? and type=?',[$orderno,'order',0])
		->where('source in (?)',[2,3,4])
		->select();
		
		foreach ($swifts as $swift)
		{
			if (!empty(floatval($swift['money'])) && $swift['money'] > 0)
			{
				//应该退还的佣金
				$swift_money = number_format(($order['goodsamount'] - $extra_money) / $order['goodsamount'] * $swift['money'],2,'.','');
				if (!empty(floatval($swift_money)) && $swift_money > 0)
				{
					if(!$this->model('user')->where('id=?',[$swift['uid']])->increase('money',-$swift_money))
					{
						return false;
					}
					else
					{
						if(!$this->model('swift')->insert([
							'uid' => $swift['uid'],
							'money' => $swift_money,
							'type' => 1,
							'time' => $_SERVER['REQUEST_TIME'],
							'note' => '收益来源订单退款，扣除余额',
							'source' => 8,
							'order_type' => 'refund',
							'orderno' => $orderno,
						]))
						{
							return false;
						}
					}
				}
			}
		}
		
		
		//标记一下商品退款
		$order_product = $this->model('order_package')
		->table('order_product','left join','order_product.package_id=order_package.id')
		->where('order_package.orderno=?',[$orderno])
		->where('order_product.refund=?',[0])
		->select('order_product.*');
		foreach ($order_product as $product)
		{
			if (empty($order['coupon']))
			{
				if(!$this->model('order_product')->where('id=?',[$product['id']])->update([
					'refund' => 1,
					'refundmoney' => $product['num'] * $product['price'],
					'refundtime' => $_SERVER['REQUEST_TIME']
				]))
				{
					return false;
				}
			}
			else
			{
				$coupon = $this->model('coupon')->where('id=?',[$order['coupon']])->find();
				if (empty($coupon['product_id']))
				{
					if(!$this->model('order_product')->where('id=?',[$product['id']])->update([
						'refund' => 1,
						'refundmoney' => $product['num'] * $product['price'] - $product['num'] * $product['price'] / $order['goodsamount'] * $coupon['value'],
						'refundtime' => $_SERVER['REQUEST_TIME']
					]))
					{
						return false;
					}
				}
				else
				{
					if ($coupon['product_id'] == $product['pid'])
					{
						if(!$this->model('order_product')->where('id=?',[$product['id']])->update([
							'refund' => 1,
							'refundmoney' => $product['num'] * $product['price'] - $coupon['value'],
							'refundtime' => $_SERVER['REQUEST_TIME']
						]))
						{
							return false;
						}
					}
					else
					{
						if(!$this->model('order_product')->where('id=?',[$product['id']])->update([
							'refund' => 1,
							'refundmoney' => $product['num'] * $product['price'],
							'refundtime' => $_SERVER['REQUEST_TIME']
						]))
						{
							return false;
						}
					}
				}
			}
		}
		
		
		
		$this->model('order_log')->add($orderno,'订单退款成功');
		
		return true;
	}
	
	/**
	 * 订单支付完成的回调
	 * @param unknown $orderno
	 */
	function payedOrder($orderno)
	{
		/* $product = $this->model('order_package')
		->table('order_product','left join','order_product.package_id=order_package.id')
		->where('order_package.orderno=?',[$orderno])
		->select('order_product.pid,order_product.num');
		foreach ($product as $p)
		{
			//支付成功后增加商品的销售数量
			$this->model('product')->where('id=?',[$p['pid']])->increase('selled',$p['num']);
		} */
		
		$task_user = $this->model('task_user')->where('orderno=?',[$orderno])->find();
		if (!empty($task_user))
		{
			//是拼团订单
			if (empty($task_user['o_orderno']))
			{
				$main_order_orderno = $orderno;
			}
			else
			{
				$main_order_orderno = $task_user['o_orderno'];
			}
			
			//判断拼团是否成功
			$pay_success_num = $this->model('task_user')
			->table('`order`','left join','order.orderno=task_user.orderno')
			->where('task_user.o_orderno=? and order.pay_status=? and order.status=?',[$main_order_orderno,1,1])
			->find('count(*)');
			$pay_success_num = isset($pay_success_num['count(*)']) && !empty($pay_success_num['count(*)'])?$pay_success_num['count(*)']:0;
			
			$main_order = $this->model('order')
			->table('task_user','left join','task_user.orderno=order.orderno')
			->table('task','left join','task_user.tid=task.id')
			->where('task_user.orderno=?',[$main_order_orderno])->find([
				'order.pay_status',
				'order.status',
				'task.teamnum',
				'order.uid',
				'task.score',
				'task_user.status as task_user_status'
			]);
			if ($main_order['pay_status']==1 && $main_order['status']==1)
			{
				$pay_success_num++;
			}
			
			//拼团成功
			if ($main_order['teamnum'] == $pay_success_num && $main_order['task_user_status'] == 0)
			{
				//更改主订单拼团状态
				if(!$this->model('task_user')->where('orderno=?',[$main_order_orderno])->limit(1)->update([
					'status'=>1
				]))
				{
					return false;
				}
				//更改子订单拼团状态
				if(!$this->model('task_user')->where('o_orderno=?',[$main_order_orderno])->update('status',1))
				{
					return false;
				}
				
				//增加用户积分
				if(!$this->model('user')->where('id=?',[$main_order['uid']])->limit(1)->increase('score',$main_order['score']))
				{
					return false;
				}
				
				//vip升级 0-1
				$main_user = $this->model('user')->where('id=?',[$main_order['uid']])->find();
				if ($main_user['score'] >= 100 && $main_user['vip']==0)
				{
					if(!$this->model('user')->where('id=?',[$main_order['uid']])->limit(1)->update('vip',1))
					{
						return false;
					}
					
					//绑定轮询导师
					$uid = $this->model('teacher')->where('turn=?',[1])->select('uid');
					$index = rand(0, count($uid)-1);
					if (!empty($uid))
					{
						if(!$this->model('user')->where('id=?',[$main_order['uid']])->limit(1)->update([
							'o_master' => $uid[$index]['uid'],
							'oid'=>$uid[$index]['uid'],
							'invittime' => $_SERVER['REQUEST_TIME']
						]))
						{
							return false;
						}
					}
				}
			}
			return true;
		}
		else
		{
			$order = $this->model('order')->where('orderno=?',[$orderno])->find();
			if (!empty($order))
			{
				$uid = $order['uid'];
				$user = $this->model('user')->where('id=?',[$uid])->find();
				//分配佣金的前提是，购买用户必须是V0
				if (!empty($user) && $user['vip'] == 0)
				{
					$supUserId = $user['oid'];
					if (!empty($supUserId))
					{
						$supUser = $this->model('user')->where('id=?',[$supUserId])->find();
						if (!empty($supUser) && $supUser['vip']!=0)
						{
							$orderHelper = new \application\helper\order();
							$product = $orderHelper->getProduct($orderno);
							//计算商品的差价
							$a_money = 0;
							foreach ($product as $p)
							{
								switch ($supUser['vip'])
								{
									case 1:
										if($p['price'] > $p['v1price'])
										{
											$a_money += ($p['price'] - $p['v1price']) * $p['num'];
										}
										break;
									case 2:
										if($p['price'] > $p['v2price'])
										{
											$a_money += ($p['price'] - $p['v2price']) * $p['num'];
										}
								}
							}
							
							$a_money = floatval(number_format($a_money,2,'.',''));
							
							if (!empty($a_money))
							{
								if(!$this->model('user')->where('id=?',[$supUserId])->limit(1)->increase('money',$a_money))
								{
									return false;
								}
								if(!$this->model('swift')->insert([
									'uid' => $supUserId,
									'money' => $a_money,
									'type' => 0,
									'time' => $_SERVER['REQUEST_TIME'],
									'note' => '一级销售分成',
									'source' => 2,
									'order_type' => 'order',
									'orderno' => $orderno
								]))
								{
									return false;
								}
							}
							
							$supSupUserId = $supUser['oid'];
							if (!empty($supSupUserId))
							{
								$b_money = floatval(number_format($a_money * 0.15,2,'.',''));
								$supSupUser = $this->model('user')->where('id=?',[$supSupUserId])->find();
								if (!empty($b_money) && !empty($supSupUser))
								{
									if(!$this->model('user')->where('id=?',[$supSupUserId])->limit(1)->increase('money',$b_money))
									{
										return false;
									}
									if(!$this->model('swift')->insert([
										'uid' => $supSupUserId,
										'money' => $b_money,
										'type' => 0,
										'time' => $_SERVER['REQUEST_TIME'],
										'note' => '二级销售分成',
										'source' => 3,
										'order_type' => 'order',
										'orderno' => $orderno
									]))
									{
										return false;
									}
								}
							}
							
							$masterId = $user['o_master'];
							if (!empty($masterId))
							{
								$master_money = floatval(number_format($a_money * 0.1,2,'.',''));
								if (!empty($master_money))
								{
									if (!$this->model('user')->where('id=?',[$masterId])->limit(1)->increase('money',$master_money))
									{
										return flase;
									}
									if(!$this->model('swift')->insert([
										'uid' => $masterId,
										'money' => $master_money,
										'type' => 0,
										'time' => $_SERVER['REQUEST_TIME'],
										'note' => '导师销售分成',
										'source' => 4,
										'order_type' => 'order',
										'orderno' => $orderno
									]))
									{
										return false;
									}
								}
							}
							
							return true;
						}
					}
				}
				
			}
			return true;
		}
	}
}