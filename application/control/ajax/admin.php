<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;
use system\core\random;
use application\helper\sms;

/**
 *
 * @author jin12
 *        
 */
class admin extends ajax
{

	private $_aid = NULL;

	function login()
	{
		$username = $this->post('username');
		$password = $this->post('password');
		$adminHelper = new \application\helper\admin();
		if ($admin = $adminHelper->auth($username, $password))
		{
			if ($admin['status'] == 0)
			{
				return new json(json::PARAMETER_ERROR,'账户已被禁止登录');
			}
			$adminHelper->saveAdminSession($admin);
			$this->model("admin_log")->insertlog($this->_aid, '管理员登录成功', 1);
			$action = $adminHelper->getDefaultTypeAction(1,false);
			if (!$action)
			{
				$action = $adminHelper->getDefaultTypeAction(0);
			}
			return new json(json::OK,NULL,$action);
		}
		return new json(json::PARAMETER_ERROR, '用户名或密码错误');
	}
	
	/**
	 * 设置管理员账号状态
	 * @return \application\message\json
	 */
	function status()
	{
		$id = $this->post('id');
		if (!empty($id))
		{
			$status = $this->post('status',0);
			$this->model('admin')->where('id=?',[$id])->limit(1)->update('status',$status);
			$this->model("admin_log")->insertlog($this->_aid, '管理员设置管理账号状态 ,被设置者:'.$id.',设置后状态:'.$status, 1);
			return new json(json::OK);
		}
		else
		{
			return new json(json::PARAMETER_ERROR);
		}
	}

	/**
	 * 管理员修改自己的登录密码  预留 待html模板使用
	 * 
	 * @return \application\message\json
	 */
	function changeMyPwd()
	{
		$adminHelper = new \application\helper\admin();
		
		$old_password = $this->post('old_password');
		$new_password = $this->post('new_password');
		
		$admin = $this->model('admin')
			->where('id=?', [
			$this->_aid
		])
			->find();
		if ($admin['password'] == $adminHelper->encrypt($old_password, $admin['salt']))
		{
			$salt = random::word(6);
			$new_password = $adminHelper->encrypt($new_password, $salt);
			if ($this->model('admin')
				->where('id=?', [
				$this->_aid
			])
				->limit(1)
				->update([
				'password' => $new_password,
				'salt' => $salt
			]))
			{
				$this->model("admin_log")->insertlog($this->_aid, '管理员修改自己的密码', 1);
				return new json(json::OK);
			}
		}
		else
		{
			return new json(json::PARAMETER_ERROR, '旧密码错误');
		}
	}

	/**
	 * 添加管理员账户
	 */
	function create()
	{
		$adminEntity = new \application\entity\admin();
		$data = $this->post();
		$data['create_aid'] = $this->_aid;
		$data['create_time'] = $_SERVER['REQUEST_TIME'];
		$data['status'] = 1;
		$adminEntity->setRuleType('create');
		$adminEntity->setData($data);
		if ($adminEntity->validate())
		{
			$adminEntity->init();
			if($adminEntity->save())
			{
				$this->model("admin_log")->insertlog($this->_aid,'添加管理员成功:'.$adminEntity->getPrimaryKey(), 1);
				return new json(json::OK);
			}
			else
			{
				return new json(json::PARAMETER_ERROR,'添加失败');
			}
		}
		else
		{
			return new json(json::PARAMETER_ERROR,$adminEntity->getErrors());	
		}
	}
	
	/**
	 * 保存管理员信息
	 * @return \application\message\json
	 */
	function save()
	{
		$adminEntity = new \application\entity\admin();
		$data = $this->post();
		if (!isset($data['privileges']))
		{
			$data['privileges'] = array();
		}
		if (!isset($data['field']))
		{
			$data['field'] = array();
		}
		$adminEntity->setRuleType('save');
		$adminEntity->setData($data);
		if ($adminEntity->validate())
		{
			$adminEntity->init();
			if($adminEntity->save())
			{
				$this->model("admin_log")->insertlog($this->_aid,'修改管理员成功:'.$adminEntity->getPrimaryKey(), 1);
				return new json(json::OK);
			}
			else
			{
				return new json(json::PARAMETER_ERROR,'添加失败');
			}
		}
		else
		{
			return new json(json::PARAMETER_ERROR,$adminEntity->getErrors());
		}
	}

