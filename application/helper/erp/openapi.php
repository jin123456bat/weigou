<?php
namespace application\helper\erp;
use system\core\random;
use system\core\http;
use application\helper\erp;
class openapi extends erp
{
	private $_version = '1.0';
	
	private $_format = 'json';

	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 接口是否开启
	 * @return boolean
	 */
	function isOpen()
	{
		return true;
	}
	
	/**
	 * 签名算法
	 */
	public function sign($data)
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
		$string = strtolower(urlencode($string).$this->getAppsecret());
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
			'appid' => $this->getAppid(),
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
		return $this->getParameter('SourcePlatform');
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
		
		$data = '{SourcePlatform:'.$this->getSourcePlatform().',ExternalOrder:'.$orderno.'}';
		
		$data = $this->createParameter(__FUNCTION__, $data);
		
		$query = '';
		foreach ($data as $key => $value)
		{
			$query .= $key.'='.$value.'&';
		}
		$query = rtrim($query,'&');
		
		$data = $this->getUrl().'?'.$query;
		
		$response = http::get($data);
		return $response;
	}
	
	/**
	 * 订单创建
	 */
	function OrderCreate($suborder_id)
	{
		$data = $this->model('suborder_store')
		->table('`order`','left join','order.orderno=suborder_store.main_orderno')
		->table('address','left join','address.id=suborder_store.address')
		->table('province','left join','address.province=province.id')
		->table('city','left join','address.city=city.id')
		->table('county','left join','address.county=county.id')
		->table('user','left join','user.id=suborder_store.uid')
		->where('suborder_store.id=?',[$suborder_id])
		->find([
			$this->getSourcePlatform().' as SourcePlatform',
			'concat(replace(suborder_store.date,"-",""),suborder_store.id) as ExternalOrder',
			'province.name as ConsigneeProvince',
			'city.name as ConsigneeCity',
			'county.name as ConsigneeDistrict',
			'address.address as ConsigneeAddress',
			'address.telephone as ConsigneeMobile',
			'address.name as ConigneeName',
			'user.name as PayerName',
			'user.telephone as PayerMobile',
			'1 as PayerIdCardType',
			'address.identify as PayerIdCardNumber',
			'suborder_store.orderamount as TotalAmount',
			'suborder_store.discount as Discount',
			'suborder_store.feeamount as ShippingFee',
			'suborder_store.taxamount as Tax',
			'(select order_package.ship_type from order_package where order_package.orderno=suborder_store.main_orderno limit 1) as ShippingType',//配送方式
			//'(select order_package.ship_number from order_package where order_package.orderno=suborder_store.main_orderno limit 1) as ship_number',//配送编号
			'if(order.pay_type="alipay",112,if(order.pay_type="wechat",114,"")) as PayType',
			'if(address.zcode="",111111,address.zcode) as ConsigneePostCode',
			'order.pay_number as BatchNumber',
			'concat(order.note,",",order.msg) as Remark',
		]);
		
		if (empty($data['PayerName']))
		{
			$data['PayerName'] = $data['ConigneeName'];
		}
		
		//配送方式
		if (!empty($data['ShippingType']))
		{
			$ship = $this->model('ship')->where('code=?',[$data['ShippingType']])->find();
			$data['ShippingType'] = $ship['openapi'];
		}
		
		//商品
		$product = $this->model('suborder_store')
		->table('suborder_store_product','left join','suborder_store_product.suborder_id=suborder_store.id')
		->table('order_product','left join','suborder_store_product.order_product_id=order_product.id')
		->table('product','left join','product.id=order_product.pid')
		->where('suborder_store.id=?',[$suborder_id])
		->where('order_product.refund=?',[0])
		->select([
			'order_product.price / product.selled as UnitPrice',
			'product.name as CommodityName',
			'product.barcode as CommoditySN',
			//'order_product.num * product.selled as CommodityAmount',  以前计算商品数量的方式废弃掉 用下面新的计算方式
			'order_product.bind * order_product.num as CommodityAmount',
			'order_product.tax as Tax',
		]);
		
		$data['ItemList'] = $product;
		
		$data = $this->createParameter(__FUNCTION__, [$data]);
		
		$this->setResponseString($this->postRequest($data));
		$response_array = json_decode($this->getResponseString(),true);
		
		if (isset($response_array['Code']) && $response_array['Code'] == 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 向接口提交post请求
	 */
	protected function postRequest($data)
	{
		$data = json_encode($data,JSON_UNESCAPED_UNICODE);
		return parent::postRequest($this->getUrl(), $data);
	}
}