<?php
namespace system\core\config;
use system\core\inter\config;
class apiConfig extends config
{
	function __construct()
	{
		$this->_user = [
			'ios' => 'ios',
			'android' => 'android',
		];
	}
	
	/**
	 * 签名函数
	 * @param unknown $url
	 * @param unknown $data
	 * @param unknown $partner
	 * @param unknown $key
	 * @return string
	 */
	function sign($url,$data,$partner,$key)
	{
		if (is_array($data) && !empty($data))
		{
			ksort($data);
			reset($data);
			$data1 = strtolower(http_build_query($data,NULL,'&',PHP_QUERY_RFC3986));
			$data2 = strtolower(http_build_query($data,NULL,'&',PHP_QUERY_RFC1738));
		}
		else
		{
			$data1 = $data2 = '';
		}
		$result1 = strtoupper(md5($data1.$partner.$key));
		$result2 = strtoupper(md5($data2.$partner.$key));
		return [$result1,$result2];
	}
	
	function http_build_query($data,$enc_type = PHP_QUERY_RFC1738)
	{
		$string = '';
		foreach ($data as $index => $value)
		{
			if ($enc_type == PHP_QUERY_RFC1738)
			{
				$string .= ($index.'='.urlencode($value).'&');
			}
			else
			{
				$string .= ($index.'='.rawurlencode($value).'&');
			}
		}
		return rtrim($string,'&');
	}
}