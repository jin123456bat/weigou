<?php
namespace application\helper;

use system\core\random;
use system\core\base;

class user extends base
{

	/**
	 * 判断是否登陆，并且获取用户id
	 *
	 * @return NULL
	 */
	function isLogin()
	{
		
		if ($this->session->role === 'user')
			return $this->session->user_id;
			// 用memcached代替
			/*
		 * if ($key = $this->cookie->__get("PHPSESSID")) {
		 *
		 * if ($user = $this->memcache->__get($key)) {
		 * if ($user['role'] === 'user')
		 * return $user['user_id'];
		 * }
		 *
		 * }
		 */
		return 0;
	}

	/**
	 * 把用户信息保存到session中
	 *
	 * @param array $user        	
	 */
	function saveUserSession($user)
	{
		if (! empty($user))
		{
			$this->session->user_id = $user['id'];
			$this->session->role = 'user';
			return true;
		}
		return false;
	}

	/**
	 * 去除用户信息中的保密信息
	 *
	 * @param array $user
	 *        	用户信息
	 */
	function protectedUser(array &$user)
	{
		unset($user['salt']);
		unset($user['password']);
		$user['has_pay_password'] = empty($user['pay_password']) ? 0 : 1;
		unset($user['pay_password']);
		if (! empty($user['o_master']))
		{
			$o_master = $this->model('user')
				->where('id=?', [
				$user['o_master']
			])
				->find();
			$user['o_master_wechat_no'] = $o_master['wechat_no'];
			$user['o_master_name'] = $o_master['name'];
			$user['o_master_telephone'] = $o_master['telephone'];
		}
		else
		{
			$user['o_master_wechat_no'] = NULL;
			$user['o_master_name'] = NULL;
			$user['o_master_telephone'] = NULL;
		}
	}

	/**
	 * 创建用户收货地址数据
	 */
	function createUserAddress($uid, $province, $city, $county, $address, $name, $telephone)
	{
		return [
			'id' => NULL,
			'uid' => $uid,
			'province' => $province,
			'city' => $city,
			'county' => $county,
			'address' => $address,
			'name' => $name,
			'telephone' => $telephone,
			'zcode' => '',
			'identify' => '',
			'host' => 0,
			'isdelete' => 0,
			'deletetime' => 0,
			'createtime' => $_SERVER['REQUEST_TIME'],
			'modifytime' => $_SERVER['REQUEST_TIME']
		];
	}

	/**
	 * 创建一个可以插入到数据库的用户空数据
	 */
	function createUserData()
	{
		do
		{
			$invit = random::word(6);
		}
		while (! empty($this->model('user')
			->where('invit=?', [
			$invit
		])
			->find()));
		
		$oid = NULL;
		$o_master = NULL;
		$invittime = 0;
		$share_uid = $this->session->share_uid;
		if (! empty($share_uid))
		{
			$o_user = $this->model('user')
				->where('id=?', [
				$share_uid
			])
				->find();
			if (! empty($o_user) && ($o_user['vip'] != 0 || $o_user['master'] == 1))
			{
				$invittime = $_SERVER['REQUEST_TIME'];
				$oid = $o_user['id'];
				if ($o_user['master'] == 1)
				{
					$o_master = $o_user['id'];
				}
				else
				{
					$o_master = $o_user['o_master'];
				}
			}
		}
		
		$source = NULL;
		$user_source = $this->session->user_source;
		if (! empty($user_source))
		{
			$source = $user_source;
			
			$source_uid = $this->model('source')
				->where('id=?', [
				$source
			])
				->find();
			if (empty($source_uid))
			{
				$source = NULL;
			}
			else
			{
				if ($source_uid['type'] == 0)
				{
					$o_master = $source_uid['uid'];
					$oid = $source_uid['uid'];
				}
				else
				{ // 普通渠道
					$oid = $source_uid['uid'];
					$suser = $this->model('user')
						->where('id=?', [
						$source_uid['uid']
					])
						->find([
						'master',
						'o_master'
					]);
					if ($suser['master'] == 1)
					{
						$o_master = $source_uid['uid'];
					}
					else
					{
						$o_master = $suser['o_master'];
					}
				
				}
			}
		}
		
		$data = [
			'id' => NULL,
			'name' => '',
			'telephone' => NULL,
			'password' => '',
			'salt' => '',
			'invit' => $invit,
			'regtime' => $_SERVER['REQUEST_TIME'],
			'money' => 0,
			'gravatar' => NULL,
			'vip' => 0,
			'master' => 0,
			'oid' => $oid,
			'o_master' => $o_master,
			'wx_name' => '',
			'wx_openid_web' => NULL,
			'wx_openid_ios' => NULL,
			'wx_openid_android' => NULL,
			'qq_openid_web' => NULL,
			'qq_openid_ios' => NULL,
			'qq_openid_android' => NULL,
			'qq_time_web' => 0,
			'qq_time_ios' => 0,
			'qq_time_android' => 0,
			'qq_name' => '',
			'weibo_access_token' => NULL,
			'weibo_uid' => NULL,
			'weibo_starttime' => NULL,
			'weibo_endtime' => NULL,
			'weibo_name' => NULL,
			'pay_password' => NULL,
			'pay_salt' => NULL,
			'description' => '',
			'score' => 0,
			'invittime' => $invittime,
			'source' => $source,
			'wechat_no' => '',
			'close' => 0,
			'school' => 0
		];
		return $data;
	}

	/**
	 * 通过手机号和密码生成一组用户数据
	 *
	 * @param unknown $telephone        	
	 * @param unknown $password        	
	 * @return multitype:NULL string number
	 */
	function createUserWithTelephone($telephone, $password)
	{
		$user = $this->createUserData();
		$user['salt'] = random::word(6);
		$user['telephone'] = $telephone;
		$user['password'] = $this->encrypt($password, $user['salt']);
		return $user;
	}

	/**
	 * 生成用户的密码
	 *
	 * @param string $password
	 *        	用户的明文密码
	 */
	function encrypt($password, $salt = NULL, $type = 'md5')
	{
		if (empty($salt))
		{
			$salt = random::word(6);
		}
		switch ($type)
		{
			case 'md5':
				return md5($password . $salt);
			case 'sha1':
				return sha1($password . $salt);
		}
	}

	/**
	 * 验证用户的手机号和密码是否正确
	 *
	 * @param unknown $password
	 *        	明文密码
	 * @param unknown $encrypted_password
	 *        	密文密码
	 * @param unknown $salt
	 *        	盐值
	 * @return boolean
	 */
	function auth($password, $encrypted_password, $salt)
	{
		return $this->encrypt($password, $salt) === $encrypted_password;
	}
/**
 * 判断是否登陆，并且获取用户id
 *
 * @return NULL
 */

}