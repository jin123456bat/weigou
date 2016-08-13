<?php
namespace application\helper;
class express
{
	/**
	 * 物流查询接口
	 * @param unknown $com
	 * @param unknown $waybills
	 * @return mixed
	 */
	static function queryJuhe($com,$waybills)
	{
		$key = 'fd55196b80efdd63b68236deeace86e5';
		$url = 'http://v.juhe.cn/exp/index?key=%s&com=%s&no=%s&dtype=json';
		$url = sprintf($url,$key,$com,$waybills);
		$curl = curl_init($url);
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		));
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}
}