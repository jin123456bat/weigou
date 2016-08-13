<?php
namespace application\control\api;
use application\message\json;
use application\helper\user;
use application\helper\idcard;
class address extends common
{
	private $_response;
	
	function __construct()
	{
		parent::__construct();
		$this->_response = $this->init();
	}
	
	/**
	 * 获得用户的默认收货地址
	 */
	function getHost()
	{
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		if(empty($uid))
			return new json(json::NOT_LOGIN);
		
		$filter = [
			'length' => 1,
			'uid' => $uid,
			'sort' => [['host','desc'],['id','desc']],
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
		if (empty($address))
		{
			return new json(json::OK,NULL,[]);
		}
		else
		{
			return new json(json::OK,NULL,$address[0]);
		}
	}
	
	/**
	 * 添加收货地址
	 */
	function create()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		$name = $this->data('name','');
		if (empty($name))
			return new json(json::PARAMETER_ERROR,'收货人姓名不能为空');
		
		$telephone = $this->data('telephone','','telephone');
		if (empty($telephone))
			return new json(json::PARAMETER_ERROR,'收货人手机号不能为空');
		
		$province = $this->data('province',NULL);
		if (empty($province))
			return new json(json::PARAMETER_ERROR,'请选择省份');
		
		$city = $this->data('city',NULL);
		if (empty($city))
			return new json(json::PARAMETER_ERROR,'请选择城市');
		
		$county = $this->data('county',NULL);
		if (empty($county))
		{
			//$county = NULL;
			return new json(json::PARAMETER_ERROR,'请选择地区');
		}
		
		$address = $this->data('address','');
		if (empty($address))
			return new json(json::PARAMETER_ERROR,'请填写详细收货地址');
		
		$identify = $this->data('identify','');
		if (strlen($identify) != 15 && strlen($identify) !=18 && strlen($identify) != 0)
			return new json(json::PARAMETER_ERROR,'身份证号码必须是15或者18位');
		
		$zcode = $this->data('zcode','');
		if (strlen($zcode) != 6 && strlen($zcode) != 0)
			return new json(json::PARAMETER_ERROR,'邮编必须是6为数组');
		
		if (!empty($identify))
		{
			if (idcard::auth($name, $identify) == 0)
			{
				return new json(json::PARAMETER_ERROR,'用户名和身份证号码不匹配');
			}
		}

		$host = $this->data('host',0);
		
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		if (empty($uid))
			return new json(json::NOT_LOGIN);
		
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
		if (!empty($this->_response))
			return $this->_response;
		
		$id = $this->data('id');
		if (empty($id))
			return new json(json::PARAMETER_ERROR,'地址参数错误');
		
		$userHelper = new user();
		$uid = $userHelper->isLogin();
		if(empty($uid))
			return new json(json::NOT_LOGIN);
		
		$name = $this->data('name');
		if(empty($name))
			return new json(json::PARAMETER_ERROR,'请填写收获人姓名');
		
		$province = $this->data('province');
		if (empty($province))
			return new json(json::PARAMETER_ERROR,'请选择省份');
		
		$city = $this->data('city');
		if(empty($city))
			return new json(json::PARAMETER_ERROR,'请选择城市');
		
		$county = $this->data('county');
		if (empty($county))
		{
		    return new json(json::PARAMETER_ERROR,'请选择地区');
			//$county = NULL;
		}
		
		$telephone = $this->data('telephone','','telephone');
		if (empty($telephone))
			return new json(json::PARAMETER_ERROR,'请填写手机号码');
		
		$zcode = $this->data('zocde','');
		if (strlen($zcode)!=6 && strlen($zcode)!=0)
			return new json(json::PARAMETER_ERROR,'邮编必须是6位');
		
		$address = $this->data('address','');
		if (empty($address))
			return new json(json::PARAMETER_ERROR,'请填写详细地址');
		
		$identify = $this->data('identify','');
		if (strlen($identify)!=15 && strlen($identify)!=18 && strlen($identify)!=0)
			return new json(json::PARAMETER_ERROR,'身份证号码必须是15或者18位');
		
		if (!empty($identify))
		{
			if (idcard::auth($name, $identify) == 0)
			{
				return new json(json::PARAMETER_ERROR,'用户名和身份证号码不匹配');
			}
		}
			
		$host = $this->data('host',0);
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
	 * 设置收货地址为默认收货地址
	 */
	function host()
	{
		$userHelper = new user();
		$uid = $userHelper->isLogin();
		if (empty($uid))
			return new json(json::NOT_LOGIN);
		
		$this->model('address')->where('uid=?',[$uid])->update('host',0);
		$id = $this->data('id');
		if($this->model('address')->where('uid=? and id=?',[$uid,$id])->update([
			'host'=>1,
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
		if (!empty($this->_response))
			return $this->_response;
		
		$userHelper = new user();
		$uid = $userHelper->isLogin();
		if (empty($uid))
			return new json(json::NOT_LOGIN);
		
		$id = $this->data('id',NULL);
		if($this->model('address')->where('id=? and uid=?',[$id,$uid])->update([
			'isdelete' => 1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR,'该地址不存在');
	}
	
	/**
	 * 我的收货地址列表
	 */
	function mylists()
	{
		$userHelper = new \application\helper\user();
		$filter = [
			'uid' => $userHelper->isLogin(),
			'start' => $this->data('start',0),
			'length' => $this->data('length',10),
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
		
		$filter['parameter'] = 'count(*)';
		unset($filter['start']);
		unset($filter['length']);
		$total = $this->model('address')->fetchAll($filter);
		
		$addressReturnModel = [
			'current' => count($address),
			'total' => isset($total[0]['count(*)'])?$total[0]['count(*)']:0,
			'start' => $this->data('start',0),
			'length' => $this->data('length',10),
			'data' => $address,
		];
		
		return new json(json::OK,NULL,$addressReturnModel);
	}
}