	/**
	 * 批量短信发送接口
	 * @return \application\message\json
	 */
	function sendsms()
	{
		$body = array();
		$content = $this->post('content');
		
		$code = $this->model('sendsms')
			->orderby('id', 'desc')
			->find(array(
			'id'
		));
		$code = $code['id'] + 1;
		
		$uid = $this->model('system')->get('uid', 'sms');
		$key = $this->model('system')->get('key', 'sms');
		$sign = $this->model('system')->get('sign', 'sms');
		$template = $content . ' 退订回复TD';
		
		$sms = new sms($uid, $key, $sign);
		$j = 0;
		do
		{
			$ucount = $this->model("user")
				->where("send!=" . $code)
				->find([
				'count(1)'
			]);
			$ucount = $ucount['count(1)'];
			
			$j = ceil($ucount / 100);
			$array = array();
			for ($i = 0; $i <= $j; $i ++)
			{
				
				$user = $this->model("user")
					->where("send!=" . $code)
					->limit($i * 100, 100)
					->select([
					'id',
					'telephone'
				]);
				$array[$i] = $user;
				$uw = '';
				foreach ($user as $u)
				{
					$uw[] = $u['telephone'];
				}
				
				if (! is_array($uw))
				{
					continue;
				}
				$uw = implode(',', $uw);
				
				// 循环发送
				$num = 1;
				$num = $sms->send($uw, $template);
				
				if ($num > 0)
				{
					foreach ($user as $u)
					{
						$this->model("user")
							->where("telephone=?", [
							$u['telephone']
						])
							->update([
							"send" => $code
						]);
						$body[]['phone'] = $u['telephone'];
					}
					unset($user);
				
				}
				else
				{
					switch ($num)
					{
						case '-1':
							return new json(json::PARAMETER_ERROR, '没有该用户账户');
						case '-2':
							return new json(json::PARAMETER_ERROR, '接口密钥不正确');
						case '-21':
							return new json(json::PARAMETER_ERROR, 'MD5接口密钥加密不正确');
						case '-11':
							return new json(json::PARAMETER_ERROR, '该用户被禁用');
						case '-14':
							return new json(json::PARAMETER_ERROR, '短信内容出现非法字符');
						case '-41':
							return new json(json::PARAMETER_ERROR, '手机号码为空');
						case '-42':
							return new json(json::PARAMETER_ERROR, '短信内容为空');
						case '-51':
							return new json(json::PARAMETER_ERROR, '短信签名格式不正确');
						case '-6':
							return new json(json::PARAMETER_ERROR, 'IP限制');
					}
				}
			
			}
		}
		while ($j > 0);
		
		$this->model('sendsms')->insert(array(
			"content" => $content,
			"user" => 0
		));
		return new json(json::OK, NULL, $body);
	}
	
