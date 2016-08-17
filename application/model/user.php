<?php
namespace application\model;
use system\core\model;
class userModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	/**
	 * 根据手机号码获取用户信息
	 * @return \system\core\Ambigous
	 */
	function getByTelephone($telephone,$parameter = 'user.*,upload.path as gravatar')
	{
		$this->table('upload','left join','user.gravatar=upload.id');
		return $this->where('telephone=?',[$telephone])->find($parameter);
	}
	
	/**
	 * 检查手机号是否已经注册
	 * @param unknown $telephone
	 * @return boolean
	 */
	function telephoneExist($telephone)
	{
		return !empty($this->getByTelephone($telephone));
	}
	
	function fetch(array $filter = [])
	{
		if (isset($filter['name']))
		{
			$this->where('user.name like ?',['%'.$filter['name'].'%']);
		}
		return parent::fetch($filter);
	}
	
	function fetchAll(array $filter = [])
	{
		$this->table('upload','left join','upload.id=user.gravatar');
		return $this->fetch($filter);
	}
	
	function datatables($post)
	{
		$this->table('upload','left join','user.gravatar=upload.id');
		$parameter = [];
		foreach ($post['columns'] as $index => $columns)
		{
			if (!empty($columns['name']))
			{
				$parameter[] = $columns['name'].(empty($columns['data'])?'':(' as '.$columns['data']));
				foreach ($post['order'] as $order)
				{
					if ($order['column'] == $index)
					{
						$this->orderby($columns['name'],$order['dir']);
					}
				}
			}
		}
		if (isset($post['action']) && $post['action'] === 'filter')
		{
			if (!empty($post['username']))
			{
				$this->where('user.name like ?',['%'.trim($post['username']).'%']);
			}
			if (!empty($post['telephone']))
			{
				$this->where('telephone like ?',['%'.trim($post['telephone']).'%']);
			}
			if (!empty($post['invit']))
			{
				$this->where('invit like ?',['%'.trim($post['invit']).'%']);
			}
			if (!empty($post['regtime_from']))
			{
				$this->where('regtime >= ?',[strtotime($post['regtime_from'])]);
			}
			if (!empty($post['regtime_to']))
			{
				$this->where('regtime <= ?',[strtotime($post['regtime_to'])]);
			}
			if (!empty($post['money_from']))
			{
				$this->where('money >= ?',[$post['money_from']]);
			}
			if (!empty($post['money_to']))
			{
				$this->where('money <= ?',[$post['money_to']]);
			}
			if ($post['vip'] != '')
			{
				$this->where('vip=?',[$post['vip']]);
			}
			if ($post['master'] != '')
			{
				$this->where('master=?',[$post['master']]);
			}
			if (!empty(intval($post['o_master'])))
			{
				$this->where('o_master = ?',[intval($post['o_master'])]);
			}
			if (!empty(intval($post['oid'])))
			{
				$this->where('oid = ?',[intval($post['oid'])]);
			}
			if ($post['source'] != '')
			{
				if ($post['source'] == '-1')
				{
					$this->where('user.source is null');
				}
				else
				{
					$this->where('user.source = ?',[$post['source']]);
				}
			}
		}
		//$result = $this->select($parameter,true);
		
		var_dump($this->query('select * from user'));
		exit();
		return $result;
	}
	
	/*
	 * 普通渠道
	 */
	function source_datatables2($post,$session_id)
	{
		$this->table('upload','left join','user.gravatar=upload.id');
		
		$parameter = [];
		foreach ($post['columns'] as $index => $columns)
		{
			if (!empty($columns['name']))
			{
				$parameter[] = $columns['name'].(empty($columns['data'])?'':(' as '.$columns['data']));
				foreach ($post['order'] as $order)
				{
					if ($order['column'] == $index)
					{
						$this->orderby($columns['name'],$order['dir']);
					}
				}
			}
		}
		if (isset($post['action']) && $post['action'] === 'filter')
		{
			if (!empty($post['username']))
			{
				$this->where('user.name like ?',['%'.$post['username'].'%']);
			}
			if (!empty($post['telephone']))
			{
				$this->where('telephone like ?',['%'.$post['telephone'].'%']);
			}
			if (!empty($post['invit']))
			{
				$this->where('invit like ?',['%'.$post['invit'].'%']);
			}
			if (!empty($post['regtime_from']))
			{
				$this->where('regtime >= ?',[strtotime($post['regtime_from'])]);
			}
			if (!empty($post['regtime_to']))
			{
				$this->where('regtime <= ?',[strtotime($post['regtime_to'])]);
			}
			if (!empty($post['money_from']))
			{
				$this->where('money >= ?',[$post['money_from']]);
			}
			if (!empty($post['money_to']))
			{
				$this->where('money <= ?',[$post['money_to']]);
			}
			if ($post['vip'] != '')
			{
				$this->where('vip=?',[$post['vip']]);
			}
			if ($post['master'] != '')
			{
				$this->where('master=?',[$post['master']]);
			}
			if (!empty(intval($post['o_master'])))
			{
				$this->where('o_master = ?',[intval($post['o_master'])]);
			}
			if (!empty(intval($post['oid'])))
			{
				$this->where('oid = ?',[intval($post['oid'])]);
			}
		
		}
		//通过$session_id找到手机号id        用o_master和source来找用户
		$source_uid = $this->model('source')
		->where('id=?',[$session_id])
		->find([
				'uid'
		]);
		
		$this->where('user.source = ? or user.oid = ?',[$session_id,$source_uid['uid']]);
		return $this->select($parameter);
	}
	
	function source_datatables($post,$session_id)
	{
		$this->table('upload','left join','user.gravatar=upload.id');
	
		$parameter = [];
		foreach ($post['columns'] as $index => $columns)
		{
			if (!empty($columns['name']))
			{
				$parameter[] = $columns['name'].(empty($columns['data'])?'':(' as '.$columns['data']));
				foreach ($post['order'] as $order)
				{
					if ($order['column'] == $index)
					{
						$this->orderby($columns['name'],$order['dir']);
					}
				}
			}
		}
		if (isset($post['action']) && $post['action'] === 'filter')
		{
			if (!empty($post['username']))
			{
				$this->where('user.name like ?',['%'.$post['username'].'%']);
			}
			if (!empty($post['telephone']))
			{
				$this->where('telephone like ?',['%'.$post['telephone'].'%']);
			}
			if (!empty($post['invit']))
			{
				$this->where('invit like ?',['%'.$post['invit'].'%']);
			}
			if (!empty($post['regtime_from']))
			{
				$this->where('regtime >= ?',[strtotime($post['regtime_from'])]);
			}
			if (!empty($post['regtime_to']))
			{
				$this->where('regtime <= ?',[strtotime($post['regtime_to'])]);
			}
			if (!empty($post['money_from']))
			{
				$this->where('money >= ?',[$post['money_from']]);
			}
			if (!empty($post['money_to']))
			{
				$this->where('money <= ?',[$post['money_to']]);
			}
			if ($post['vip'] != '')
			{
				$this->where('vip=?',[$post['vip']]);
			}
			if ($post['master'] != '')
			{
				$this->where('master=?',[$post['master']]);
			}
			if (!empty(intval($post['o_master'])))
			{
				$this->where('o_master = ?',[intval($post['o_master'])]);
			}
			if (!empty(intval($post['oid'])))
			{
				$this->where('oid = ?',[intval($post['oid'])]);
			}
	
		}
	
		//通过$session_id找到手机号id        用o_master和source来找用户
		$source_uid = $this->model('source')
		->where('id=?',[$session_id])
		->find([
				'uid'
		]);
	
		$this->where('user.source = ? or user.o_master=?',[$session_id,$source_uid['uid']]);
		return $this->select($parameter);
	}
	
