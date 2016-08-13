<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class bankcard extends ajax
{
	function create()
	{
		$type = $this->post('type','alipay');
		$account = $this->post('account',NULL);
		if (empty($account)) {
			return new json(json::PARAMETER_ERROR,'账户不能为空');
		}
		$name = $this->post('name',NULL);
		if (empty($name))
			return new json(json::PARAMETER_ERROR,'户名不能为空');
		
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		
		switch($type)
		{
			case 'alipay':
				$alipay = [
					'id' => NULL,
					'uid' => $uid,
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
					return new json(json::OK,NULL,$this->model('bankcard')->lastInsertId());
				}
				return new json(json::PARAMETER_ERROR,'添加失败');
				break;
			case 'bank':
				$bank = $this->post('bank');
				if (empty($bank))
					return new json(json::PARAMETER_ERROR,'开户行不能为空');
				$subbank = $this->post('subbank');
				if (empty($subbank))
					return new json(json::PARAMETER_ERROR,'开户支行不能为空');
				$province = $this->post('province');
				$city = $this->post('city');
				if (empty($province))
					return new json(json::PARAMETER_ERROR,'开户省份不能为空');
				if (empty($city))
					return new json(json::PARAMETER_ERROR,'开户城市不能为空');
				$bankcard = [
					'id' => NULL,
					'uid' => $uid,
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
					return new json(json::OK,NULL,$this->model('bankcard')->lastInsertId());
				}
				return new json(json::PARAMETER_ERROR,'添加失败');
				break;
		}
	}
	
	function save()
	{
		$id = $this->post('id');
		if(!empty($id))
		{
			$drawal = $this->model('drawal')->where('id=?',[$id])->find();
			$bankcard = $drawal['bankcard'];
			if (!empty($bankcard))
			{
				$type = $this->post('type','alipay');
				$account = $this->post('account');
				if (empty($account))
				{
					return new json(json::PARAMETER_ERROR,'请填写账户');
				}
				$name = $this->post('name');
				if (empty($name))
				{
					return new json(json::PARAMETER_ERROR,'请填写收款人姓名');
				}
				
				$bank = $this->post('bank');
				if (empty($bank) && $type == 'bank')
				{
					return new json(json::PARAMETER_ERROR,'请选择开户行');
				}
				if ($type == 'alipay')
				{
					$bank = NULL;
				}
				
				$subbank = $this->post('subbank');
				if (empty($subbank) && $type=='bank')
				{
					return new json(json::PARAMETER_ERROR,'请填写开户支行');
				}
				if ($type == 'alipay')
				{
					$subbank = NULL;
				}
				
				$province = $this->post('province');
				if (empty($province) && $type=='bank')
				{
					return new json(json::PARAMETER_ERROR,'请选择开户省份');
				}
				if ($type == 'alipay')
				{
					$province = NULL;
				}
				
				$city = $this->post('city');
				if (empty($city) && $type=='bank')
				{
					return new json(json::PARAMETER_ERROR,'请选择开户省份');
				}
				if ($type == 'alipay')
				{
					$city = NULL;
				}
				if($this->model('bankcard')->where('id=?',[$bankcard])->limit(1)->update([
					'type' => $type,
					'account' => $account,
					'bank'=> $bank,
					'province' => $province,
					'city'=>$city,
					'subbank'=>$subbank,
					'modifytime'=>$_SERVER['REQUEST_TIME']
				]))
				{
					return new json(json::OK);
				}
				else
				{
					return new json(json::PARAMETER_ERROR,'修改失败');
				}
			}
		}
	}
}