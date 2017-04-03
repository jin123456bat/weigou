<?php
namespace application\helper;
use system\core\base;
/**
 * 支付驱动
 * @author jin12
 *
 */
class pay extends base
{
	
	private $_debug;//是否是调试模式
	
	private $_id;//订单号
	
	private $_money;//支付金额
	
	private $_paytype;//支付方式
	
	private $_product_name;//商品名称
	
	private $_product_description;//商品描述
	
	private $_charset;//字符集
	
	private $_notify_url;//回调地址
	
	private $_return_url;//跳转地址
	
	private $_client;//客户端访问方式   wap  web app
	
	private $_partner;//收款方账户信息  名
	
	private $_key;//收款方账户信息 密钥
	
	private $_timeout;//超时时间  单位秒
	
	private $_currency;//交易币种
	
	private $_parameter = [];//其他交易参数
	
	private $_signtype;//交易过程中的签名方式
	
	private $_paynumber;//支付单号
	
	private $_url;//接口地址
	
	private $_instance;
	
	function __construct()
	{
		
	}
	
	/**
	 * 添加其他交易参数
	 */
	function createParameter($key = '',$value = '')
	{
		if (is_array($key))
		{
			$this->_parameter = array_merge($this->_parameter,$key);
		}
		else if (is_string($key))
		{
			$this->_parameter[$key] = $value;
		}
	}
	
	/**
	 * 获取一个额外参数  不存在的话返回NULL
	 */
	function getParameter($key = NULL)
	{
		if ($key === NULL)
			return $this->_parameter;
		return isset($this->_parameter[$key])?$this->_parameter[$key]:NULL;
	}
	
	/**
	 * 移除交易参数
	 * @param unknown $key
	 */
	function removeParameter($key)
	{
		unset($this->_parameter[$key]);
	}
	
	/**
	 * 设置支付单号
	 * @param string $paynumber
	 */
	function setPaynumber($paynumber)
	{
		$this->_paynumber = $paynumber;
	}
	
	/**
	 * 获取支付单号
	 * @return string
	 */
	function getPaynumber()
	{
		return $this->_paynumber;
	}
	
	/**
	 * 设置支付金额
	 * @param unknown $money
	 */
	function setMoney($money)
	{
		$this->_money = $money;
	}
	
	/**
	 * 获取支付金额
	 */
	function getMoney()
	{
		return $this->_money;
	}
	
	/**
	 * 设置支付id
	 */
	function setId($id)
	{
		$this->_id = $id;
	}
	
	/**
	 * 获取支付id
	 */
	function getId()
	{
		return $this->_id;
	}
	
	/**
	 * 设置支付方式
	 * @param unknown $paytype
	 */
	function setPayType($paytype)
	{
		$this->_paytype = $paytype;
	}
	
	/**
	 * 获取支付方式
	 * @return unknown
	 */
	function getPayType()
	{
		return $this->_paytype;
	}
	
	/**
	 * 设置购买的商品名称
	 */
	function setProductName($name)
	{
		$this->_product_name = $name;
	}
	
	/**
	 * 获取购买的商品名称
	 */
	function getProductName()
	{
		return $this->_product_name;
	}
	
	/**
	 * 设置商品描述
	 * @param unknown $description
	 */
	function setProductDescription($description)
	{
		$this->_product_description = $description;
	}
	
	/**
	 * 获取商品描述
	 * @return unknown
	 */
	function getProductDescription()
	{
		return $this->_product_description;
	}
	
	/**
	 * 设置异步通知地址
	 * @param unknown $url
	 */
	function setNotifyUrl($url)
	{
		$this->_notify_url = $url;
	}
	
	/**
	 * 获取异步通知地址
	 * @return unknown
	 */
	function getNotifyUrl()
	{
		return $this->_notify_url;
	}
	
	/**
	 * 设置页面跳转地址
	 */
	function setReturnUrl($url)
	{
		$this->_return_url = $url;
	}
	
	/**
	 * 获取页面跳转地址
	 */
	function getReturnUrl()
	{
		return $this->_return_url;
	}
	
	/**
	 * 设置交易过程中的字符集
	 */
	function setCharset($charset)
	{
		$this->_charset = $charset;
	}
	
	/**
	 * 获取字符集
	 */
	function getCharset()
	{
		return $this->_charset;
	}
	
	/**
	 * 设置超时时间，单位秒
	 */
	function setTimeout($time)
	{
		$this->_timeout = $time;
	}
	
	/**
	 * 获取超时时间
	 */
	function getTimeout()
	{
		return $this->_timeout;
	}
	
