<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;

class store extends ajax
{

	private $_aid = null;

	function lists()
	{
		$publish_id = $this->get('publish_id');
		
		$store = $this->model('store')->where('isdelete=? and publish=?', [0,$publish_id])->select();
		
		$unselect = $this->model('store')->where('isdelete=? and publish is null',[0])->select();
		
		return new json(json::OK, NULL, array_merge($store,$unselect));
	}

	function find()
	{
		$id = $this->get('id');
		$result = $this->model('store')
			->where('id=?', [
			$id
		])
			->find();
		$result['erp'] = $this->model('erp')
			->where('id=?', [
			$result['erp']
		])
			->scalar('name');
		return new json(json::OK, NULL, $result);
	}

	/**
	 * 仓库创建
	 * @return \application\message\json
	 */
	function create()
	{
		$name = $this->post('name','','trim');
		
		if (empty($name))
		{
			return new json(json::PARAMETER_ERROR,'名称不能为空');
		}
		
		$result = $this->model('store')
			->where('name=? and isdelete=?', [
			$name,
			0
		])->find();
			
		if (! empty($result))
		{
			return new json(json::PARAMETER_ERROR, '仓库已经存在');
		}
		if ($this->model('store')->insert([
			'name' => $name,
			'createtime' => $_SERVER['REQUEST_TIME'],
			'modifytime' => $_SERVER['REQUEST_TIME'],
			'isdelete' => 0,
			'deletetime' => 0,
			'erp' => NULL,
			'is_auto' => 0
		]))
		{
			$data = [
				'id' => $this->model('store')->lastInsertId(),
				'name' => $name
			];
			$this->model("admin_log")->insertlog($this->_aid, '创建仓库成功', 1);
			return new json(json::OK, NULL, $data);
		}
		return new json(json::PARAMETER_ERROR);
	}

	function remove()
	{
		$id = $this->post('id',NULL,'intval');
		if (empty($id))
		{
			return new json(json::PARAMETER_ERROR);
		}
		if ($this->model('store')
			->where('id=?', [
			$id
		])
			->limit(1)->update(['isdelete'=>1,'deletetime'=>time()]))
		{
			$this->model("admin_log")->insertlog($this->_aid, '删除仓库成功', 1);
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	function setPublish()
	{
		$id = $this->post('id',NULL,'intval');
		$publish = $this->post('publish',NULL,'intval');
		$this->model('store')->where('id=?',[$id])->limit(1)->update('publish',$publish);
		return new json(json::OK);
	}

	function save()
	{
		$name = $this->post('name');
		$id = $this->post('id');
		$is_auto = $this->post("is_auto");
		if (empty($id))
		{
			return new json(json::PARAMETER_ERROR);
		}
		if (empty($name))
		{
			return new json(json::PARAMETER_ERROR);
		}
		
		if ($this->model('store')
			->where('id=?', [
			$id
		])
			->update([
			'name' => $name,
			'is_auto' => $is_auto,
			'modifytime' => $_SERVER['REQUEST_TIME']
		]))
		{
			$this->model("admin_log")->insertlog($this->_aid, '保存仓库成功', 1);
			return new json(json::OK);
		}
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
					'find',
					'create',
					'save',
					'remove',
					'setPublish'
				],
				'message' => new json(json::NOT_LOGIN),
				'express' => empty($this->_aid),
				'redict' => './index.php?c=admin&a=login',
			),
		);
	}
}