<?php
namespace application\control\view;
use system\core\view;
use system\core\http;
class wechat extends view
{
	private $_userinfo;
	
	function __construct()
	{
		parent::__construct();
	}
	
	function login()
	{
		if(isWechat())
		{
			$appid = $this->model('system')->get('appid','wechat');
			$appsecret = $this->model('system')->get('appsecret','wechat');
			$this->_wechat = new \application\helper\wechat($appid, $appsecret);
			if ($this->get->code === NULL)
			{
				$location = $this->_wechat->getCode($this->http->url(), 'snsapi_userinfo');
				header('Location: '.$location,true,302);
				exit();
			}
			else
			{
				$code = $this->get->code;
				$codeinfo = $this->_wechat->getOpenid($code);
				if (isset($codeinfo['openid']) && isset($codeinfo['access_token']))
				{
					$this->_userinfo = $this->_wechat->getUserInfo($codeinfo['access_token'], $codeinfo['openid']);
					//记录当前登陆方式
					$this->session->login_type = 'wechat';
					
					//获取微信用户的信息
					$userHelper = new \application\helper\user();
					$user = $this->model('user')->where('wx_openid_web=?',[$this->_userinfo->openid])->find();
					
					//提取需要的微信信息
					$user_data = [
						'wx_name' => $this->_userinfo->nickname,
						'wx_openid_web' => $this->_userinfo->openid,
					];
					
					if (!empty($this->_userinfo->headimgurl))
					{
						$filename = './application/upload/'.md5($this->_userinfo->headimgurl).'.png';
						file_put_contents($filename, http::get($this->_userinfo->headimgurl));
						if (is_file($filename))
						{
							$this->model('upload')->insert([
								'name' => $this->_userinfo->headimgurl,
								'type' => 'png',
								'path' => $filename,
								'time' => $_SERVER['REQUEST_TIME'],
								'size' => filesize($filename)
							]);
							$fileid = $this->model('upload')->lastInsertId();
							$user_data['gravatar'] = $fileid;
						}
					}
					
					//记录微信信息
					$this->session->login_info = $user_data;
					
					if (!empty($user))
					{
						//假如用户存在 更新用户微信信息
						$this->model('user')->where('wx_openid_web=?',[$this->_userinfo->openid])->limit(1)->update($user_data);
							
						if (empty($user['telephone']))
						{
							//手机号尚未绑定 去绑定
							$this->response->setCode(302);
							$this->response->addHeader('Location',$this->http->url('','mobile','wechat_set_telephone'));
						}
						else
						{
							//手机号已经绑定 直接登陆
							$userHelper->saveUserSession($user);
							$this->response->setCode(302);
							$this->response->addHeader('Location',$this->http->url('','mobile','account'));
						}
					}
					else
					{
						//用户不存在，直接去绑定手机号和密码
						$this->response->setCode(302);
						$this->response->addHeader('Location',$this->http->url('','mobile','wechat_set_telephone'));
					}
					return '';
				}
			}
		}
		return '请使用微信打开该连接';
	}
}