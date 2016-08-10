<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
use application\helper\sms;
class drawal extends ajax
{
	function passHandle()
	{
		$id = $this->post('id');
		if($this->model('drawal')->where('id=?',[$id])->limit(1)->update([
			'pass'=>1,
			'passtime'=>$_SERVER['REQUEST_TIME']
		]))
		{


			$drawal = $this->model('drawal')
				->table('user','left join','drawal.uid=user.id')
				->where('drawal.id=?',[$id])
				->limit(1)
				->find([
						'drawal.money as money',
						'user.telephone as telephone'
				]);
			$telephone = $drawal['telephone'];
			
			$uid = $this->model('system')->get('uid','sms');
			$key = $this->model('system')->get('key','sms');
			$sign = $this->model('system')->get('sign','sms');

			$sms = new sms($uid, $key, $sign);
			$content = "您的提现金额".$drawal['money']."已打入你的账户，请查收";
			$sms->send($telephone, $content);






			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	function create()
	{
		$bankcard = $this->post('bankcard');
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		if(empty($uid))
		{
			return new json(json::NOT_LOGIN);
		}
		
		$money = $this->post('money',0,'floatval');
		if (empty($money) || $money<100)
		{
			return new json(json::PARAMETER_ERROR,'提现金额太低');
		}
		
		$userMoney = $this->model('user')->where('id=?',[$uid])->find('money');
		if ($userMoney['money'] >= $money)
		{
			$data = [
				'uid' => $uid,
				'money' => $money,
				'pass' => 0,
				'passtime' => 0,
				'createtime' => $_SERVER['REQUEST_TIME'],
				'bankcard' => $bankcard,
			];
				
			$this->model('drawal')->transaction();
			//添加提现记录
			if($this->model('drawal')->insert($data))
			{
				//添加流水记录
				if(!$this->model('swift')->insert([
					'uid' => $uid,
					'money' => $money,
					'type' => 1,
					'time' => $_SERVER['REQUEST_TIME'],
					'note' => '提现申请,冻结余额',
					'source' => 1,
				]))
				{
					$this->model('drawal')->rollback();
					return new json(json::PARAMETER_ERROR,'流水记录失败');
				}
	
				//减少用户余额
				if(!$this->model('user')->where('id=?',[$uid])->increase('money',-$money))
				{
					$this->model('drawal')->rollback();
					return new json(json::PARAMETER_ERROR,'系统繁忙');
				}
	
				$this->model('drawal')->commit();
				return new json(json::OK);
			}
			$this->model('drawal')->rollback();
			return new json(json::PARAMETER_ERROR);
		}
		return new json(json::PARAMETER_ERROR,'超过账户余额');
	}
	
	function info()
	{
		$id = $this->post('id');
		$drawal = $this->model('drawal')->where('id=?',[$id])->find();
		if (isset($drawal['bankcard']) && !empty($drawal['bankcard']))
		{
			$bankcard = $this->model('bankcard')->where('id=?',[$drawal['bankcard']])->find();
			return new json(json::OK,NULL,$bankcard);
		}
	}
}