<?php
namespace application\helper\erp;
use application\helper\erp;
use application\message\xml;
use application\helper\product;

/**
 * @author jin12
 *
 */
class oms extends erp
{
	private $_debug = false;
	
	
	private $_md5_url = 'http://oms.x-omni.com/api/encrypt';
	
	private $_xml_url = 'http://oms.x-omni.com/api/';
	
	private $UserName = '135'; //开户名
	
	private $ApiSecret = 'bTx3p4TTvHbzJFUER2d61BF3TEKaxs3Hy9Wx0Z*B'; //api密钥；
	
	/*
	 * erp_mode代表erp对接的方式，
	 * false代表单仓库，我们系统的一个仓库对应erp那边的一个仓库
	 * true 代表我们一个仓库，对应erp多个仓库，这时需要配置substore表
	 */
	private $_erp_mode = false;
	
	
	function __construct()
	{
		
	}
	
	/**
	 * 接口是否开启
	 * @return boolean
	 */
	function isOpen()
	{
		return true;
	}
	
	
	/* 获取接口地址
	 * @see \application\helper\erp::getUrl()
	 */
	public function getUrl()
	{
		if ($this->_debug)
		{
			return $this->_xml_url;
		}
		return parent::getUrl();
	}
	
	/* 获取appsecret
	 * @see \application\helper\erp::getAppsecret()
	 */
	public function getAppsecret()
	{
		if($this->_debug)
		{
			return $this->ApiSecret;
		}
		return parent::getAppsecret();
	}
	
	/**
	 * 添加商品
	 */
	function AddGoods($id)
	{
		$product = $this->model('product')->where('id=?',[$id])->find();
		if (!empty($product))
		{
			$store = $this->model('store')
			->table('erp','left join','erp.id=store.erp')->where('store.id=?',[$product['store']])->find([
				'erp.name'
			]);
			if (!empty($store) && $store['name'] == end(explode('\\', __CLASS__)))
			{
				$productHelper = new \application\helper\product();
				$data = [
					'GoodsName' => $product['name'],
					'GoodsNameEn' => $product['name'],
					'DeclaredName' => $product['name'],
					'DeclaredValue' => $product['price'],//申报值
					'GoodsPrice' => $product['price'],
					'GoodsMarketprice' => $product['oldprice'],
					'GoodsCost' => $product['inprice'],
					'GoodsUnit' => $this->model('dictionary')->get($product['MeasurementUnit'],'code'),
					'GoodsSerial' => $product['id'],
					'GoodsBarcode' => $product['barcode'],
					'GoodsClassID' => $product['outside'],
					'NetWeight' => $product['grossWeight'],//净重
					'GoodsPic' => $productHelper->getListImage($product['id'],true),
					'Instruction' => $product['short_description'],
					'GoodsContent' => $product['short_description'],
					'GoodsState' => $product['status'],
					'ProduceCountry' => $this->model('dictionary')->get($product['origin'],'code'),
				];
				
				$xml = $this->createParameter($data, __FUNCTION__.$data['GoodsName'], __FUNCTION__);
				$response = $this->sendXml($xml);
				return $response;
			}
		}
		return false;
	}
	
	/**
	 * 商品查询
	 * @param unknown $barcode
	 * @return unknown[]|\system\core\Ambigous[]|mixed
	 */
	function QueryGoods($barcode)
	{
		$data = [
			'GoodsBarcode' => $barcode,
		];
		$xml = $this->createParameter($data, __FUNCTION__, __FUNCTION__);
		$xml = $this->sendXml($xml);
		$response = xmlToArray($xml);
		if (isset($response['Goods']['Status']) && $response['Goods']['Status'] == 1)
		{
			if (isset($response['Goods']['Data']['Goods']))
			{
				$product = $response['Goods']['Data']['Goods'];
				$product = [
					'name' => $product['GoodsName'],
					'MeasurementUnit' => $this->model('dictionary')->where('type=? and code=?',['MeasurementUnit',str_pad($product['GoodsUnit'],3,'0',STR_PAD_LEFT)])->find('id')['id'],
					'origin' => $this->model('dictionary')->where('type=? and code=?',['country',$product['ProduceCountry']])->find('id')['id'],
					'grossWeight' => $product['GrossWeight']
				];
				return $product;
			}
		}
		return false;
	}
	
	/**
	 * 商品库存查询
	 * @param 商品条形码 $barcode
	 * @param int $store 我们系统的仓库id
	 * @return bool|int 成功返回数字，否则返回false
	 */
	function QueryGoodsInventory($barcode,$store)
	{
		$store_id = 0;
		$storeParameter = $this->getParameter('store');
		if (is_array($storeParameter) && !empty($storeParameter))
		{
			foreach ($storeParameter as $st)
			{
				if($st['store'] == $store)
				{
					$store_id = $st['HouseId'];
				}
			}
		}
		
		$data = [
			'GoodsSerial' => $barcode,
			'HouseId' => $store_id,
			//'StockGetTogether' => 'YES',
		];
		$xml = $this->createParameter($data, __FUNCTION__, __FUNCTION__);
		$xml = $this->sendXml($xml);
		$response = xmlToArray($xml);
		if (isset($response['Inventory']['Status']) && $response['Inventory']['Status'] == 1)
		{
			//一定要判断是否查询数据中存在
			if (isset($response['Inventory']['Data']['Inventory']['Available']))
			{
				return $response['Inventory']['Data']['Inventory']['Available'];
			}
			else
			{
				return 0;
			}
		}
		return false;
	}
	
