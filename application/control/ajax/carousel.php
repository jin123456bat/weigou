<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class carousel extends ajax
{
	function save()
	{
		$id = $this->post('id');
		if($this->model('carousel')->where('id=?',[$id])->update([
			'title' => $this->post('title',''),
			'sort' => $this->post('sort',0),
			'linktype' => $this->post('linktype','none'),
			'url' => $this->post('url',''),
			'modifytime' => $_SERVER['REQUEST_TIME'],
			'logo' => empty($this->post('logo',NULL,'intval'))?NULL:$this->post('logo',NULL,'intval'),
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	function create()
	{
		$total = $this->model('carousel')->where('isdelete=?',[0])->select('count(*)');
		if($this->model('carousel')->insert([
			'title' => '',
			'logo' => NULL,
			'sort' => $total[0]['count(*)'] + 1,
			'createtime' => $_SERVER['REQUEST_TIME'],
			'deletetime' => 0,
			'isdelete' => 0,
			'modifytime' => $_SERVER['REQUEST_TIME'],
			'linktype' => 'none',
			'url' => '',
			'position' => $this->post('position','index'),
		]))
		{
			$cid = $this->model('carousel')->lastInsertId();
			return new json(json::OK,NULL,$cid);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	function remove()
	{
		$id = $this->post('id');
		if($this->model('carousel')->where('id=?',[$id])->update([
			'isdelete'=>1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
}