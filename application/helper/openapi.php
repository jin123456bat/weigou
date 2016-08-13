<?php
namespace application\helper;
use system\core\base;
use system\core\random;
use system\core\http;
class openapi extends base
{
	private $_debug = false;
	
	private $_url = 'http://openapi.dchnu.com/open.api';
	
	private $_debug_url = 'http://openapitest.dchnu.com/open.api';
	
	private $_version = '1.0';

	private $_appid = '1026';
	
	private $_appsecret = '5253f2ed-9f23-4083-905f-f9b40f711977';
	
	private $_format = 'json';
	
	private $_repeat_id;
	
	function __construct()
	{
		$this->_repeat_id = NULL;
	}
	
	/**
	 * 设置调用接口的时候是否为重复调用
	 */
	function setRepeat($repeat_id)
	{
		$this->_repeat_id = $repeat_id;
	}
	
	/**
	 * 获取接口请求地址
	 * @return string
	 */
	function getUrl()
	{
		if ($this->_debug)
		{
			return $this->_debug_url;
		}
		return $this->_url;
	}
	
	/**
	 * 签名算法
	 */
	private function sign($data)
	{
		//过滤参数
		$array = [];
		foreach ($data as $key => $value)
		{
			if ($key != 'sign' && $key != 'appSecret' && $key != 'data')
			{
				$array[$key] = $value;
			}
		}
		
		//排序
		ksort($array);
		reset($array);
		
		$string = '';
		//拼接key => value
		foreach ($array as $key => $value)
		{
			$string .= $key.'='.$value.'&';
		}
		
		//去掉最后一个&符号
		$string = rtrim($string,'&');
		$string = strtolower(urlencode($string).$this->_appsecret);
		$string = md5($string);
		return $string;
	}
	
	/**
	 * 创建真实提交参数
	 * @param unknown $data
	 */
	function createParameter($method,$data)
	{
		$_data = [
			'method' => $method,
			'version' => $this->_version,
			'appid' => $this->_appid,
			'format' => $this->_format,
			'tstamp' => date('YmdHis'),
			'nonce' => random::number(12),
		];
		$_data['sign'] = $this->sign($_data);
		$_data['data'] = $data;
		return $_data;
	}
	
	/**
	 * 获取分销商平台ID
	 */
	private function getSourcePlatform()
	{
		return 1026;
	}
	
	/**
	 * 当接口处理成功
	 * @param string $method 调用的方法名称
	 * @param array $request 调用的参数数组
	 * @param string $response 接口响应内容
	 */
	function success($method,$request,$response)
	{
		if (!empty($this->_repeat_id))
		{
			$this->model('api_log')->where('id=?',[$this->_repeat_id])->limit(1)->update([
				'success' => 1,
			]);
		}
	}
	
	/**
	 * 当接口处理失败
	 * @param string $method 调用的方法名称
	 * @param array $request 调用的参数数组
	 * @param string $response 接口响应内容
	 */
	function failed($method,$request,$response)
	{
		if (empty($this->_repeat_id))
		{
			$this->model('api_log')->insert([
				'name' => 'openapi',
				'classname' => __CLASS__,
				'url' => $this->getUrl(),
				'parameter' => json_encode($request),
				'method' => $method,
				'createtime' => $_SERVER['REQUEST_TIME'],
				'lasttime' => $_SERVER['REQUEST_TIME'],
				'times' => 1,
				'success' => 0,
				'response' => $response,
			]);
		}
		else
		{
			$this->model('api_log')->where('id=?',[$this->_repeat_id])->limit(1)->update([
				'lasttime' => $_SERVER['REQUEST_TIME'],
				'response' => $response,
			]);
			$this->model('api_log')->where('id=?',[$this->_repeat_id])->increase('times',1);
		}
	}

