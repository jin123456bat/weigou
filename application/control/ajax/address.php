<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
use application\helper\idcard;
class address extends ajax
{
	function setHost()
	{
		$id = $this->post('id');
		if(empty($id))
			return new json(json::PARAMETER_ERROR);
		
		$address = $this->model('address')->where('id=? and isdelete=?',[$id,0])->find();
		if(empty($address))
			return new json(json::PARAMETER_ERROR);
		
		//取消所有的默认
		$this->model('address')->where('uid=?',[$address['uid']])->update('host',0);
		
		if($this->model('address')->where('id=?',[$id])->limit(1)->update('host',1))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	/**
	 * 添加收货地址
	 */
	function create()
	{
		$name = $this->post('name','');
		if (empty($name))
			return new json(json::PARAMETER_ERROR,'收货人姓名不能为空');
	
		$telephone = $this->post('telephone','','telephone');
		if (empty($telephone))
			return new json(json::PARAMETER_ERROR,'收货人手机号不能为空');
	
		$province = $this->post('province',NULL);
		if (empty($province))
			return new json(json::PARAMETER_ERROR,'请选择省份');
	
		$city = $this->post('city',NULL);
		if (empty($city))
			return new json(json::PARAMETER_ERROR,'请选择城市');
	
		$county = $this->post('county',NULL);
		if (empty($county))
		{
		    return new json(json::PARAMETER_ERROR,'请选择地区');
			//$county = NULL;
		}
	
		$address = $this->post('address','');
		if (empty($address))
			return new json(json::PARAMETER_ERROR,'请填写详细收货地址');
	
		$identify = $this->post('identify','');
		if (strlen($identify) != 15 && strlen($identify) !=18 && strlen($identify) != 0)
			return new json(json::PARAMETER_ERROR,'身份证号码必须是15或者18位');
	
		$zcode = $this->post('zcode','');
		if (strlen($zcode) != 6 && strlen($zcode) != 0)
			return new json(json::PARAMETER_ERROR,'邮编必须是6为数组');
	
		$host = $this->post('host',0);
	
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		if (empty($uid))
			return new json(json::NOT_LOGIN);
	
		if (!empty($identify))
		{
			if (idcard::auth($name, $identify) == 0)
			{
				return new json(json::PARAMETER_ERROR,'用户名和身份证号码不匹配');
			}
		}
		
		if ($host==1)
		{
			$this->model('address')->where('uid=?',[$uid])->update('host',0);
		}
	
		$address = $userHelper->createUserAddress($uid, $province, $city, $county, $address, $name, $telephone);
		$address['identify'] = $identify;
		$address['zcode'] = $zcode;
		$address['host'] = $host;
	
		if($this->model('address')->insert($address))
		{
			$id = $this->model('address')->lastInsertId();
				
			$address = $this->model('address')
			->table('province','left join','province.id=address.province')
			->table('city','left join','city.id=address.city')
			->table('county','left join','address.county=county.id')
			->where('address.id=?',[$id])->find([
				'address.id',
				'address.province as province_id',
				'address.city as city_id',
				'address.county as county_id',
				'province.name as province',
				'city.name as city',
				'county.name as county',
				'address.name',
				'address.telephone',
				'address.zcode',
				'address.identify',
				'address.host',
				'address.address',
			]);
				
			return new json(json::OK,NULL,$address);
		}
		return new json(json::PARAMETER_ERROR,'创建失败');
	}
	
	function save()
	{
		$id = $this->post('id');
		if (empty($id))
			return new json(json::PARAMETER_ERROR,'地址参数错误');
	
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		if(empty($uid))
			return new json(json::NOT_LOGIN);
	
		$name = $this->post('name');
		if(empty($name))
			return new json(json::PARAMETER_ERROR,'请填写收获人姓名');
	
		$province = $this->post('province');
		if (empty($province))
			return new json(json::PARAMETER_ERROR,'请选择省份');
	
		$city = $this->post('city');
		if(empty($city))
			return new json(json::PARAMETER_ERROR,'请选择城市');
	
		$county = $this->post('county',NULL,'intval');
		if (empty($county))
		{
		    return new json(json::PARAMETER_ERROR,'请选择地区');
			//$county = NULL;
		}
	
		$telephone = $this->post('telephone','','telephone');
		if (empty($telephone))
			return new json(json::PARAMETER_ERROR,'请填写手机号码');
	
		$zcode = $this->post('zocde','');
		if (strlen($zcode)!=6 && strlen($zcode)!=0)
			return new json(json::PARAMETER_ERROR,'邮编必须是6位');
	
		$address = $this->post('address','');
		if (empty($address))
			return new json(json::PARAMETER_ERROR,'请填写详细地址');
		
		$identify = $this->post('identify','');
		if (strlen($identify)!=15 && strlen($identify)!=18 && strlen($identify)!=0)
			return new json(json::PARAMETER_ERROR,'身份证号码必须是15或者18位');
	
		if (!empty($identify))
		{
			if (idcard::auth($name, $identify) == 0)
			{
				return new json(json::PARAMETER_ERROR,'用户名和身份证号码不匹配');
			}
		}
		
		$host = $this->post('host',0);
		if ($host==1)
		{
			$this->model('address')->where('uid=?',[$uid])->update('host',0);
		}
		if($this->model('address')->where('id=? and uid=?',[$id,$uid])->update([
			'name' => $name,
			'telephone' => $telephone,
			'identify' => $identify,
			'province' => $province,
			'county' => $county,
			'city' => $city,
			'address' => $address,
			'zcode' => $zcode,
			'host' => $host,
			'modifytime' => $_SERVER['REQUEST_TIME']
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	
	/**
	 * 删除收货地址
	 */
	function remove()
	{
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		if (empty($uid))
			return new json(json::NOT_LOGIN);
	
		$id = $this->post('id',NULL);
		if($this->model('address')->where('id=? and uid=? and isdelete=?',[$id,$uid,0])->update([
			'isdelete' => 1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR,'该地址不存在');
	}
	
	function mylists()
	{
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		if (empty($uid))
		{
			return new json(json::NOT_LOGIN);
		}
		$filter = [
			'uid' => $uid,
			'isdelete' => 0,
			'parameter' => 'address.id,
							address.name,
							address.telephone,
							address.zcode,
							address.identify,
							address.host,
							address.address,
							province.id as province_id,
							city.id as city_id,
							county.id as county_id,
							province.name as province,
							city.name as city,
							county.name as county'
		];
		$address = $this->model('address')->fetchAll($filter);
		return new json(json::OK,NULL,$address);
	}
}