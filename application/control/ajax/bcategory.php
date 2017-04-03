<?php
namespace application\control\ajax;
use system\core\ajax;
use application\helper\admin;
use application\message\json;

class bcategory extends ajax
{
	/**
	 * 添加后台分类
	 */
	function create()
	{
		$name = $this->post('name','','trim|htmlspecialchars');
		$sort = $this->post('sort',0);
		$bc_id = $this->post('bc_id',NULL,'intval');
		$stock_limit = $this->post('stock_limit',10,'intval');
		if (empty($name))
		{
			return new json(json::PARAMETER_ERROR,'请填写分类名称');
		}
		$bc_id = empty($bc_id)?NULL:$bc_id;
		
		if($this->model('bcategory')->insert([
			'name' => $name,
			'sort' => $sort,
			'bc_id'=>$bc_id,
			'stock_limit'=>$stock_limit,
		]))
		{
			$id = $this->model('bcategory')->lastInsertId();
			$result = $this->model('bcategory')->where('id=?',[$id])->find();
			return new json(json::OK,NULL,$result);
		}
		return new json(json::PARAMETER_ERROR,'添加失败');
	}
	
	function find()
	{
		return new json(json::OK,NULL,$this->model('bcategory')->where('id=?',[$this->post('id',0,'intval')])->find());
	}
	
	function setStockLimit()
	{
		$id = $this->post('id',0,'intval');
		$stock_limit = $this->post('stock_limit',0,'intval');
		if ($stock_limit>100 || $stock_limit<0)
		{
			return new json(json::PARAMETER_ERROR,'必须在0到100之间');
		}
		$this->model('bcategory')->where('id=?',[$id])->limit(1)->update([
			'stock_limit'=>$stock_limit,
		]);
		return new json(json::OK);
	}
	
	function save()
	{
		$id = $this->post('id',0,'intval');
		$name = $this->post('name','','trim|htmlspecialchars');
		$sort = $this->post('sort',0,'intval');
		$bc_id = $this->post('bc_id',NULL,'intval');
		$stock_limit = $this->post('stock_limit',10,'intval');
		
		$bc_id = empty($bc_id)?NULL:$bc_id;
		
		if($this->model('bcategory')->where('id=?',[$id])->update([
			'name' => $name,
			'sort' => $sort,
			'bc_id' => $bc_id,
			'stock_limit' => $stock_limit,
		]))
		{
			return new json(json::OK);
		}
		else
		{
			return new json(json::PARAMETER_ERROR,'更新失败');
		}
	}
	
	/**
	 * 获取某一级分类下的子分类列表
	 */
	function show()
	{
		$bc_id = $this->post('bc_id',0,'intval');
		if (empty($bc_id))
		{
			$result = $this->model('bcategory')->where('bc_id is null')->orderby('sort','asc')->select();
		}
		else
		{
			$result = $this->model('bcategory')->where('bc_id=?',[$bc_id])->orderby('sort','asc')->select();
		}
		return new json(json::OK,NULL,$result);
	}
	
	function source()
	{
		$A = $this->model('bcategory')->where('bc_id is null')->orderby('sort','asc')->select('id,name');
		foreach ($A as &$category)
		{
			$child = $this->model('bcategory')->where('bc_id=?',[$category['id']])->select('id,name');
			foreach ($child as &$cate)
			{
				$c = $this->model('bcategory')->where('bc_id=?',[$cate['id']])->select('id,name');
				$cate['child'] = $c;
			}
			$category['child'] = $child;
		}
		return new json($A);
	}
	
	/**
	 * 移除分类
	 */
	function remove()
	{
		$id = $this->post('id',0,'intval');
		if (empty($id))
		{
			return new json(json::PARAMETER_ERROR);
		}
		if (!empty($this->model('bcategory')->where('bc_id=?',[$id])->select()))
		{
			return new json(json::PARAMETER_ERROR,'请先移除子类');
		}
		
		if($this->model('bcategory')->where('id=?',[$id])->limit(1)->delete())
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR,'删除失败');
	}
	
	function __access()
	{
		$adminHelper = new admin();
		return array(
			array(
				'deny',
				'actions' => ['create','setStockLimit','save','remove'],
				'message' => new json(json::NOT_LOGIN),
				'express' => empty($adminHelper->getAdminId()),
				'redict' => './index.php?c=admin&a=login',
			),
		);
	}
}