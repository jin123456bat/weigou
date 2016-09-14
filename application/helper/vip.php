<?php
namespace application\helper;
use system\core\base;
use system\core\random;
class vip extends base
{
	/**
	 * 创建订单数据
	 * @param unknown $uid
	 * @param unknown $price
	 * @param unknown $from
	 * @param unknown $to
	 * @return multitype:NULL number string unknown 
	 */
	function createOrderData($uid,$price,$from,$to)
	{
		do{
			$orderno = date('ymdHis').$uid.random::word(4);
		}
		while(!empty($this->model('vip_order')->where('orderno=?',[$orderno])->find()));
		
		return [
			'id' => NULL,
			'uid' => $uid,
			'orderno' => $orderno,
			'createtime' => $_SERVER['REQUEST_TIME'],
			'paytime' => 0,
			'paynumber' => '',
			'paytype' => '',
			'payprice' => 0,
			'payamount' => $price,
			'vip_from' => $from,
			'vip_to' => $to
		];
	}
	
	/**
	 * 完成支付订单
	 */
	function payedOrder($orderno,$pay_type,$pay_number,$pay_money,array $options = [])
	{
		$vip_order = $this->model('vip_order')->where('orderno=?',[$orderno])->find();
		if (!empty($vip_order))
		{
			$user = $this->model('user')->where('id=?',[$vip_order['uid']])->find();
			if ($user['vip'] == $vip_order['vip_from'] && $pay_money == $vip_order['payamount'])
			{
				$data = [
					'paytype' => $pay_type,
					'paynumber' => $pay_number,
					'payprice' => $pay_money,
					'paytime' => $_SERVER['REQUEST_TIME'],
				];
				if(!empty($options))
					$data = array_merge($data,$options);
				
				$this->model('vip_order')->transaction();
				
				if($this->model('vip_order')->where('orderno=?',[$orderno])->limit(1)->update($data))
				{
					if($this->model('user')->where('id=?',[$vip_order['uid']])->update('vip',$vip_order['vip_to']))
					{
						$class_name = '\application\callback\vip';
						if (class_exists($class_name,true) && method_exists($class_name, 'payedOrder') && is_callable([$class_name,'payedOrder']))
						{
							if(!call_user_func([new $class_name(),'payedOrder'],$orderno))
							{
								$this->model('vip_order')->rollback();
								return false;
							}
						}
						$this->model('vip_order')->commit();
						return true;
					}
				}
				$this->model('vip_order')->rollback();
			}
		}
		return false;
	}
}