	/**
	 * 设置交易货币种类
	 */
	function setCurrency($currency)
	{
		$this->_currency = $currency;
	}
	
	/**
	 * 获取交易货币种类
	 */
	function getCurrency()
	{
		return $this->_currency;
	}
	
	/**
	 * 设置交易账户的partner
	 */
	function setPartner($partner)
	{
		$this->_partner = $partner;
	}
	
	/**
	 * 获取交易账户的partner
	 */
	function getPartner()
	{
		return $this->_partner;
	}
	
	/**
	 * 设置交易账户的key
	 */
	function setKey($key)
	{
		$this->_key = $key;
	}
	
	/**
	 * 获取交易账户的key
	 */
	function getKey()
	{
		return $this->_key;
	}
	
	/**
	 * 设置客户端方式 只允许app,web,wap
	 */
	function setClient($client)
	{
		if (in_array($client, ['app','web','wap']))
		{
			$this->_client = $client;
		}
	}
	
	/**
	 * 获取客户端方式
	 */
	function getClient()
	{
		return $this->_client;
	}
	
	/**
	 * 设置签名方式
	 */
	function setSigntype($signtype)
	{
		$this->_signtype = $signtype;
	}
	
	/**
	 * 获取签名方式
	 */
	function getSigntype()
	{
		return $this->_signtype;
	}
	
	/**
	 * 设置接口地址
	 * @param unknown $url
	 */
	function setUrl($url)
	{
		$this->_url = $url;
	}
	
	/**
	 * 获取接口地址
	 */
	function getUrl()
	{
		return $this->_url;
	}
	
	/**
	 * 验证异步通知是否正确
	 */
	function auth()
	{
		$gateway_class = ('application\\helper\\pay\\'.$this->_paytype);
		$this->_instance = new $gateway_class();
		if(method_exists($this->_instance,'__beforeAuth') && is_callable([$this->_instance,'__beforeAuth']))
		{
			call_user_func([$this->_instance,'__beforeAuth']);
		}
		$result = $this->_instance->auth($this);
		if (method_exists($this->_instance, '__afterAuth') && is_callable([$this->_instance,'__afterAuth']))
		{
			call_user_func([$this->_instance,'__afterAuth'],$result);
		}
		return $result;
	}
	
	/**
	 * 创建支付参数
	 */
	function createPayParameter()
	{
		$gateway_class = ('application\\helper\\pay\\'.$this->_paytype);
		$this->_instance = new $gateway_class();
		if (method_exists($this->_instance, '__beforeCreatePayParameter') && is_callable([$this->_instance,'__beforeCreatePayParameter']))
		{
			call_user_func([$this->_instance,'__beforeCreatePayParameter']);
		}
		$pay_parameter = $this->_instance->createPayParameter($this);
		if (method_exists($this->_instance, '__afterCreatePayParameter') && is_callable([$this->_instance,'__afterCreatePayParameter']))
		{
			call_user_func([$this->_instance,'__afterCreatePayParameter'],$pay_parameter);
		}
		return $pay_parameter;
	}
	
	/**
	 * 创建订单退款参数
	 */
	function createRefundParameter()
	{
		$gateway_class = ('application\\helper\\pay\\'.$this->_paytype);
		$this->_instance = new $gateway_class();
		if (method_exists($this->_instance, '__beforeCreateRefundParameter') && is_callable([$this->_instance,'__beforeCreateRefundParameter']))
		{
			call_user_func([$this->_instance,'__beforeCreateRefundParameter']);
		}
		$pay_parameter = $this->_instance->createRefundParameter($this);
		if (method_exists($this->_instance, '__afterCreateRefundParameter') && is_callable([$this->_instance,'__afterCreateRefundParameter']))
		{
			call_user_func([$this->_instance,'__afterCreateRefundParameter'],$pay_parameter);
		}
		return $pay_parameter;
	}
	
	/**
	 * 发送支付单
	 */
	function payed()
	{
		$gateway_class = ('application\\helper\\pay\\'.$this->_paytype);
		$this->_instance = new $gateway_class();
		if(method_exists($this->_instance,'__beforePayed') && is_callable([$this->_instance,'__beforePayed']))
		{
			call_user_func([$this->_instance,'__beforePayed']);
		}
		$result = $this->_instance->Payed($this);
		if (method_exists($this->_instance, '__afterPayed') && is_callable([$this->_instance,'__afterPayed']))
		{
			call_user_func([$this->_instance,'__afterPayed'],$result);
		}
		return $result;
	}
	
	/**
	 * 获取上一次的错误信息
	 */
	function getLastError()
	{
		return $this->_instance->getLastError();
	}
}