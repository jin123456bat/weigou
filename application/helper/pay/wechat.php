<?php
namespace application\helper\pay;
use application\helper\pay;
use system\core\random;
class wechat
{
	private $_pay;
	
	private $_error = NULL;
	
	function createPayParameter(pay $pay)
	{
		$this->_pay = $pay;
		$random = random::word(32);//随机字符串
		$data = array(
			'device_info' => strtoupper($this->_pay->getClient()),
			'time_start' => date("YmdHis",$_SERVER['REQUEST_TIME']),
			'time_expire' => date("YmdHis",$_SERVER['REQUEST_TIME']+$this->_pay->getTimeout()),
			'fee_type' => $this->_pay->getCurrency(),
			'mch_id' => $this->_pay->getPartner(),
			'body' => $this->_pay->getProductName(),//商品描述
			'nonce_str' => random::word(32),
			'notify_url' => $this->_pay->getNotifyUrl(),//异步通知地址
			'out_trade_no' => $this->_pay->getId(),//订单号
			'spbill_create_ip' => ip(),//ip地址
			'total_fee' => $this->_pay->getMoney() * 100,
		);
		//获取其他的支付参数
		$parameter = $pay->getParameter();
		//将其他的支付参数和必须的支付参数经行绑定
		$parameter = array_merge($data,$parameter);
		$content = $this->createPrepay($parameter);
		$xmlResult = new \SimpleXMLElement($content);
		$xmlResult = $this->xmlToArray($xmlResult);
		if($xmlResult['return_code'] != 'SUCCESS')
		{
			$this->_error = $xmlResult['return_msg'];
			return false;
		}
		else
		{
			if($this->verifyResult($xmlResult))
			{
				//有效的微信信息
				if($xmlResult['result_code'] != 'FAIL')
				{
					$this->_error = NULL;
					return $this->output($xmlResult);
				}
				else
				{
					$this->_error = $xmlResult['err_code'].'|'.$xmlResult['err_code_des'];
					return false;
				}
			}
		}
	}
	
	/**
	 * 创建退款参数
	 * @param unknown $pay
	 * @return \application\helper\pay\NULL[]
	 */
	function createRefundParameter($pay)
	{
		$this->_pay = $pay;
		$url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
		
		$data = array(
			'appid' => $this->_pay->getParameter('appid'),
			'mch_id' => $this->_pay->getPartner(),
			'nonce_str'=> random::word(32),
			'transaction_id' => $this->_pay->getPaynumber(), //支付单号
			'out_refund_no' => $this->_pay->getId(),  //退款单号id
			'total_fee' => $this->_pay->getParameter('pay_money') * 100,  //通过微信支付的总金额
			'refund_fee' => $this->_pay->getMoney() * 100,//退款金额
			'refund_fee_type' => 'CNY',
			'op_user_id' => $this->_pay->getPartner(),  //同意退款人
		);
		$data['sign'] = $this->sign($data);
		$content = '<xml>';
		foreach ($data as $key => $value)
		{
			$content .= ('<'.$key.'>'.$value.'</'.$key.'>');
		}
		$content .= '</xml>';
		
		
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//设置证书
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		//使用证书：cert 与 key 分别属于两个.pem文件
		curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLCERT, $this->_pay->getParameter('client_cert','wechat'));
		curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLKEY, $this->_pay->getParameter('client_key','wechat'));
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		//运行curl
		$data = curl_exec($ch);
		curl_close($ch);
		$data = new \SimpleXMLElement($data);
		$data = $this->xmlToArray($data);
		return $data;
	}
	
	/**
	 * 获取上一次错误结果
	 * @return Ambigous <NULL, string, unknown>
	 */
	function getLastError()
	{
		return $this->_error;
	}
	
	/**
	 * 验证微信消息
	 * @return boolean
	 */
	function auth(pay $pay)
	{
		$this->_pay = $pay;
		$content = $this->_pay->getParameter('postParameter');
		$content = new \SimpleXMLElement($content);
		$content = $this->xmlToArray($content);
		if($this->verifyResult($content))
		{
			return $content;
		}
		return false;
	}
	
	/**
	 * xml转数组
	 * @param unknown $xml
	 * @return multitype:NULL
	 */
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
	 * 生成前段调用的参数
	 * @param unknown $xmlResult
	 * @return multitype:string unknown Ambigous <boolean, string> 
	 */
	private function output($xmlResult,$random = '')
	{
		if (empty($random))
			$random = random::word(32);
		$data = array(
			'appId' => $xmlResult['appid'],
			'timeStamp' => $_SERVER['REQUEST_TIME'],
			'nonceStr' => $random,
			'package'=> 'prepay_id='.$xmlResult['prepay_id'],
			'signType' => 'MD5'
		);
		$data['paySign'] = $this->sign($data);
		return $data;
	}
	
	/**
	 * 验证微信返回的数据
	 */
	private function verifyResult($data)
	{
		return $this->sign($data) == $data['sign'];
	}
	
	private function createPrepay($data)
	{
		$data['sign'] = $this->sign($data);
		//构造传递数据
		$content = '<xml>';
		foreach ($data as $key => $value)
		{
			$content .= ('<'.$key.'>'.$value.'</'.$key.'>');
		}
		$content .= '</xml>';
		//接口地址
		$url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
		$context = stream_context_create(array(
			'http'=>array(
				'method'=>"POST",
				'header'=> "Content-type: application/x-www-form-urlencoded\r\n",
				'content'=>$content
			)
		));
		return file_get_contents($url,NULL,$context);
	}
	
	/**
	 * 参数签名
	 * @param unknown $data
	 * @return string
	 */
	private function sign($data)
	{
		$data = $this->filterParameter($data);
		ksort($data);
		reset($data);
		$str = $this->toString($data).'&key='.$this->_pay->getKey();
		$str = strtoupper(md5($str));
		return $str;
	}
	
	/**
	 * 参数过滤
	 * @param unknown $data
	 * @return multitype:unknown 
	 */
	private function filterParameter($data)
	{
		$parameter = array();
		foreach ($data as $key => $value)
		{
			if($value === '' || $key === 'sign')
				continue;
			$parameter[$key] = $value;
		}
		return $parameter;
	}
	
	/**
	 * 将参数列表转化为url字符串
	 * @param array $data
	 */
	private function toString($data)
	{
		$str = '';
		foreach($data as $key => $value)
		{
			$str .= ($key.'='.$value.'&');
		}
		return rtrim($str,'&');
	}
}