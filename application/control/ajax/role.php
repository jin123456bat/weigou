<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class role extends ajax
{
	function role()
	{
		$id = $this->post('id');
		$name = $this->post('name');
		if (empty($id) || empty($name))
			return new json(json::PARAMETER_ERROR);
		
		$role = $this->model('role')->where('id=?',[$id])->find();
		if($role[$name] == 15)
		{
			if($this->model('role')->where('id=?',[$id])->update('`'.$name.'`',0))
			{
				return new json(json::OK);
			}
		}
		else
		{
			if($this->model('role')->where('id=?',[$id])->update('`'.$name.'`',15))
			{
				return new json(json::OK);
			}
		}
	}
	
	function create()
	{
		$name = $this->post('name');
		if (!empty($name))
		{
			$fields = $this->model('role')->getFields();
			foreach ($fields['role'] as $index => $value)
			{
				if ($value == 'id')
					continue;
				$data[$value] = 15;
			}
			$data['name'] = $name;
			if($this->model('role')->insert($data))
			{
				$data['id'] = $this->model('role')->lastInsertId();
				return new json(json::OK,NULL,$data);
			}
			return new json(json::PARAMETER_ERROR);
		}
		return new json(json::PARAMETER_ERROR,'角色名不能为空');
	}
	
	function remove()
	{
		$id = $this->post('id');
		if(!empty($this->model('admin')->where('role=?',[$id])->find()))
		{
			return new json(json::PARAMETER_ERROR,'存在改角色下的管理员用户，无法删除该角色');
		}
		if($this->model('role')->where('id=?',[$id])->delete())
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
}