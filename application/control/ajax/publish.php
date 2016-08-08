<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class publish extends ajax
{
	function create()
	{
		$name = $this->post('name');
		$password = $this->post('password');
		if(empty($name) || empty($password))
			return new json(json::PARAMETER_ERROR,'请填写完整信息');
		
		if(!empty($this->model('publish')->where('name=?',[$name])->find()))
		{
			return new json(json::PARAMETER_ERROR,'用户名已经存在');
		}
		
		if($this->model('publish')->insert([
			'name' => $name,
			'password' => md5($password),
			'isdelete'=>0,
			'deletetime'=>$_SERVER['REQUEST_TIME']
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	function remove()
	{
		$id = $this->post('id');
		if($this->model('publish')->where('id=?',[$id])->update([
			'isdelete'=>1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
}