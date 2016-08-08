<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class category extends ajax
{
	function save()
	{
		$id = $this->post('id');
		$data = [];
		if ($this->post('name') !== NULL)
			$data['name'] = $this->post('name','');
		if ($this->post('description')!==NULL)
			$data['description'] = $this->post('description','');
		if ($this->post('sort')!==NULL)
			$data['sort'] = $this->post('sort',1);
		if ($this->post('logo')!==NULL)
			$data['logo'] = $this->post('logo');
		
		if (!empty($this->post('cid')))
		{
			$data['cid'] = $this->post('cid');
		}
		else
		{
			$data['cid'] = NULL;
		}
		
		$data['modifytime'] = $_SERVER['REQUEST_TIME'];
		
		if($this->model('category')->where('id=?',[$id])->update($data))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	
	function remove()
	{
		$id = $this->post('id',0,'intval');
		if($this->model('category')->where('id=?',[$id])->update([
			'isdelete'=>1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	function create()
	{
		$name = $this->post('name','');
		$description = $this->post('description','');
		$sort = $this->post('sort',1);
		$logo = $this->post('logo');
		$cid = $this->post('cid');
		if (empty($cid))
		{	
			$cid = NULL;
		}
		if (empty($logo))
		{
			$logo = NULL;
		}
		
		if($this->model('category')->insert([
			'id'=>NULL,
			'name' => $name,
			'logo' => $logo,
			'sort' => $sort,
			'description' => $description,
			'isdelete' => 0,
			'deletetime' => 0,
			'createtime' => $_SERVER['REQUEST_TIME'],
			'modifytime' => $_SERVER['REQUEST_TIME'],
			'cid' => $cid
		]))
		{
			$id = $this->model('category')->lastInsertId();
			$data = $this->model('category')->table('category as c_category','left join','c_category.id=category.cid')->where('category.id=?',[$id])->table('upload','left join','upload.id=category.logo')->find('
				category.id,
				category.name,
				category.sort,
				category.description,
				upload.path as logo,
				c_category.name as c_name,
				category.cid
			');
			return new json(json::OK,NULL,$data);
		}
		return new json(json::PARAMETER_ERROR);
	}
}