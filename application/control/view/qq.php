<?php
namespace application\control\view;
use system\core\view;
class qq extends view
{
	function __construct()
	{
		parent::__construct();
		$this->_csrf_token_refresh = false;
	}
	
	function login()
	{
		$code = $this->get('code');
		if(!empty($code))
		{
			$data = [
				'grant_type' => 'authorization_code',
				'client_id' => $this->model('system')->get('appid','qq'),
				'client_secret' => $this->model('system')->get('appkey','qq'),
				'code' => $code,
				'redirect_uri' => urlencode('http://'.$_SERVER['HTTP_HOST']),
				'state' => '',
			];
			$url = 'https://graph.qq.com/oauth2.0/token?'.http_build_query($data);
			$content = file_get_contents($url);
			parse_str($content,$content);
			if (isset($content['access_token']))
			{
				$graph_url = 'https://graph.qq.com/oauth2.0/me?access_token='.$content['access_token'];
				$str  = file_get_contents($graph_url);
				if (strpos($str, "callback") !== false)
				{
					$lpos = strpos($str, "(");
					$rpos = strrpos($str, ")");
					$str  = substr($str, $lpos + 1, $rpos - $lpos -1);
				}
				$user = json_decode($str,true);
				if (isset($user['openid']))
				{
					$userinfo_url = 'https://graph.qq.com/user/get_user_info?access_token=%s&oauth_consumer_key=%s&openid=%s';
					$userinfo_url = sprintf($userinfo_url,$content['access_token'],$this->model('system')->get('appid','qq'),$user['openid']);
					$userinfo = file_get_contents($userinfo_url);
					$userinfo = json_decode($userinfo,true);
					if (isset($userinfo['ret']) && $userinfo['ret']=='0')
					{
						$data = [
							'name' => $userinfo['nickname']
						];
						
						$gravatar = isset($userinfo['figureurl_qq_2']) && !empty($userinfo['figureurl_qq_2'])?$userinfo['figureurl_qq_2']:$userinfo['figureurl_qq_1'];
						if (!empty($gravatar))
						{
							$filename = './application/upload/'.md5($gravatar).'.gif';
							file_put_contents($filename, file_get_contents($gravatar));
							
							if (is_file($filename))
							{
								$this->model('upload')->insert([
									'name' => $userinfo['figureurl_qq_1'],
									'type' => 'gif',
									'path' => $filename,
									'time' => $_SERVER['REQUEST_TIME'],
									'size' => filesize($filename)
								]);
								$data['gravatar'] = $this->model('upload')->lastInsertId();
							}
						}
						
						//记录用户信息
						$this->session->user_info = $data;
					}
					
					//记录登陆信息
					$this->session->login_type = 'qq';
					$this->session->login_info = [
						'openid' => $user['openid'],
						'access_token' => $content['access_token'],
						'time' => $_SERVER['REQUEST_TIME'],
					];
					
					$user = $this->model('user')->where('qq_openid_web=?',[$user['openid']])->find();
					if (empty($user))
					{
						$this->response->setCode(302);
						$this->response->addHeader('Location',$this->http->url('','mobile','qq_set_telephone'));
					}
					else
					{
						if($_SERVER['REQUEST_TIME'] - $user['qq_time'] > 25*24*3600)
						{
							$this->model('user')->where('qq_openid_web=?',[$user['openid']])->update([
								'qq_access_token' => $content['access_token'],
								'qq_time' => $_SERVER['REQUEST_TIME']
							]);
						}
						
						$data = $this->session->user_info;
						if(!empty($data) && is_array($data))
						{
							if(!$this->model('user')->where('qq_openid_web=?',[$user['qq_openid']])->limit(1)->update($data))
							{
								var_dump("更新失败");
								exit();
							}
						}
						
						if (empty($user['telephone']))
						{
							$this->response->setCode(302);
							$this->response->addHeader('Location',$this->http->url('','mobile','qq_set_telephone'));
						}
						else
						{
							$userHelper = new \application\helper\user();
							$userHelper->saveUserSession($user);
							
							//qq登陆成功
							$this->response->setCode(302);
							$this->response->addHeader('Location',$this->http->url('','mobile','account'));
						}
					}
				}
			}
		}
		
	}
}