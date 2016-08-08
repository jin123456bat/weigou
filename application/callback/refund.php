<?php
namespace application\callback;
use system\core\base;
class refund extends base
{
	/**
	 * 商品退款回调
	 * @param unknown $refundno
	 */
	function product($refundno)
	{
		$refund = $this->model('refund')->where('refundno=?',[$refundno])->find();
		if (empty($refund))
		{
			return false;
		}
		
		$orderno = $refund['orderno'];
		
		$order = $this->model('order')->where('orderno=?',[$orderno])->find();
		if (empty($order))
		{
			return false;
		}
		
		$money = $refund['money'];
		
		//标记退款单完成状态
		if(!$this->model('refund')->where('refundno=?',[$refundno])->limit(1)->update([
			'status' => 1,
			'completetime' => $_SERVER['REQUEST_TIME'],
		]))
		{
			return false;
		}
		
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
				$swift_money = number_format($money / $order['goodsamount'] * $swift['money']);
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
		
		$order_product_id = $refund['order_product_id'];
		if (!empty($order_product_id))
		{
			$product = $this->model('order_product')
			->table('product','left join','product.id=order_product.pid')
			->where('order_product.id=?',[$order_product_id])
			->find('product.name,order_product.num,order_product.content');
			
			$this->model('order_log')->add($orderno,$product['name'].'['.$product['content'].']'.'x'.$product['num'].'退款成功');
		}
		return true;
	}
}