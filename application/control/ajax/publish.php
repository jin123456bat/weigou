<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;

class publish extends ajax
{
	private $_aid;
	
	function lists()
	{
		$result = $this->model('publish')->where('isdelete=?',[0])->select();
		return new json(json::OK,NULL,$result);
	}
	
    function create()
    {
        $name = $this->post('name','','trim');
        $password = $this->post('password','','trim');
        if (empty($name) || empty($password)){
            return new json(json::PARAMETER_ERROR, '请填写完整信息');
        }

        if (!empty($this->model('publish')->where('name=?', [$name])->find())) {
            return new json(json::PARAMETER_ERROR, '用户名已经存在');
        }

        if ($this->model('publish')->insert([
            'name' => $name,
            'password' => md5($password),
            'isdelete' => 0,
            'deletetime' => $_SERVER['REQUEST_TIME']
        ])
        ) {
            $this->model("admin_log")->insertlog($this->_aid, '商户管理，增加供应商供应商成功，商户名：' . $name,1);
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR);
    }

    function remove()
    {
        $admin = $this->session->id;

        $id = $this->post('id');
        if ($this->model('publish')->where('id=?', [$id])->update([
            'isdelete' => 1,
            'deletetime' => $_SERVER['REQUEST_TIME']
        ])
        ) {
            $this->model("admin_log")->insertlog($admin, '商户管理，供应商删除成功，商户id：' . $id,1);

            return new json(json::OK);
        }
        $this->model("admin_log")->insertlog($admin, '商户管理，供应商删除失败，商户id：' . $id);
        return new json(json::PARAMETER_ERROR);
    }
    
    function __access()
    {
    	$adminHelper = new admin();
    	$this->_aid = $adminHelper->getAdminId();
    	return array(
    		array(
    			'deny',
    			'actions' => [
    				'lists',
    				'create',
    				'remove'
    			],
    			'message' => new json(json::NOT_LOGIN),
    			'express' => empty($this->_aid),
    			'redict' => './index.php?c=admin&a=login',
    		),
    	);
    }
}