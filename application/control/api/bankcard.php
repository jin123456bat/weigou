<?php
namespace application\control\api;
use application\message\json;
class bankcard extends common
{
	private $_response;
	
	private $_uid;
	
	function __construct()
	{
		parent::__construct();
		$this->_response = $this->init();
		
		$userHelper = new \application\helper\user();
		$this->_uid = $userHelper->isLogin();
	}
	
	/**
	 * 添加提现账户
	 */
	function create()
	{
		if (!empty($this->_response))
			return $this->_response;
		if (empty($this->_uid))
			return new json(json::NOT_LOGIN);
		$type = $this->data('type','alipay');
		$account = $this->data('account',NULL);
		if (empty($account)) {
			return new json(json::PARAMETER_ERROR,'账户不能为空');
		}
		$name = $this->data('name',NULL);
		if (empty($name))
			return new json(json::PARAMETER_ERROR,'户名不能为空');
		
		switch($type)
		{
			case 'alipay':
				$alipay = [
					'id' => NULL,
					'uid' => $this->_uid,
					'type' => 'alipay',
					'account' => $account,
					'name' => $name,
					'bank' => NULL,
					'subbank' => NULL,
					'province' => NULL,
					'city' => NULL,
					'createtime' => $_SERVER['REQUEST_TIME'],
					'isdelete'=>0,
					'modifytime' => $_SERVER['REQUEST_TIME'],
					'deletetime' => 0
				];
				if($this->model('bankcard')->insert($alipay))
				{
					return new json(json::OK);
				}
				return new json(json::PARAMETER_ERROR,'添加失败');
				break;
			case 'bank':
				$bank = $this->data('bank');
				if (empty($bank))
					return new json(json::PARAMETER_ERROR,'开户行不能为空');
				$subbank = $this->data('subbank');
				if (empty($subbank))
					return new json(json::PARAMETER_ERROR,'开户支行不能为空');
				$province = $this->data('province');
				$city = $this->data('city');
				if (empty($province))
					return new json(json::PARAMETER_ERROR,'开户省份不能为空');
				if (empty($city))
					return new json(json::PARAMETER_ERROR,'开户城市不能为空');
				$bankcard = [
					'id' => NULL,
					'uid' => $this->_uid,
					'type' => 'bank',
					'account' => $account,
					'name' => $name,
					'bank' => $bank,
					'subbank' => $subbank,
					'province' => $province,
					'city' => $city,
					'createtime' => $_SERVER['REQUEST_TIME'],
					'isdelete'=>0,
					'modifytime' => $_SERVER['REQUEST_TIME'],
					'deletetime' => 0
				];
				if($this->model('bankcard')->insert($bankcard))
				{
					return new json(json::OK);
				}
				return new json(json::PARAMETER_ERROR,'添加失败');
				break;
		}
	}
	
	/**
	 * 保存修改的提现账户
	 * @return \application\message\json
	 */
	function save()
	{
		if (!empty($this->_response))
			return $this->_response;
		if (empty($this->_uid))
			return new json(json::NOT_LOGIN);
		
		$id = $this->data('id');
		if(empty($id))
			return new json(json::PARAMETER_ERROR,'账户id错误');
		
		$account = $this->data('account');
		if (empty($account))
			return new json(json::PARAMETER_ERROR,'账户不能为空');
		$name = $this->data('name');
		if (empty($name))
			return new json(json::PARAMETER_ERROR,'户名不能为空');
		
		$type = $this->data('type','alipay');
		switch($type)
		{
			case 'alipay':
				$alipay = [
					'type' => 'alipay',
					'account' => $account,
					'name' => $name,
					'bank' => NULL,
					'subbank' => NULL,
					'province' => NULL,
					'city' => NULL,
					'modifytime' => $_SERVER['REQUEST_TIME']
				];
				if($this->model('bankcard')->where('id=? and uid=?',[$id,$this->_uid])->update($alipay))
				{
					return new json(json::OK);
				}
				break;
			case 'bank':
				$city = $this->data('city');
				if (empty($city))
					return new json(json::PARAMETER_ERROR,'请选择城市');
				$province = $this->data('province');
				if (empty($province))
					return new json(json::PARAMETER_ERROR,'请选择省份');
				$subbank = $this->data('subbank');
				if (empty($subbank))
					return new json(json::PARAMETER_ERROR,'请填写支行名称');
				$bank = $this->data('bank');
				if (empty($bank))
					return new json(json::PARAMETER_ERROR,'请填写银行名称');
				$bankcard = [
					'type' => 'bank',
					'account' => $account,
					'name' => $name,
					'bank' => $bank,
					'subbank' => $subbank,
					'province' => $province,
					'city' => $city,
					'modifytime' => $_SERVER['REQUEST_TIME'],
				];
				if ($this->model('bankcard')->where('id=? and uid=?',[$id,$this->_uid])->update($bankcard))
				{
					return new json(json::OK);
				}
				return new json(json::PARAMETER_ERROR);
				break;
		}
		return new json(json::PARAMETER_ERROR,'type参数错误');
	}
	
	/**
	 * 删除提现账户
	 */
	function remove()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		if (empty($this->_uid))
			return new json(json::NOT_LOGIN);
		
		$id = $this->data('id');
		if (empty($id))
			return new json(json::PARAMETER_ERROR,'账户id为空');
		
		if($this->model('bankcard')->where('id=? and uid=?',[$id,$this->_uid])->update([
			'isdelete'=>1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	/**
	 * 我的提现账户列表
	 */
	function mylists()
	{
		$userHelper = new \application\helper\user();
		$filter = [
			'uid' => $userHelper->isLogin(),
			'start' => $this->data('start',0),
			'length' => $this->data('length',10),
			'isdelete' => 0,
			'parameter' => [
				'bankcard.id',
				'bankcard.type',
				'bankcard.account',
				'bankcard.name',
				'bankcard.bank',
				'bankcard.subbank',
				'province.id as province_id',
				'city.id as city_id',
				'province.name as province',
				'city.name as city'
			],
		];
		$bankcard = $this->model('bankcard')->fetchAll($filter);
		
		$filter['parameter'] = 'count(*)';
		unset($filter['start']);
		unset($filter['length']);
		$total = $this->model('bankcard')->fetch($filter);
		
		$bankcardReturnModel = [
			'current' => count($bankcard),
			'total' => isset($total[0]['count(*)'])?$total[0]['count(*)']:0,
			'start' => $this->data('start',0),
			'length' => $this->data('length',10),
			'data' => $bankcard,
		];
		
		return new json(json::OK,NULL,$bankcardReturnModel);
	}
}