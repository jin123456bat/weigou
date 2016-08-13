<?php 
namespace application\helper;
use system\core\base;
use system\core\http;
abstract class erp extends base
{
	private $_url;
	
	private $_appid;
	
	private $_appsecret;
	
	private $_parameter;
	
	private $_repeat_id;
	
	private $_depart;
	
	private $_responseString;
	
	function __construct()
	{
		
	}
	
	/**
	 * 设置是否是拆单数据
	 * @param unknown $depart
	 */
	function setDepart($depart)
	{
		$this->_depart = $depart;
	}
	
	/**
	 * 判断是否是拆单数据
	 */
	function isDepart()
	{
		return $this->_depart;
	}
	
	function setRepeat($repeat_id)
	{
		$this->_repeat_id = $repeat_id;
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
		if (!empty($this->_repeat_id))
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
	 * 设置url
	 * @param unknown $url
	 */
	function setUrl($url)
	{
		$this->_url = $url;
	}
	
	/**
	 * 获取url
	 */
	function getUrl()
	{
		return $this->_url;
	}
	
	/**
	 * 设置接口通信的appid
	 */
	function setAppid($appid)
	{
		$this->_appid = $appid;
	}
	
	/**
	 * 获取appid
	 */
	function getAppid()
	{
		return $this->_appid;
	}
	
	/**
	 * 设置接口通信的appsecret
	 */
	function setAppsecret($appsecret)
	{
		$this->_appsecret = $appsecret;
	}
	
	/**
	 * 获取appsecret
	 */
	function getAppsecret()
	{
		return $this->_appsecret;
	}
	
	/**
	 *  设置接口通信的其他参数
	 */
	function setParameter($parameter)
	{
		if (is_string($parameter))
		{
			$this->_parameter = json_decode($parameter,true);
		}
		else if (is_array($parameter))
		{
			$this->_parameter = $parameter;
		}
	}
	
	/**
	 * 获取其他参数
	 */
	function getParameter($name = NULL)
	{
		if ($name === NULL)
		{
			return $this->_parameter;
		}
		else
		{
			return $this->_parameter[$name];
		}
	}
	
	/**
	 * 向接口提交post请求
	 */
	protected function postRequest($url,$data)
	{
		return http::post($url, $data);
	}
	
	/**
	 * 向接口发送get请求
	 * @param unknown $url
	 * @return Ambigous <string, mixed>
	 */
	protected function getRequest($url)
	{
		return http::get($url);
	}
	
	/**
	 * 签名验证的过程
	 */
	abstract function sign($data);
	
	/**
	 * 设置响应的内容
	 */
	function setResponseString($response)
	{
		$this->_responseString = $response;
	}
	
	/**
	 * 获取响应内容
	 */
	function getResponseString()
	{
		return $this->_responseString;
	}
}
?>