<?php
namespace application\helper;
use system\core\http;
class idcard
{
	static public $mall_id = '110471';
	static public $appkey = '7bc9cd03b3e7cdc4544a8054d71ed4d1';
	static public $url = 'http://121.41.42.121:8080/v2/id-server';
	
	/**
	 * 验证身份证和用户名是否匹配
	 */
	static public function auth($name,$identify)
	{
		$data = [
			'mall_id' => self::$mall_id,
			'realname' => $name,
			'idcard' => $identify,
			'tm' => $_SERVER['REQUEST_TIME'],
		];
		$appkey = self::$appkey;
		$data['sign'] = self::sign($data, $appkey);
		$response = json_decode(http::get(self::$url.'?'.http_build_query($data)),true);
		if (isset($response['status']) && $response['status'] == 2001)
		{
			if ($response['data']['code'] == 1000)
			{
				return 1;
			}
			return 0;
		}
		return -1;
	}

	/**
	 * 签名
	 * @param unknown $data
	 * @param unknown $appkey
	 * @return string
	 */
	static private function sign($data,$appkey)
	{
		$string = '';
		foreach ($data as $value)
		{
			$string .= $value;
		}
		$string .= $appkey;
		return md5($string);
	}
}