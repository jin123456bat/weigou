<?php
namespace application\helper;
use system\core\random;
use system\core\base;
class wechat extends base
{
	
	private $_appid;
	
	private $_appsecret;
	
	function __construct($appid,$appsecret)
	{
		$this->_appid = $appid;
		$this->_appsecret = $appsecret;
	}
	
	
	/**
	 * 检查微信接入
	 *
	 * @param unknown $signature        	
	 * @param unknown $timestamp        	
	 * @param unknown $nonce
	 * @return boolean
	 */
	function checkSignature($signature, $timestamp, $nonce, $token)
	{
		$tmpArr = array(
			$token,
			$timestamp,
			$nonce
		);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		
		if ($tmpStr == $signature) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 捕获微信消息
	 *
	 * @return string
	 */
	function getData()
	{
		$postStr = file_get_contents('php://input');
		libxml_disable_entity_loader(true);
		file_put_contents('./wechat.txt', $postStr);
		$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
		return json_decode(json_encode($postObj), false);
	}

	/**
	 * 获取用户授权的code 返回一个url 必须跳转到这个url
	 *
	 * @param url $redict
	 *        	获取到code之后跳转的url
	 * @param string $scope
	 *        	snsapi_base |snsapi_userinfo
	 * @param string $state
	 *        	默认值为空
	 */
	function getCode($redict, $scope, $state = '')
	{
		if (empty($state))
			$state = '';
		$redict = urlencode($redict);
		$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=%s&state=%s#wechat_redirect';
		$url = sprintf($url, $this->_appid, $redict, $scope, $state);
		return $url;
	}
	
	/**
	 * 获取用户信息
	 * @param unknown $access_token
	 * @param unknown $openid
	 * @param string $lang
	 * @return mixed
	 */
	function getUserInfo($access_token,$openid,$lang = 'zh_CN')
	{
		$url = 'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=%s';
		$url = sprintf($url,$access_token,$openid,$lang);
		return json_decode($this->getRequest($url));
	}

	/**
	 *
	 * @param string $code
	 *        	通过getCode获取到的code
	 * @param string 返回值字段 默认为openid
	 */
	function getOpenid($code,$field = '')
	{
		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code';
		$url = sprintf($url, $this->_appid, $this->_appsecret, $code);
		$result = $this->getRequest($url);
		$result = json_decode($result, true);
		if (empty($field))
			return $result;
		return $result[$field];
	}
	
	/**
	 * 获取access_token
	 */
	function getAccessToken()
	{
		$access_token = $this->model('system')->get('access_token','wechat');
		if(!empty($access_token))
		{
			$access_token = json_decode($access_token,true);
			if (isset($access_token['access_token']) && isset($access_token['time']) && $_SERVER['REQUEST_TIME'] < $access_token['time'])
			{
				return $access_token['access_token'];
			}
		}
		$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
		$url = sprintf($url, $this->_appid, $this->_appsecret);
		$content = json_decode($this->getRequest($url),true);
		if (isset($content['access_token']))
		{
			$content['time'] = $_SERVER['REQUEST_TIME'] + $content['expires_in'] - 1000;
			$this->model('system')->set('access_token','wechat',json_encode($content),'微信AccessToken不要修改');
			return $content['access_token'];
		}
		return false;
	}
	
	
	/**
	 * 获取jsApiTicket
	 * @param unknown $access_token
	 * @return mixed|boolean
	 */
	function getJsApiTicket()
	{
		$js_api_ticket = $this->model('system')->get('js_api_ticket','wechat');
		if (!empty($js_api_ticket))
		{
			$js_api_ticket = json_decode($js_api_ticket,true);
			if (isset($js_api_ticket['ticket']) && isset($js_api_ticket['time']) && $_SERVER['REQUEST_TIME'] < $js_api_ticket['time'])
			{
				return $js_api_ticket['ticket'];
			}
		}
		$access_token = $this->getAccessToken();
		$url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token='.$access_token;
		$js_api_ticket = json_decode($this->getRequest($url),true);
		if(isset($js_api_ticket['ticket']) && $js_api_ticket['errcode'] == 0)
		{
			$js_api_ticket['time'] = $_SERVER['REQUEST_TIME'] + $js_api_ticket['expires_in'] - 1000;
			$this->model('system')->set('js_api_ticket','wechat',json_encode($js_api_ticket),'微信分享的Ticket不要修改');
			return $js_api_ticket['ticket'];
		}
		return false;
	}

	/**
	 * 发送get请求
	 *
	 * @param unknown $url        	
	 * @return string
	 */
	private function getRequest($url)
	{
		if (function_exists('curl_init'))
		{
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$result = curl_exec($curl);
			return $result;
		}
		return file_get_contents($url);
		
	}
	
	/**
	 * 微信分享和卡劵使用的签名包
	 * @param unknown $jsApiTicket
	 * @return multitype:string NULL number unknown Ambigous <boolean, string> 
	 */
	public function getSignPackage($jsApiTicket)
	{
		// 注意 URL 一定要动态获取，不能 hardcode.
		$protocol = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$url = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	
		$timestamp = time();
		$nonceStr = random::word(16);

		// 这里参数的顺序要按照 key 值 ASCII 码升序排序
		$string = 'jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s';
		$string = sprintf($string,$jsApiTicket,$nonceStr,$timestamp,$url);
		$signature = sha1($string);
	
		$signPackage = array(
			"appId" => $this->_appid,
			"nonceStr" => $nonceStr,
			"timestamp" => $timestamp,
			"url" => $url,
			"signature" => $signature,
			"rawString" => $string
		);
		return $signPackage;
	}

	/**
	 * 发送post请求
	 *
	 * @param unknown $url        	
	 * @param unknown $data        	
	 * @return string
	 */
	private function postRequest($url, $data, $curl = true)
	{
		if (! $curl && function_exists('file_get_contents')) {
			if (is_array($data)) {
				$data = http_build_query($data);
			} else 
				if (is_object($data)) {
					$data = json_encode($data, JSON_UNESCAPED_UNICODE);
				}
			
			$context = array(
				'http' => array(
					'method' => "POST",
					'header' => "Content-type: application/x-www-form-urlencoded\r\n" . "Content-length:" . strlen($data) . "\r\n",
					'content' => $data
				)
			);
			$context = stream_context_create($context);
			return file_get_contents($url, NULL, $context);
		} else {
			if (is_array($data))
			{
				foreach ($data as $index => &$file) {
					if (isset($file[0]) && $file[0] == '@') {
						//mb php5.5不支持@
						$file = new \CURLFile(substr($file, 1));
					}
				}
			}
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			$result = curl_exec($curl);
			return $result;
		}
	}
}
