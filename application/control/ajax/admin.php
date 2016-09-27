<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
use system\core\random;
/**
 * @author jin12
 *
 */
class admin extends ajax
{
	function login()
	{
		$username = $this->post('username');
		$password = $this->post('password');
		$adminHelper = new \application\helper\admin();
		if($admin = $adminHelper->auth($username, $password))
		{
			$adminHelper->saveAdminSession($admin);
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR,'用户名或密码错误');
	}
	
	function changePassword()
	{
        $admin=$this->session->id;
		$id = $this->post('id');
		$password = $this->post('password');
		if (!empty($password))
		{
			if(strlen($password) <= 6) {
                $this->model("admin_log")->insertlog($admin, '管理员更改密码失败（密码长度太短）');
                return new json(json::PARAMETER_ERROR, '密码长度太短');
            }
			$adminHelper = new \application\helper\admin();
			$salt = random::word(6);
			$password = $adminHelper->encrypt($password,$salt);
			if($this->model('admin')->where('id=?',[$id])->limit(1)->update([
				'password' => $password,
				'salt' => $salt,
			]))
			{
                $this->model("admin_log")->insertlog($admin, '管理员更改密码成功，用户id：'.$id,1);
				return new json(json::OK);
			}
            $this->model("admin_log")->insertlog($admin, '管理员更改密码失败（参数错误）');
			return new json(json::PARAMETER_ERROR);
		}
		else
		{
            $this->model("admin_log")->insertlog($admin, '管理员更改密码成功，用户id：' . $id, 1);
			return new json(json::OK);
		}
	}
	
	function remove()
	{
        $admin=$this->session->id;
		$id = $this->post('id');
		if($this->model('admin')->where('id=?',[$id])->delete())
		{
            $this->model("admin_log")->insertlog($admin, '管理员删除失败，用户id：' . $id, 1);
			return new json(json::OK);
		}
        $this->model("admin_log")->insertlog($admin, '管理员删除失败（请求内容错误）');
		return new json(json::PARAMETER_ERROR);
	}
	
	/**
	 * 更改管理员角色
	 */
	function role()
	{
        $admin=$this->session->id;
		$id = $this->post('id');
		$role = $this->post('role');
		$this->model('admin')->where('id=?',[$id])->limit(1)->update('role',$role);
        $this->model("admin_log")->insertlog($admin, '管理员更改用户组成功,用户id：' . $id, 1);

        return new json(json::OK);
	}
	
	/**
	 * 添加管理员账户
	 */
	function create()
	{
		$username = $this->post('username');
		$password = $this->post('password');
		$role = $this->post('role','','intval');
		if (strlen($password)<6)
			return new json(json::PARAMETER_ERROR,'密码长度太短');
		
		if (!empty($this->model('admin')->where('username=?',[$username])->find()))
			return new json(json::PARAMETER_ERROR,'用户名已存在');
		
		if (empty($role))
			return new json(json::PARAMETER_ERROR,'请选择权限组');
			
		$adminHelper = new \application\helper\admin();
		$admin = $adminHelper->createAdminData($username, $password,$role);
		if($this->model('admin')->insert($admin))
		{
			$admin['id'] = $this->model('admin')->lastInsertId();
			return new json(json::OK,NULL,$admin);
		}
		return new json(json::PARAMETER_ERROR,'用户名已经存在');
	}
}