/*
 * 渠道金额统计
 */	
	function sourceunder_datatables($post,$session_id)
	{
		$this->table('upload','left join','user.gravatar=upload.id');
		$this->table('source','left join','user.id=source.uid');
		$parameter = [];
		foreach ($post['columns'] as $index => $columns)
		{
			if (!empty($columns['name']))
			{
				$parameter[] = $columns['name'].(empty($columns['data'])?'':(' as '.$columns['data']));
				foreach ($post['order'] as $order)
				{
					if ($order['column'] == $index)
					{
						$this->orderby($columns['name'],$order['dir']);
					}
				}
			}
		}
		if (isset($post['action']) && $post['action'] === 'filter')
		{
			if (!empty($post['username']))
			{
				$this->where('user.name like ?',['%'.$post['username'].'%']);
			}
			if (!empty($post['telephone']))
			{
				$this->where('telephone like ?',['%'.$post['telephone'].'%']);
			}
			if (!empty($post['invit']))
			{
				$this->where('invit like ?',['%'.$post['invit'].'%']);
			}
			if (!empty($post['regtime_from']))
			{
				$this->where('regtime >= ?',[strtotime($post['regtime_from'])]);
			}
			if (!empty($post['regtime_to']))
			{
				$this->where('regtime <= ?',[strtotime($post['regtime_to'])]);
			}
			if (!empty($post['money_from']))
			{
				$this->where('money >= ?',[$post['money_from']]);
			}
			if (!empty($post['money_to']))
			{
				$this->where('money <= ?',[$post['money_to']]);
			}
			if ($post['vip'] != '')
			{
				$this->where('vip=?',[$post['vip']]);
			}
			if ($post['master'] != '')
			{
				$this->where('master=?',[$post['master']]);
			}
			if (!empty(intval($post['o_master'])))
			{
				$this->where('o_master = ?',[intval($post['o_master'])]);
			}
			if (!empty(intval($post['oid'])))
			{
				$this->where('oid = ?',[intval($post['oid'])]);
			}
	
		}
	
	
		$this->where('source.id=? or source.u_source=? and source.isdelete=0',[$session_id,$session_id]);
		return $this->select($parameter);
	}
	
	function count()
	{
		$result = $this->select('count(*)');
		return $result[0]['count(*)'];
	}
	
	
	function source_count($session_id)
	{
		//通过$session_id找到手机号id        用o_master和source来找用户
		$source_uid = $this->model('source')
		->where('id=?',[$session_id])
		->find([
				'uid'
		]);
		
		$result = $this->where('user.source = ? or user.o_master=?',[$session_id,$source_uid['uid']])->select('count(*)');
		return $result[0]['count(*)'];
	}
	/*
	 * 普通渠道总数
	 */
	function source_count2($session_id)
	{
		//通过$session_id找到手机号id        用o_master和source来找用户
		$source_uid = $this->model('source')
		->where('id=?',[$session_id])
		->find([
				'uid'
		]);
		
		$result = $this->where('user.source = ? or user.oid = ? ',[$session_id,$source_uid['uid']])->select('count(*)');
		return $result[0]['count(*)'];
	}
	function sourceunder_count($session_id)
	{
		$this->table('source','left join','source.uid=user.id');
		$result = $this->where('source.id = ? or source.u_source=? and source.isdelete=0',[$session_id,$session_id])->select('count(*)');
		return $result[0]['count(*)'];
	}
	
	
}