<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class role extends ajax
{
	private $_aid;
	
	/**
	 * 设置角色状态
	 * @return \application\message\json
	 */
	function status()
	{
		$id = $this->post('id');
		if (!empty($id))
		{
			$status = $this->post('status',0);
			$this->model('role')->transaction();
			$this->model('role')->where('id=?',[$id])->limit(1)->update('status',$status);
			$this->model("admin_log")->insertlog($this->_aid, '管理员更改角色状态,被更改角色ID:'.$id.',更改后状态:'.$status, 1);
			$this->model('role')->commit();
			return new json(json::OK);
		}
		else
		{
			return new json(json::PARAMETER_ERROR);
		}
	}
	
	/**
	 * 添加角色
	 * @return \application\message\json
	 */
	function create()
	{
		$role = new \application\entity\role();
		$data = $this->post();
		$data['create_aid'] = $this->_aid;
		$data['create_time'] = $_SERVER['REQUEST_TIME'];
		$data['status'] = 1;
		$role->setData($data);
		if ($role->validate())
		{
			if($role->save())
			{
				$this->model("admin_log")->insertlog($this->_aid,'添加角色成功:'.$role->getPrimaryKey(), 1);
				return new json(json::OK);
			}
			else
			{
				return new json(json::PARAMETER_ERROR,'保存失败');
			}
		}
		else
		{
			return new json(json::PARAMETER_ERROR,$role->getErrors());
		}
	}
	
	/**
	 * 载入角色的权限树
	 * @return \application\message\json
	 */
	function load()
	{
		$id = $this->post('id');
		$type = $this->post('type');
		if (!in_array($type, ['admin','role']))
		{
			return new json(json::PARAMETER_ERROR);
		}
		
		$page = $this->model('jstree_state')->where('id=? and type=?',[$id,$type])->select();
		
		if ($type=='role')
		{
			$button = $this->model($type.'_privileges')->where('rid=? and type=?',[$id,'button'])->select('pid');
			
			return new json(json::OK,NULL,array(
				'page' => $page,
				'button' => $button,
			));
		}
		else if ($type=='admin')
		{
			$button = $this->model($type.'_privileges')->where('aid=? and type=?',[$id,'button'])->select('pid');
			
			$role = [];
			$admin_role = $this->model('admin_role')->where('aid=?',[$id])->select('rid');
			foreach ($admin_role as $r)
			{
				$role[] = $r['rid'];
			}
			
			$field = [];
			$admin_field = $this->model('admin_fields')->where('aid=?',[$id])->select('field');
			foreach ($admin_field as $f)
			{
				$field[] = $f['field'];
			}
			
			return new json(json::OK,NULL,array(
				'role' => $role,
				'page' => $page,
				'button' => $button,
				'field'=>$field,
			));
		}
		
	}
	
	function save()
	{
		$role = new \application\entity\role();
		$data = $this->post();
		$role->setData($data);
		if ($role->validate())
		{
			if($role->save())
			{
				$this->model("admin_log")->insertlog($this->_aid,'修改角色成功:'.$role->getPrimaryKey(), 1);
				return new json(json::OK);
			}
			else
			{
				return new json(json::PARAMETER_ERROR,'保存失败');
			}
		}
		else
		{
			return new json(json::PARAMETER_ERROR,$role->getErrors());
		}
	}
	
	function __access()
	{
		$adminHelper = new \application\helper\admin();
		$this->_aid = $adminHelper->getAdminId();
		return array(
			array(
				'allow',
				'actions' => [
					'remove',
					'create',
					'status',
					'load',
				],
				'message' => new json(json::NOT_LOGIN),
				'express' => !empty($this->_aid),
				'redict' => './index.php?c=admin&a=login'
			),
			array(
				'deny',
				'actions' => '*',
			)
		);
	}
}