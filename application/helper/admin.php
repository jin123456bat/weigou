<?php
namespace application\helper;
use system\core\base;
use system\core\random;
class admin extends base
{
	/**
	 * 验证用户名和密码是否匹配
	 * @param unknown $username
	 * @param unknown $password
	 */
	function auth($username,$password)
	{
		$result = $this->model('admin')->where('username=?',[$username])->find();
		if (empty($result))
			return false;
		if($this->encrypt($password,$result['salt']) === $result['password'])
		{
			return $result;
		}
		return false;
	}
	
	/**
	 * 生成可以插入到数据库的管理员数据
	 * @param unknown $username
	 * @param unknown $password
	 * @param string $salt
	 * @return multitype:NULL unknown 
	 */
	function createAdminData($username,$password,$role,$salt = NULL)
	{
		if (empty($salt))
			$salt = random::word(6);
		$password = $this->encrypt($password,$salt);
		return [
			'id' => NULL,
			'username' => $username,
			'password' => $password,
			'salt' => $salt,
			'role' => $role,
		];
	}
	
	function saveAdminSession($admin)
	{
		$this->session->id = $admin['id'];
		$this->session->admin = true;
		$this->session->role = $admin['role'];
		return true;
	}
	
	/**
	 * 获取管理员的id
	 * @return NULL
	 */
	function getAdminId()
	{
		if($this->session->admin==true)
		{
			return $this->session->id;
		}
		return NULL;
	}
	
	/**
	 * 获得管理员的用户组
	 * @return NULL
	 */
	function getGroupId()
	{
		if (!empty($this->getAdminId()))
		{
			return $this->session->role;
		}
		return NULL;
	}
	
	/**
	 * 生成管理员的密码
	 * @param string $password 用户的明文密码
	 */
	function encrypt($password,$salt = NULL,$type = 'sha1')
	{
		if (empty($salt))
		{
			$salt = random::word(6);
		}
		switch ($type)
		{
			case 'md5':return md5($password.$salt);
			case 'sha1':return sha1($password.$salt);
		}
	}
}