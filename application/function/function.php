<?php
function telephone($string)
{
	$pattern = '$\d{11}$';
	if(preg_match($pattern, $string,$match))
	{
		return $match[0];
	}
	return NULL;
}

function ip()
{
	$onlineip = NULL;
	if(getenv('HTTP_CLIENT_IP')) {
		$onlineip = getenv('HTTP_CLIENT_IP');
	} elseif(getenv('HTTP_X_FORWARDED_FOR')) {
		$onlineip = getenv('HTTP_X_FORWARDED_FOR');
	} elseif(getenv('REMOTE_ADDR')) {
		$onlineip = getenv('REMOTE_ADDR');
	} else {
		$onlineip = $HTTP_SERVER_VARS['REMOTE_ADDR'];
	}
	return filter_var($onlineip, FILTER_VALIDATE_IP)?$onlineip:'127.0.0.1';
}

/**
 * 判断当前是微信浏览器
 */
function isWechat()
{
	if (isset($_SERVER['HTTP_USER_AGENT']))
	{
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		return stripos($user_agent, 'MicroMessenger') !== false;
	}
	return false;
}

/**
 * 将字符串切割为数组
 * @param unknown $str
 * @param number $l
 * @return string[]
 */
function str_split_unicode($str, $l = 0)
{
	if ($l > 0) {
		$ret = array();
		$len = strlen($str);
		
		for ($i = 0; $i < $len; $i += $l) {
			$temp = substr($str, $i, $l);
			if (!empty($temp))
			{
				$ret[] = $temp;
			}
		}
		
		return $ret;
	}
	return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}