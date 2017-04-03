<?php
namespace application\control\view;
use system\core\view;
use system\core\http;
class weibo extends view
{
	function __construct()
	{
		parent::__construct();
	}
	
	function login()
	{
		$code = $this->get('code');
		if(!empty($code))
		{
			$access_token_url = 'https://api.weibo.com/oauth2/access_token?client_id=%s&client_secret=%s&grant_type=authorization_code&redirect_uri=%s&code=%s';
			$appkey = $this->model('system')->get('appkey','weibo');
			$appsecret = $this->model('system')->get('appsecret','weibo');
			$redirect_url = urlencode('http://'.$_SERVER['HTTP_HOST']);
			$access_token_url = sprintf($access_token_url,$appkey,$appsecret,$redirect_url,$code);
			$content = http::post($access_token_url,[]);
			$content = json_decode($content,true);
			if($content && isset($content['access_token']))
			{
				$parameter = [
					'access_token' => $content['access_token'],
					'uid' => $content['uid']
				];
				$user_info_url = 'https://api.weibo.com/2/users/show.json';
				$user_info = http::get($user_info_url.'?'.http_build_query($parameter));
				$user_info = json_decode($user_info,true);
				if ($user_info && isset($user_info['id']))
				{
					$uid = $user_info['id'];
					$name = isset($user_info['name']) && !empty($user_info['name'])?$user_info['name']:$user_info['screen_name'];
					if (isset($user_info['avatar_hd']) && !empty($user_info['avatar_hd']))
					{
						$headimgurl = $user_info['avatar_hd'];
					}
					else if (isset($user_info['avatar_large']) && !empty($user_info['avatar_large']))
					{
						$headimgurl = $user_info['avatar_large'];
					}
					else if (isset($user_info['profile_image_url']) && !empty($user_info['profile_image_url']))
					{
						$headimgurl = $user_info['profile_image_url'];
					}
					
					$user_data = [
						'weibo_uid' => $uid,
						'weibo_name' => $name,
						'weibo_access_token' => $content['access_token'],
						'weibo_starttime' => $_SERVER['REQUEST_TIME'],
						'weibo_endtime' => $_SERVER['REQUEST_TIME'] + $content['expires_in'],
					];
					
					if (isset($headimgurl) && !empty($headimgurl))
					{
						$filename = './application/upload/'.md5($headimgurl).'.png';
						file_put_contents($filename, http::get($headimgurl));
						if (file_exists($filename))
						{
							if($this->model('upload')->insert([
								'name' => $headimgurl,
								'path' => $filename,
								'type' => 'png',
								'size' => filesize($filename),
								'time' => $_SERVER['REQUEST_TIME']
							]))
							{
								$user_data['gravatar'] = $this->model('upload')->lastInsertId();
							}
						}
					}
					
					$this->session->login_type = 'weibo';
					$this->session->login_info = $user_data;
					
					$user = $this->model('user')->where('weibo_uid=?',[$uid])->find();
					if (!empty($user))
					{
						if (empty($user['telephone']))
						{
							$this->response->setCode(302);
							$this->response->addHeader('Location',$this->http->url('','mobile','weibo_set_telephone'));
						}
						else
						{
							if ($_SERVER['REQUEST_TIME'] > $user['weibo_endtime'])
							{
								$this->model('user')->where('weibo_uid=?',[$uid])->limit(1)->update($user_data);
							}
							$this->response->setCode(302);
							$this->response->addHeader('Location',$this->http->url('','mobile','account'));
						}
					}
					else
					{
						$this->response->setCode(302);
						$this->response->addHeader('Location',$this->http->url('','mobile','weibo_set_telephone'));
					}
				}
			}
		}
		return '新浪微博登陆失败';
	}
}