	/**
	 * 按钮级权限
	 */
	function privileges_button()
	{
		$id = $this->post('id');
		if ($id == 'type_0')
		{
			//平台中的所有权限
			$privileges = $this->model('privileges')->where('mid in (select id from admin_menu where type=?)',[0])->select([
				'id',
				'name as text',
				'mid',
			]);
			foreach($privileges as &$p)
			{
				$p['m_title'] = $this->model('admin_menu')->where('id=?',[$p['mid']])->scalar('name');
			}
			unset($p);
		}
		else if ($id == 'type_1')
		{
			$privileges = $this->model('privileges')->where('mid in (select id from admin_menu where type=?)',[1])->select([
				'id',
				'name as text',
				'mid',
			]);
			
			foreach($privileges as &$p)
			{
				$p['m_title'] = $this->model('admin_menu')->where('id=?',[$p['mid']])->scalar('name');
			}
			unset($p);
		}
		else
		{
			$total_id = [$id];
			
			$iteratoring_id = [$id];
			$id = array_shift($iteratoring_id);
			while (!empty($id))
			{
				$selected_id = $this->model('admin_menu')->where('u_link=?',[$id])->select('id');
				foreach ($selected_id as $id)
				{
					$iteratoring_id[] = $id['id'];
					$total_id[] = $id['id'];
				}
				$id = array_shift($iteratoring_id);
			}
			
			$privileges = $this->model('privileges')->where('mid in (?)',$total_id)->select([
				'id',
				'name as text',
				'mid',
			]);
			foreach($privileges as &$p)
			{
				$p['m_title'] = $this->model('admin_menu')->where('id=?',[$p['mid']])->scalar('name');
			}
			unset($p);
		}
		
		$expect = $this->post('expect',array());
		$remain = array();
		foreach ($privileges as $p)
		{
			if (!in_array($p['mid'], $expect))
			{
				$remain[] = $p;
			}
		}
		return new json($remain);
	}
	
	/**
	 * 页面级权限
	 * @return \application\message\json
	 */
	function privileges_page()
	{
		$id = $this->get('id',NULL);
		if (empty($id) || $id == '#')
		{
			$privileges = [
				['id' => 'type_0','text' => '平台','type'=>'folder','children'=>true],
				['id' => 'type_1','text' => '商城','type'=>'folder','children'=>true],
			];
			return new json($privileges);
		}
		else if (strtolower(substr(trim($id), 0,4)) == 'type')
		{
			list(,$type_id) = explode('_', strtolower(trim($id)));
			$privileges = $this->model('admin_menu')->where('type=? and u_link is null and display=?',[$type_id,1])->select('id,name as text');
			foreach ($privileges as &$p)
			{
				$p['children'] = true;
				$p['type'] = 'folder';
			}
			return new json($privileges);
		}
		else
		{
			$privileges = $this->model('admin_menu')->where('u_link=? and display=?',[$id,1])->select('id,name as text');
			foreach ($privileges as &$p)
			{
				$num = $this->model('admin_menu')->where('u_link=? and display=?',[$p['id'],1])->count();
				if ($num>0)
				{
					$p['children'] = true;
					$p['type'] = 'folder';
				}
				else
				{
					$p['children'] = false;
					$p['type'] = 'file';
				}
			}
			return new json($privileges);
		}
	}
	
	/**
	 * 加载节点下所有子级的页面级权限列表
	 */
	function privileges_page_children()
	{
		$id = $this->get('id');
		if ($id == 'type_0')
		{
			$total_id = [];
			$selected_id = $this->model('admin_menu')->where('type=?',[0])->select([
				'id',
			]);
			foreach ($selected_id as $id)
			{
				$total_id[] = $id['id'];
			}
			return new json($total_id);
		}
		else if ($id == 'type_1')
		{
			$total_id = [];
			$selected_id = $this->model('admin_menu')->where('type=?',[1])->select([
				'id',
			]);
			foreach ($selected_id as $id)
			{
				$total_id[] = $id['id'];
			}
			return new json($total_id);
		}
		else
		{
			$total_id = [$id];
			$iteratoring_id = [$id];
			$id = array_shift($iteratoring_id);
			while (!empty($id))
			{
				$selected_id = $this->model('admin_menu')->where('u_link=?',[$id])->find('id');
				foreach ($selected_id as $id)
				{
					$total_id[] = $id;
					$iteratoring_id[] = $id;
				}
				$id = array_shift($iteratoring_id);
			}
			return new json($total_id);
		}
	}

	function __access()
	{
		$adminHelper = new admin();
		$this->_aid = $adminHelper->getAdminId();
		return array(
			array(
				'deny',
				'actions' => [
					'status',
					'changeMyPwd',
					'save',
					'create',
					'sendsms',
				],
				'message' => new json(json::NOT_LOGIN),
				'express' => empty($this->_aid),
				'redict' => './index.php?c=admin&a=login'
			),
			array(
				'allow',
    			'actions' => '*',
    		)
    	);
    }
}