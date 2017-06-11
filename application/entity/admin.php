<?php
namespace application\entity;

use system\core\entity;
use system\core\random;

class admin extends entity
{
	function __rule()
	{
		return array(
			array('username'=>'required','message'=>'请填写用户名'),
			array('username'=>'maxlength','maxlength'=>24,'message'=>'用户名长度不能超过24个字符'),
			array('username'=>'unique','message'=>'用户名已存在','type'=>'create'),
			array('password'=>'required','message'=>'请填写密码','type'=>'create'),
			array('role'=>'required','message'=>'请至少分配一个角色'),
			array('telephone'=>'telephone','message'=>'手机号码不正确'),
		);
	}
	
	function init()
	{
		$data = $this->getData();
		if (!empty($data['password']))
		{
			$salt = random::word(6);
			$this->addData('salt', $salt);
			$adminHelper = new \application\helper\admin();
			$this->addData('password', $adminHelper->encrypt($data['password'],$salt));
		}
		else
		{
			$this->removeData('password');
		}
	}
	
	function __role($admin_id,$role)
	{
		$admin_role = array();
		foreach ($role as $r)
		{
			$admin_role[] = array(
				'aid' => $admin_id,
				'rid' => $r,
			);
		}
		
		return array(
			'admin_role' => array(
				'insert' => $admin_role,
				'delete' => array(
					'aid'=>$admin_id,
				)
			)
		);
	}
	
	function __privileges($admin_id,$privileges)
	{
		$page_id = [];
		
		$need_type_1 = false;
		$need_type_0 = false;
		
		$jstree_state = array();
	
		$privileges_button = array();
		foreach ($privileges['button'] as $button)
		{
			$privileges_button[] = array('aid'=>$admin_id,'pid'=>$button,'type'=>'button');
		}
		foreach ($privileges['page'] as $page)
		{
			$jstree_state[] = array('id' => $admin_id,'node_id'=>$page,'type'=>'admin');
				
			if ($page == 'type_0')
			{
				$privileges_button[] = array('aid'=>$admin_id,'pid'=>0,'type'=>'column');
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
				$privileges_button[] = array('aid'=>$admin_id,'pid'=>1,'type'=>'column');
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
			$privileges_button[] = array('aid'=>$admin_id,'pid'=>$id,'type'=>'page');
		}
		
		if ($need_type_0)
		{
			$privileges_button[] = array('aid'=>$admin_id,'pid'=>0,'type'=>'column');
		}
		if ($need_type_1)
		{
			$privileges_button[] = array('aid'=>$admin_id,'pid'=>1,'type'=>'column');
		}
		
		return array(
			'admin_privileges' => array(
				'insert' => $privileges_button,
				'delete' => array(
					'aid' => $admin_id,
				)
			),
			'jstree_state' => array(
				'insert' => $jstree_state,
				'delete'=>array(
					'id'=>$admin_id,
					'type'=>'admin',
				)
			)
		);
	}
	
	function __field($admin_id,$field)
	{
		$admin_fields = array();
		foreach ($field as $f)
		{
			$admin_fields[] = array(
				'aid' => $admin_id,
				'field' => $f
			);
		}
		return array(
			'admin_fields' => array(
				'insert' => $admin_fields,
				'delete' => array(
					'aid' => $admin_id,
				)
			)
		);
	}
}