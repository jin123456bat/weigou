<?php
namespace application\entity;

use system\core\entity;

class role extends entity
{
	function __rule()
	{
		return array(
			array('name'=>'required','message'=>'请填写角色名称'),
			array('name'=>'maxlength','maxlength'=>32,'message'=>'名称长度不能超过32个字符'),
			array('description'=>'maxlength','maxlength'=>256,'message'=>'描述长度不能超过256'),
			array('privileges'=>'required','message'=>'请至少分配一个权限'),
		);
	}
	
	function __privileges($role_id,$privileges)
	{
		$page_id = [];
		
		$need_type_1 = false;
		$need_type_0 = false;
		
		$jstree_state = array();
		
		$privileges_button = array();
		foreach ($privileges['button'] as $button)
		{
			$privileges_button[] = array('rid'=>$role_id,'pid'=>$button,'type'=>'button');
		}
		foreach ($privileges['page'] as $page)
		{
			$jstree_state[] = array('id' => $role_id,'node_id'=>$page,'type'=>'role');
			
			if ($page == 'type_0')
			{
				$privileges_button[] = array('rid'=>$role_id,'pid'=>0,'type'=>'column');
				$select_id = $this->model('admin_menu')->where('type=?',[0])->select('id');
				foreach ($select_id as $id)
				{
					if (!in_array($id['id'], $page_id))
					{
						$page_id[] = $id['id'];
					}
				}
			}
			else if ($page == 'type_1')
			{
				$privileges_button[] = array('rid'=>$role_id,'pid'=>1,'type'=>'column');
				$select_id = $this->model('admin_menu')->where('type=?',[1])->select('id');
				foreach ($select_id as $id)
				{
					if (!in_array($id['id'], $page_id))
					{
						$page_id[] = $id['id'];
					}
				}
			}
			else
			{
				$type = $this->model('admin_menu')->where('id=?',[$page])->scalar('type');
				if ($type==1)
				{
					$need_type_1 = true;
				}
				else if ($type==0)
				{
					$need_type_0 = true;
				}
				
				if (!in_array($page, $page_id))
				{
					$page_id[] = $page;
				}
				
				$ids_array = [$page];
				$id = array_shift($ids_array);
				while (!empty($id))
				{
					$ids = $this->model('admin_menu')->where('u_link=?',[$id])->select('id');
					foreach ($ids as $id)
					{
						$ids_array[] = $id['id'];
						if (!in_array($id['id'], $page_id))
						{
							$page_id[] = $id['id'];
						}
					}
					$id = array_shift($ids_array);
				}
				
				$id = $page;
				while (!empty($id))
				{
					$id = $this->model('admin_menu')->where('id=?',[$id])->scalar('u_link');
					if (!empty($id))
					{
						if (!in_array($id, $page_id))
						{
							$page_id[] = $id;
						}
					}
				}
			}
		}
		
		foreach ($page_id as $id)
		{
			$privileges_button[] = array('rid'=>$role_id,'pid'=>$id,'type'=>'page');
		}
		
		if ($need_type_0)
		{
			$privileges_button[] = array('rid'=>$role_id,'pid'=>0,'type'=>'column');
		}
		if ($need_type_1)
		{
			$privileges_button[] = array('rid'=>$role_id,'pid'=>1,'type'=>'column');
		}
		
		return array(
			'role_privileges' => array(
				'insert' => $privileges_button,
				'delete' => array(
					'rid' => $role_id,
				)
			),
			'jstree_state' => array(
				'insert' => $jstree_state,
				'delete'=>array(
					'id'=>$role_id,
					'type'=>'role',
				)
			)
		);
	}
}