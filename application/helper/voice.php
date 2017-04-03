<?php
namespace application\helper;
use system\core\http;
use system\core\base;
class voice extends base
{
	/**
	 * 文字转语音
	 * @param string $text 文字内容
	 * @param string $token 百度的access_token
	 * @param int $speed 语音速度
	 * @param int $pit 语调
	 * @param int $vol 音量
	 * @param int $per 0为女声，1为男声
	 */
	function textToVoice($text,$token = NULL,$speed = 5,$pit = 5,$vol = 5,$per = 0)
	{
		$client_id = 'PdQf1VkvV9qKsv3hVLSwAyef';
		$client_secret = '2658c189926a6e744b9d0879ee3e5b83';
		if (empty($token))
		{
			$token = $this->getAccessToken($client_id, $client_secret);
		}
		
		$data = [
			'tex' => $text,
			'lan' => 'zh',
			'tok' => $token,
			'ctp' => 1,
			'cuid' => ip(),
			'spd' => $speed,
			'pit' => $pit,
			'vol' => $vol,
			'per' => $per
		];
		$url = 'http://tsn.baidu.com/text2audio';
		$url = $url.'?'.http_build_query($data);
		$response = http::get($url);
		$response_json = json_decode($response,true);
		
		if (!empty($response_json))
		{
			return false;
		}
		
		$filename = './application/upload/voice/'.md5($response).'.mp3';
		file_put_contents($filename, $response);
		return $filename;
	}
	
	/**
	 * 获取百度access_token
	 * @param unknown $grant_type
	 * @param unknown $client_id
	 * @param unknown $client_secret
	 * @return mixed
	 */
	function getAccessToken($client_id,$client_secret,$grant_type = 'client_credentials')
	{
		/*
		 * App ID: 8217119
		 * API Key: PdQf1VkvV9qKsv3hVLSwAyef
		 * Secret Key: 2658c189926a6e744b9d0879ee3e5b83
		 */
		$access_token = $this->model('system')->get('access_token','baidu');
		$access_token = json_decode($access_token,true);
		if (isset($access_token['access_token']) && $_SERVER['REQUEST_TIME'] < $access_token['time'] + $access_token['expires_in'] - 2000)
		{
			return $access_token['access_token'];
		}
		$url = 'https://openapi.baidu.com/oauth/2.0/token';
		$response = http::post($url, [
			'grant_type' => $grant_type,
			'client_id' => $client_id,
			'client_secret' => $client_secret,
		]);
		$response = json_decode($response,true);
		$response['time'] = $_SERVER['REQUEST_TIME'];
		$this->model('system')->set('access_token','baidu',json_encode($response));
		return $response['access_token'];
	}
}