<?php
namespace application\control\api;
use application\helper\user;
use application\message\json;
use application\helper\sms;
class drawal extends common
{
	private $_response;
	
	function __construct()
	{
		parent::__construct();
		$this->_response = $this->init();
	
	}
	/**
	 * 创建提现申请
	 */
	function create()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		$bankcard = $this->data('bankcard');
		$userHelper = new user();
		$uid = $userHelper->isLogin();
		if(empty($uid))
		{
			return new json(json::NOT_LOGIN);
		}
		
		$money = $this->data('money',0,'floatval');
		if (empty($money) || $money<100)
			return new json(json::PARAMETER_ERROR,'提现金额太低');
		
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
				
				$username = $this->model('user')->where('id=?',[$uid])->scalar('name');
				
				$uid = $this->model('system')->get('uid', 'sms');
				$key = $this->model('system')->get('key', 'sms');
				$sign = $this->model('system')->get('sign', 'sms');
				$template = $this->model('system')->get('template', 'drawal_admin_notice');
				$telephone = $this->model('system')->get('telephone','drawal_admin_notice');
				
				$content = str_replace(['{money}','{username}'], [$money,$username], $template);
				
				$sms = new sms($uid, $key, $sign);
				$sms->send($telephone, $content);
				
				$this->model('drawal')->commit();
				return new json(json::OK);
			}
			$this->model('drawal')->rollback();
			return new json(json::PARAMETER_ERROR);
		}
		return new json(json::PARAMETER_ERROR,'超过账户余额');
	}
}