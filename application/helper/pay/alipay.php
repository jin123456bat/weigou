<?php
namespace application\helper\pay;
use application\helper\pay;
/**
 * @author jin12
 *
 */
class alipay
{
	private $_pay;
	
	/**
	 * 创建支付参数
	 * @param pay $pay
	 * @return multitype:unknown 
	 */
	function createPayParameter(pay $pay)
	{
		$this->_pay = $pay;
		
		$data = [
			'body' => $pay->getProductDescription(),
			'subject' => $pay->getProductName(),
			'partner' => $pay->getPartner(),
			'out_trade_no'=> $pay->getId(),
			'rmb_fee' => number_format($pay->getMoney(),2,'.',''),
			'notify_url' => $pay->getNotifyUrl(),
			'return_url' => $pay->getReturnUrl(),
			'_input_charset'=> $pay->getCharset(),
		];
		$parameter = $pay->getParameter();
		$parameter = array_merge($data,$parameter);
		$parameter = $this->filterParameter($parameter);
		ksort($parameter);
		reset($parameter);
		$parameter['sign'] = $this->sign($parameter,$pay->getSigntype());
		$parameter['sign_type'] = strtoupper($pay->getSigntype());
		return $parameter;
	}
	
	/**
	 * 创建退款参数
	 */
	function createRefundParameter(pay $pay)
	{
		$this->_pay = $pay;
		
		$data = array(
			'service' => 'refund_fastpay_by_platform_nopwd',
			'partner' => $this->_pay->getPartner(),
			'_input_charset' => $this->_pay->getCharset(),
			'notify_url' => 'http://'.$_SERVER['HTTP_HOST'].'/gateway/alipay/refund_notify.php',
			'batch_no' => date("Ymd").$this->_pay->getId(),//必须8位日期开头
			'refund_date' => date("Y-m-d H:i:s"),
			'batch_num' => 1,
			'detail_data' => $this->_pay->getPaynumber().'^'.$this->_pay->getMoney().'^自动退款',
		);
		ksort($data);
		reset($data);
		$data['sign'] = $this->sign($data,$this->_pay->getSigntype());
		$data['sign_type'] = $this->_pay->getSigntype();
		$url = 'https://mapi.alipay.com/gateway.do';
		$result = file_get_contents($url.'?'.http_build_query($data));
		$xmlResult = new \SimpleXMLElement($result);
		$xmlResult = $this->xmlToArray($xmlResult);
		return $xmlResult;
	}
	
	function xmlToArray($xml)
	{
		$array = array();
		$xml = (array)$xml;
		foreach($xml as $key => $value)
		{
			$array[$key] = $value.'';
		}
		return $array;
	}
	
	/**
	 * 发送支付单
	 */
	function Payed(pay $pay)
	{
		$this->_pay = $pay;
		
		$data = array(
			'service'=> 'alipay.acquire.customs',
			'partner' => $this->_pay->getPartner(),
			'_input_charset' => $this->_pay->getCharset(),
			'out_request_no' => $this->_pay->getId(),
			'trade_no'=> $this->_pay->getPaynumber(),
			'merchant_customs_code'=> $this->_pay->getParameter('customs_code'),
			'merchant_customs_name'=> $this->_pay->getParameter('customs_name'),
			'amount' => $this->_pay->getMoney(),
			'customs_place' => $this->_pay->getParameter('customs_place'),
		);
		$url = $this->_pay->getUrl('url');
		ksort($data);
		reset($data);
		$data['sign'] = $this->sign($data,$this->_pay->getSigntype());
		$result = file_get_contents($url.'?'.$this->toString($data));
		return $result;
	}
	
	/**
	 * 验证支付宝异步消息是否ok
	 * @return number
	 */
	function auth(pay $pay)
	{
		$this->_pay = $pay;
		
		$partner = $this->_pay->getPartner();
		$url = $this->_pay->getUrl();
		$notify_id = $this->_pay->getParameter('notify_id');
		$url = $url.'?service=notify_verify&partner='.$partner.'&notify_id='.$notify_id;
		$result = file_get_contents($url);
		
		if(!preg_match('/true$/i', $result))
		{
			return false;
		}
		
		$parameter = $this->_pay->getParameter('postParameter');
		$sign = $parameter['sign'];
		$parameter = $this->filterParameter($parameter);
		ksort($parameter);
		reset($parameter);
		switch (strtoupper(trim($this->_pay->getSigntype())))
		{
			case 'MD5':
				if($this->sign($parameter,$this->_pay->getSigntype()) != $sign)
				{
					return false;
				}
				break;
			case 'RSA':
				if(!$this->rsaVerify($this->toString($parameter), $this->_pay->getParameter('rsa_public_key'), $sign))
				{
					return false;
				}
				break;
			default:return false;
		}
		return true;
	}
	
	/**
	 * 参数加密
	 * @param unknown $parameter
	 * @return string
	 */
	private function sign($parameter,$sign_type = '')
	{
		$parameter = $this->toString($parameter).$this->_pay->getKey();
		switch (strtoupper($sign_type))
		{
			case 'MD5':$parameter = md5($parameter);
				break;
			case 'RSA':
				$private_key = $this->_pay->getParameter('private_key');
				return $this->encryptRSA($parameter, $private_key);
				break;
			case 'DSA':break;
			default:break;
		}
		return $parameter;
	}
	
	/**
	 * 将交易参数转化为字符串
	 * @param unknown $parameter
	 * @return string
	 */
	private function toString($parameter)
	{
		$return = '';
		foreach($parameter as $key => $value)
		{
			$return .= ($key.'='.trim($value).'&');
		}
		return rtrim($return,'&');
	}
	
	/**
	 * RSA私钥加密
	 * @param unknown $string
	 * @param unknown $private_key
	 * @return unknown
	 */
	function encryptRSA($string,$private_key)
	{
		$priKey = file_get_contents($private_key);
		$res = openssl_get_privatekey($priKey);
		openssl_sign($string, $sign, $res);
		openssl_free_key($res);
		//base64编码
		return base64_encode($sign);
	}
	
	/**
	 *
	 * @param unknown $data
	 * @param unknown $ali_public_key_path
	 * @param unknown $sign
	 * @return boolean
	 */
	function rsaVerify($data, $ali_public_key_path, $sign)
	{
		$pubKey = file_get_contents($ali_public_key_path);
		$res = openssl_get_publickey($pubKey);
		$result = (bool)openssl_verify($data, base64_decode($sign), $res);
		openssl_free_key($res);
		return $result;
	}
	
	/**
	 * RSA私钥解密
	 * @param unknown $string
	 * @param unknown $public_key
	 */
	function decryptRSA($string,$private_key)
	{
		$priKey = file_get_contents($private_key);
		$res = openssl_get_privatekey($priKey);
		//用base64将内容还原成二进制
		$content = base64_decode($string);
		//把需要解密的内容，按128位拆开解密
		$result  = '';
		for($i = 0; $i < strlen($content)/128; $i++  ) {
			$data = substr($content, $i * 128, 128);
			openssl_private_decrypt($data, $decrypt, $res);
			$result .= $decrypt;
		}
		openssl_free_key($res);
		return $result;
	}
	
	/**
	 * 过滤掉不需要加密的参数
	 * @param unknown $parameter
	 */
	private function filterParameter($parameter)
	{
		$return = array();
		foreach($parameter as $key => $value)
		{
			if($key == 'sign' || $value == '' || $key == 'sign_type')
				continue;
			$return[$key] = $value;
		}
		return $return;
	}
}