	/**
	 * 获取订单信息
	 * @param unknown $orderno
	 */
	function GetOrderRoute($orderno)
	{
		if (is_array($orderno))
		{
			$orderno = implode('|', $orderno);
		}
		
		$parameter = [
			'ExternalOrder' => $orderno,
			'SourcePlatform' => $this->getSourcePlatform(),
		];
		$response = http::get($this->getUrl().'?'.http_build_query($parameter));
		return $response;
	}
	
	/**
	 * 订单创建
	 */
	function OrderCreate($orderno)
	{
		return true;
		
		$order = $this->model('order')
		->table('address','left join','address.id=order.address')
		->table('province','left join','address.province=province.id')
		->table('city','left join','address.city=city.id')
		->table('county','left join','address.county=county.id')
		->table('user','left join','user.id=order.uid')
		->where('order.orderno=?',[$orderno])
		->find([
			'province.name as province',
			'city.name as city',
			'county.name as county',
			'address.address',
			'address.telephone as mobile',
			'address.name',
			'user.name as username',
			'user.telephone',
			'address.identify',
			'order.orderamount',
			'order.discount',
			'order.feeamount',
			'order.taxamount',
			'(select order_package.ship_type from order_package where order_package.orderno=order.orderno limit 1) as ship_type',//配送方式
			'(select order_package.ship_number from order_package where order_package.orderno=order.orderno limit 1) as ship_number',//配送编号
			'order.pay_type',
			'address.zcode',
			'order.pay_number',
			'concat(order.note,",",order.msg) as remark',
		]);
		
		//商品
		$product = $this->model('order_package')
		->table('order_product','left join','order_package.id=order_product.package_id')
		->table('product','left join','product.id=order_product.pid')
		->where('order_package.orderno=?',[$orderno])
		//->where('order_product.refund=?',[0])
		->select([
			'order_product.price as UnitPrice',
			'product.name as CommodityName',
			'product.barcode as CommoditySN',
			'order_product.num as CommodityAmount',
			'order_product.tax as Tax',
		]);
		
		
		//配送方式
		$ship = $this->model('ship')->where('code=?',[$order['ship_type']])->find();
		$ship_type = $ship['openapi'];
		
		//支付方式
		switch ($order['pay_type'])
		{
			case 'wechat':$pay_type = 114;
			case 'alipay':$pay_type = 112;
			default:
				$pay_type = '';
		}
		
		$data = [
			'SourcePlatform' => $this->getSourcePlatform(),
			'ExternalOrder' => $orderno,//
			'ConsigneeProvince' => $order['province'],
			'ConsigneeCity' => $order['city'],
			'ConsigneeDistrict' => $order['county'],
			'ConsigneeAddress' => $order['address'],
			'ConsigneeMobile' => $order['mobile'],
			'ConigneeName' => $order['name'],
			'PayerName' => $order['name'],
			'PayerMobile' => $order['telephone'],
			'PayerIdCardType' => 1,
			'PayerIdCardNumber' => $order['identify'],
			'TotalAmount' => $order['orderamount'],
			'Discount' => $order['discount'],
			'ShippingFee' => $order['feeamount'],
			'Tax' => $order['taxamount'],
			'ShippingType' => $ship_type,
			'PayType' => $pay_type,
			'ConsigneePostCode' => $order['zcode'],
			'BatchNumber' => $order['pay_number'],
			'Remark' => $order['remark'],
			'ItemList' => $product,
		];
		$data = $this->createParameter(__FUNCTION__, [$data]);
		$response_string = $this->postRequest($data);
		$response_array = json_decode($response_string,true);
		if (isset($response_array['code']) && $response_array['code'] == 0)
		{
			$this->success(__FUNCTION__,['orderno' => $orderno],$response_string);
			return $response_array;
		}
		else
		{
			$this->failed(__FUNCTION__,['orderno' => $orderno],$response_string);
			return $response_array;
		}
	}
	
	/**
	 * 向接口提交post请求
	 */
	private function postRequest($data)
	{
		$data = json_encode($data,JSON_UNESCAPED_UNICODE);
		return http::post($this->getUrl(), $data);
	}
}
