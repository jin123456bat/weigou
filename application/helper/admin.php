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
	 * 检查权限
	 * @param unknown $pid
	 * @param unknown $type
	 * @param string $keyword
	 * @return boolean
	 */
	function checkPower($pid,$type,$keyword = '')
	{
		static $static_result = array();
		$static_key = empty($keyword)?0:$keyword;
		if (isset($static_result[$pid][$type][$static_key]))
		{
			return $static_result[$pid][$type][$static_key];
		}
		
		if (empty($type))
		{
			return false;
		}
	
		$adminHelper = new \application\helper\admin();
		$aid = $adminHelper->getAdminId();
		if (empty($aid))
		{
			return false;
		}
	
		$role = [];
		$role_id = $this->model('admin_role')->where('aid=?',[$aid])->select('rid');
		foreach ($role_id as $id)
		{
			$status = $this->model('role')->where('id=?',[$id['rid']])->scalar('status');
			if ($status == 0)
			{
				continue;
			}
			$role[] = $id['rid'];
		}
	
		//角色的权限
		$privileges = $this->model('role_privileges')->where('rid in (?)',$role)->select(['pid','type']);
		//判断角色中的权限是否满足条件
		foreach ($privileges as $p)
		{
			if ($p['type'] == $type)
			{
				if (empty($keyword))
				{
					if ($pid == $p['pid'])
					{
						$static_result[$pid][$type][$static_key] = true;
						return true;
					}
				}
				else
				{
					if ($type == 'button')
					{
						if($this->model('privileges')->where('keyword=?',[$keyword])->scalar('id') == $p['pid'])
						{
							$static_result[$pid][$type][$static_key] = true;
							return true;
						}
					}
					else if ($type == 'page')
					{
						if ($this->model('admin_menu')->where('link=?',[$keyword])->scalar('id') == $p['pid'])
						{
							$static_result[$pid][$type][$static_key] = true;
							return true;
						}
					}
				}
			}
		}
	
		//管理员的额外权限
		$admin_privileges = $this->model('admin_privileges')->where('aid=?',[$aid])->select(['pid','type']);
		foreach ($admin_privileges as $p)
		{
			if ($p['type'] == $type)
			{
				if (empty($keyword))
				{
					if ($pid == $p['pid'])
					{
						$static_result[$pid][$type][$static_key] = true;
						return true;
					}
				}
				else
				{
					if ($type == 'button')
					{
						if($this->model('privileges')->where('keyword=?',[$keyword])->scalar('id') == $p['pid'])
						{
							$static_result[$pid][$type][$static_key] = true;
							return true;
						}
					}
					else if ($type == 'page')
					{
						if ($this->model('admin_menu')->where('link=?',[$keyword])->scalar('id') == $p['pid'])
						{
							$static_result[$pid][$type][$static_key] = true;
							return true;
						}
					}
				}
			}
		}
		
		$static_result[$pid][$type][$static_key] = false;
		return false;
	}
	
	public function getDefaultTypeAction($type,$default = 'nopower')
	{
		$menus = $this->model('admin_menu')->where('type=? and u_link is null',[$type])->select();
		foreach ($menus as $menu)
		{
			if ($this->checkPower($menu['id'], 'page'))
			{
				$actions = $this->model('admin_menu')->where('type=? and u_link=? and display=?',[$type,$menu['id'],1])->orderby('host','desc')->select();
				foreach ($actions as $action)
				{
					if ($this->checkPower($action['id'], 'page'))
					{
						return $action['link'];
					}
				}
			}
		}
		return $default;
	}
	
	/**
	 * 保存管理员的登录信息
	 * @param unknown $admin
	 * @return boolean
	 */
	function saveAdminSession($admin)
	{
		$this->session->id = $admin['id'];
		$this->session->admin = true;
		$this->session->username = $admin['username'];
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