	function QueryPlatform($platForm = NULL)
	{
		$data = [
			'PlatformId' => $platForm,
		];
		$xml = $this->createParameter($data,__FUNCTION__,__FUNCTION__);
		$response = $this->sendXml($xml);
	}
	
	/**
	 * 订单取消
	 */
	function CancelOrder($suborder_id)
	{
		$data = $this->model('suborder_store')->where('id=?',[$suborder_id])->find([
			'concat(replace(suborder_store.date,"-",""),suborder_store.id) as CustomerCode',
		]);
		$xml = $this->createParameter($data, __FUNCTION__, __FUNCTION__);
		$response = $this->sendXml($xml);
		$response = xmlToArray($response);
		if (isset($response['Order']['Status']) && $response['Order']['Status'] == 1)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * 添加订单
	 * @param unknown $suborder_id
	 * @return boolean
	 */
	function AddOrder($suborder_id)
	{
		$erp_mode = $this->_erp_mode;
		$suborder = $this->model('suborder_store')->where('id=?',[$suborder_id])->find();
		if (empty($suborder))
		{
			return false;
		}
		$store = $this->model('store')->where('id=?',[$suborder['store']])->find();//找到对应的仓库
		
		//仓库id替换 获得oms的仓库代码
		if (!$erp_mode)
		{
			$store_id = 0;
			$way = array();//['yt'=>58]
			
			$storeParameter = $this->getParameter('store');
			if (is_array($storeParameter) && !empty($storeParameter))
			{
				foreach ($storeParameter as $st)
				{
					if($st['store'] == $suborder['store'])
					{
						$store_id = $st['HouseId'];
						$way = $st['way'];
					}
				}
			}
		}
		else
		{
			//假如erp_mode为true，需要重新拆单
		}
		
		//获取物流方式编号
		$way = end(end($way));
		
		$data = $this->model('suborder_store')
		->table('`order`','left join','order.orderno=suborder_store.main_orderno')
		->table('address','left join','address.id=suborder_store.address')
		->table('province','left join','address.province=province.id')
		->table('city','left join','address.city=city.id')
		->table('county','left join','address.county=county.id')
		->table('user','left join','user.id=suborder_store.uid')
		->where('suborder_store.id=?',[$suborder_id])
		->find([
			$this->getParameter('platform').' as OrderFrom',//所属平台ID
			'"YES" as OrderDeliver',//订单发货
			'address.name as ReciverName',
			'address.address as ReciverAddress',
			'address.telephone as ReciverPhone',
			'address.zcode as ReciverZipcode',
			'county.name as ReciverDistrict',
			'city.name as ReciverCity',
			'province.name as ReciverState',
			'"中国" as ReciverCountryname',
			'address.identify as ReciverIdentity',
			'concat(order.note,",",order.msg) as OrderMessage',
			$store_id.' as HouseId',
			!$erp_mode?($way.' as ShippingExpressId'):'58 as ShippingExpressId',//这个是物流方式
			'"" as SendOrderSn',//发货单号
			'"YES" as OrderDeliver',//订单同步直接发货
			'suborder_store.feeamount as ShippingFee',
			//'(suborder_store.goodsamount*0.8 + suborder_store.feeamount - suborder_store.discount) as OrderAmount',  //订单金额需要重新计算
			'suborder_store.taxamount as TaxAmount',
			'suborder_store.discount as DiscountAmount',
			'concat(replace(suborder_store.date,"-",""),suborder_store.id) as CustomerCode',//这边是订单号
			'1 as SendType',//发货方式  包税 普通 直邮 默认为保税
			'1 as CustomsID',//清关 浙江口岸
			'0 as PayVirtual',//聚合支付
			'0 as SplitOrder',//直接免税拆单
			//以下信息需要额外组成
			'from_unixtime(order.pay_time) as PayTime',
			'address.identify as PayIdentity',
			'address.telephone as PurchaserPhone',
			'address.address as PurchaserAddr',
			'address.name as PurchaserName',
			'user.id as PurchaserId',//这边需要用户id？而不是用户名？
			'order.pay_number as PayCode',
			'order.pay_type as PayType',
		]);
		
		//承诺书
		$data['UserProcotol'] = '本人承诺所购买商品系个人合理自用，现委托商家代理申报、代缴税款等通关事宜，本人保证遵守《海关法》和国家相关法律法规，保证所提供的身份信息和收货信息真实完整，无侵犯他人权益的行为，以上委托关系系如实填写，本人愿意接受海关、检验检疫机构及其他监管部门的监管，并承担相应法律责任.';
		
		$product = $this->model('suborder_store_product')
		->table('order_product','left join','order_product.id=suborder_store_product.order_product_id')
		->table('product','left join','product.id=order_product.pid')
		->where('suborder_store_product.suborder_id=?',[$suborder_id])
		->select([
		//	'product.id as GoodsCommonid',
			'product.barcode as GoodsSerial',
			//'order_product.price as GoodsPayPrice',
			'(order_product.num * product.inprice * 0.8) as GoodsPayPrice',//商品单价
			'(order_product.num * product.selled) as GoodsNum',//商品数量
		]);
		
		//重新计算订单价格
		$goodsamount = 0;
		foreach ($product as $p_info)
		{
			$goodsamount += $p_info['GoodsPayPrice'];
		}
		$data['OrderAmount'] = number_format($goodsamount + $data['ShippingFee'] + $data['TaxAmount'] - $data['DiscountAmount'],2,'.','');
		
		$data['GoodsList']['Goods'] = $product;
		
		file_put_contents('./oms_debug.txt', json_encode($data));
		
		//支付信息
		$data['PayInfo'] = array(
			'PayCompanyCode' => $this->model('paycompany_customs')->where('city=?',[$store['customs']])->find($data['PayType'])[$data['PayType']],//支付公司编码
			'PayTime' => $data['PayTime'],
			'PayIdentity' => $data['PayIdentity'],
			'PurchaserPhone' => $data['PurchaserPhone'],
			'PurchaserAddr' => $data['PurchaserAddr'],
			'PurchaserName' => $data['PurchaserName'],
			'PurchaserId' => $data['PurchaserId'],
			'PayCode' => $data['PayCode'],
		);
		
		unset($data['PayCompanyCode']);
		unset($data['PurchaserPhone']);
		unset($data['PurchaserAddr']);
		unset($data['PurchaserName']);
		unset($data['PurchaserId']);
		unset($data['PayCode']);
		unset($data['PayIdentity']);
		unset($data['PayTime']);
		unset($data['PayType']);
		
		$xml = $this->createParameter($data,__FUNCTION__.$data['CustomerCode'],__FUNCTION__);
		
		$response = $this->sendXml($xml);
		$this->setResponseString($response);
		$response = xmlToArray($response);
		if (isset($response['Order']['Status']) && $response['Order']['Status'] == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 订单物流轨迹查询
	 * @param unknown $suborder_id
	 */
	function QueryLogistics($suborder_id)
	{
		$data = $this->model('suborder_store')->where('id=?',[$suborder_id])->find([
			'concat(replace(suborder_store.data,"-",""),suborder_store.id) as CustomerCode',
		]);
		$xml = $this->createParameter($data, __FUNCTION__, __FUNCTION__);
		$response = $this->sendXml($xml);
		return $response;
	}
	
	/**
	 * 身份验证
	 * @param unknown $name
	 * @param unknown $identify
	 */
	function IdcardCheck($name,$identify)
	{
		$data = [
			'CardName' => $name,
			'Idcard' => $identify
		];
		$xml = $this->createParameter($data, __FUNCTION__.$data['CardName'].$data['Idcard'], __FUNCTION__);
		$response = $this->sendXml($xml);
		return $response;
	}
	
	/**
	 * 物流查询 查询仓库下可用的物流方式
	 * @param unknown $HouseId
	 */
	function QueryExpress($HouseId)
	{
		$data = [
			'HouseId' => $HouseId,
		];
		$xml = $this->createParameter($data, __FUNCTION__, __FUNCTION__);
		$response = $this->sendXml($xml);
		return $response;
	}
	
	/**
	 * 创建要提交的参数
	 * @param array $data
	 * @param unknown $signString
	 */
	private function createParameter(array $data,$signString,$BusinessLogic)
	{
		$data['MD5Key'] = $this->sign($signString);
		$xml = new xml($data,false,false,'Oms');
		$xml = $xml->__toString();
		$xml = str_replace('<Oms>', '<Oms BusinessLogic="'.$BusinessLogic.'">', $xml);
		$xml = '<OmsList UserName="' . $this->UserName . '">'.$xml.'</OmsList>';
		$xml = '<?xml version="1.0" encoding="utf-8"?>'.$xml;
		return $xml;
	}
	
	/**
	 * 签名验证
	 * {@inheritDoc}
	 * @see \application\helper\erp::sign()
	 */
	function sign($key)
	{
		$xml = new xml([
			'String' => $key,
			'md5key' => $this->getAppsecret(),
		],false,false,'MD5');
		//组装加密xml
		$xml_data_md5 = '<?xml version="1.0" encoding="UTF-8"?>' .$xml->__toString();
		
		
		//返回加密值
		$get_xml = $this->sendXml($xml_data_md5, $this->_md5_url);
		$k = xmlToArray($get_xml);
		return $k['md5value'];
	}
	
	/**
	 * 提交并得到返回XML
	 * @copyright 2015-06-02, coolzbw
	 */
	function sendXml($xml, $url = NULL) {
		if (empty($url))
		{
			$url = $this->getUrl();
		}
		$header = array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
		curl_setopt($curl, CURLOPT_AUTOREFERER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}
	
}