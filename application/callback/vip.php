<?php
namespace application\callback;
use system\core\base;
class vip extends base
{
	function payedOrder($orderno)
	{
		$vip_order = $this->model('vip_order')->where('orderno=?',[$orderno])->find();
		if(!empty($vip_order))
		{
			$userId = $vip_order['uid'];
			$user = $this->model('user')->where('id=?',[$userId])->find();
			if (!empty($user))
			{
				$supUserId = $user['oid'];
				if (!empty($supUserId))
				{
					$supUser = $this->model('user')->where('id=?',[$supUserId])->find();
					if (!empty($supUser))
					{
						//计算给上级用户的佣金
						if ($supUser['vip'] == 2)
						{
							$totalamount = floatval(number_format($vip_order['payamount'],2,'.',''));
						}
						else if ($supUser['vip']==1)
						{
							if($vip_order['vip_from'] == 0 && $vip_order['vip_to']==1)
							{
								$totalamount = 200;
							}
							else if ($vip_order['vip_from'] == 0 && $vip_order['vip_to'] == 2)
							{
								$totalamount = 200;
							}
							else if ($vip_order['vip_from'] == 1 && $vip_order['vip_to'] == 2)
							{
								$totalamount = 0;
							}
						}
						else
						{
							$totalamount = 0;
						}
						
						//给上级用户佣金
						$calMoney = $totalamount * 0.5;//50%
						if (!empty($calMoney))
						{
							if(!$this->model('user')->where('id=?',[$supUserId])->increase('money',$calMoney))
							{
								echo "一级收益失败";
								return false;
							}
							if(!$this->model('swift')->insert([
								'uid' => $supUserId,
								'money' => $calMoney,
								'type' => 0,
								'time' => $_SERVER['REQUEST_TIME'],
								'note' => '用户升级vip获得一级收益,订单号:'.$orderno,
								'source' => 5,
								'order_type' => 'vip',
								'orderno' => $orderno,
							]))
							{
								echo "一级收益失败";
								return false;
							}
						}
						
						$supSupUserId = intval($supUser['oid']);
						if (!empty($supSupUserId))
						{
							$supSupUser = $this->model('user')->where('id=?',[$supSupUserId])->find();
							if ($supSupUser['vip'] == 2)
							{
								$totalamount = floatval(number_format($vip_order['payamount'],2,'.',''));
							}
							else if ($supSupUser['vip']==1)
							{
								if($vip_order['vip_from'] == 0 && $vip_order['vip_to']==1)
								{
									$totalamount = 200;
								}
								else if ($vip_order['vip_from'] == 0 && $vip_order['vip_to'] == 2)
								{
									$totalamount = 200;
								}
								else if ($vip_order['vip_from'] == 1 && $vip_order['vip_to'] == 2)
								{
									$totalamount = 0;
								}
							}
							else
							{
								$totalamount = 0;
							}
							//给上上级的佣金
							$calMoney = $totalamount * 0.1;//10%
							if (!empty($calMoney))
							{
								if(!$this->model('user')->where('id=?',[$supSupUserId])->increase('money',$calMoney))
								{
									echo "二级收益失败";
									return false;
								}
								if(!$this->model('swift')->insert([
									'uid' => $supSupUserId,
									'money' => $calMoney,
									'type' => 0,
									'time' => $_SERVER['REQUEST_TIME'],
									'note' => '用户升级vip获得二级收益,订单号:'.$orderno,
									'source' => 6,
									'order_type' => 'vip',
									'orderno' => $orderno,
								]))
								{
									echo "二级收益失败";
									return false;
								}
							}
						}
						
						
						//给导师的收益
						$masterId = intval($user['o_master']);
						if (!empty($masterId))
						{
							$calMoney = floatval(number_format($vip_order['payamount'] * 0.07,2,'.',''));
							if (!empty($calMoney))
							{
								
								if(!$this->model('user')->where('id=?',[$masterId])->increase('money',$calMoney))
								{
									echo "导师收益失败";
									return false;
								}
								if(!$this->model('swift')->insert([
									'uid' => $masterId,
									'money' => $calMoney,
									'type' => 0,
									'time' => $_SERVER['REQUEST_TIME'],
									'note' => '用户升级vip获得导师收益,订单号:'.$orderno,
									'source' => 7,
									'order_type' => 'vip',
									'orderno' => $orderno,
								]))
								{
									echo "导师收益失败";
									return false;
								}
							}
						}
						
						//假如发展了10个v2自动升级到导师
						if ($supUser['master'] == 0 && $supUser['vip']==2 && $vip_order['vip_to'] ==2)
						{
							$vip2userNum = $this->model('user')->where('vip=? and oid=?',[2,$supUserId])->find('count(*)');
							$num = isset($vip2userNum['count(*)']) && !empty($vip2userNum['count(*)'])?$vip2userNum['count(*)']:0;
							if ($num >= 10)
							{
								if(!$this->model('user')->where('id=?',[$supUserId])->limit(1)->update('master',1))
								{
									echo "升级导师失败";
									return false;
								}
							}
						}
					}
				}
				else
				{
					//分配轮询导师
					$uid = $this->model('teacher')->where('turn=?',[1])->select('uid');
					$index = rand(0, count($uid)-1);
					if (!empty($uid))
					{
						if(!$this->model('user')->where('id=?',[$userId])->limit(1)->update([
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
		}
		return true;
	}
}