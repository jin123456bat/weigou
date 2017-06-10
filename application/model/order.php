<?php
namespace application\model;

use system\core\model;

class orderModel extends model
{

	function __construct($table)
	{
		parent::__construct($table);
	}

	function datatables($post)
	{
		$parameter = [];
		
		foreach ($post['columns'] as $index => $columns)
		{
			if (! empty($columns['name']))
			{
				$parameter[] = $columns['name'] . (empty($columns['data']) ? '' : (' as ' . $columns['data']));
				foreach ($post['order'] as $order)
				{
					if ($order['column'] == $index)
					{
						$this->orderby($columns['name'], $order['dir']);
					}
				}
			}
		}
		
		if (isset($post['pk']) && is_array($post['pk']) && !empty($post['pk']))
		{
			foreach ($post['pk'] as $pk)
			{
				$this->where($pk['key'].'=?',[$pk['value']]);
			}
		}
		
		if (isset($post['ajaxData']) && is_array($post['ajaxData']) && !empty($post['ajaxData']))
		{
			foreach($post['ajaxData'] as $key => $value)
			{
				if ($key == 'where')
				{
					$this->where($value);
				}
				else
				{
					//假如是array的话使用or链接
					if (is_array($value))
					{
						$sql = '';
						$parameters = [];
						foreach ($value as $v)
						{
							$parameters[] = $v;
							if (empty($sql))
							{
								$sql .= $key.'=?';
							}
							else
							{
								$sql .= ' or '.$key.'=?';
							}
						}
						$this->where($sql,$parameters);
					}
					else
					{
						$this->where($key.'=?',[$value]);
					}
				}
			}
		}
		
		if (isset($post['keywords']) && !empty(trim($post['keywords'])))
		{
			$this->where('order.orderno like ?',['%'.trim($post['keywords']).'%']);
		}
		
		//解析status，让status支持复杂的表达式
		if (isset($post['status']) && !empty(trim($post['status'])))
		{
			$status = trim($post['status']);
			$pattern = '/\([^()]+\)/';
			if(preg_match_all($pattern, $status,$matches))
			{
				foreach ($matches[0] as $express)
				{
					$express = trim($express,'()');
					
					$expresses = explode('|', $express);
					$sql = '';
					$parameters = [];
					foreach ($expresses as $e)
					{
						list($key,$value) = explode(':', $e);
						$parameters[] = $value;
						if (empty($sql))
						{
							$sql .= $key.'=?';
						}
						else
						{
							$sql .= ' or '.$key.'=?';
						}
					}
					if (!empty($sql))
					{
						$sql = '('.$sql.')';
					}
					$this->where($sql,$parameters);
					
					$status = str_replace('('.$express.')', '', $status);
					
					$status = str_replace(',,', ',', $status);
				}
			}
			if (!empty($status))
			{
				$expresses = explode(',', $status);
				foreach ($expresses as $express)
				{
					list($key,$value) = explode(':', $express);
					$this->where($key.'=?',[$value]);
				}
			}
		}
		
		$result = $this->select($parameter);
		return $result;
	}

	/*
	 * 渠道订单查看
	 */
	
	function source_datatables($post, $session_uid, $session_id)
	{
		$this->table('user', 'left join', 'user.id=order.uid');
		$this->table('address', 'left join', 'address.id=order.address');
		$this->table('province', 'left join', 'province.id=address.province');
		$this->table('city', 'left join', 'city.id=address.city');
		$this->table('county', 'left join', 'county.id=address.county');
		$this->table('task_user', 'left join', 'task_user.orderno=order.orderno');
		
		$parameter = [];
		foreach ($post['columns'] as $index => $columns)
		{
			if (! empty($columns['name']))
			{
				$parameter[] = $columns['name'] . (empty($columns['data']) ? '' : (' as ' . $columns['data']));
				foreach ($post['order'] as $order)
				{
					if ($order['column'] == $index)
					{
						$this->orderby($columns['name'], $order['dir']);
					}
				}
			}
		}
		if (isset($post['action']) && $post['action'] === 'filter')
		{
			if (! empty($post['orderno']))
			{
				$this->table('suborder_store', 'left join', 'suborder_store.main_orderno=order.orderno');
				$this->where('order.orderno like ? or concat(replace(suborder_store.date,"-",""),suborder_store.id) like ?', [
					'%' . trim($post['orderno']) . '%',
					'%' . trim($post['orderno']) . '%'
				]);
			}
			if (! empty($post['createtime_from']))
			{
				$this->where('order.createtime >= ?', [
					strtotime($post['createtime_from'])
				]);
			}
			if (! empty($post['createtime_to']))
			{
				$this->where('order.createtime <= ?', [
					strtotime($post['createtime_to'])
				]);
			}
			if (! empty($post['uid']))
			{
				$this->where('order.uid=?', [
					$post['uid']
				]);
			}
			if (! empty($post['orderamount_from']))
			{
				$this->where('order.orderamount >= ?', [
					$post['orderamount_from']
				]);
			}
			if (! empty($post['orderamount_to']))
			{
				$this->where('order.orderamount <= ?', [
					$post['orderamount_to']
				]);
			}
			if (! empty($post['address_province']))
			{
				$this->where('address.province=?', [
					$post['address_province']
				]);
			}
			if (! empty($post['address_city']))
			{
				$this->where('address.city=?', [
					$post['address_city']
				]);
			}
			
			if ($post['pay_status'] != '')
			{
				$this->where('pay_status=?', [
					$post['pay_status']
				]);
			}
			
			if (! empty($post['pay_number']))
			{
				$this->where('pay_number=?', [
					trim($post['pay_number'])
				]);
			}
			if ($post['kouan'] != '')
			{
				if ($post['kouan'] == 3)
				{
					$this->where('order.need_kouan=?', [
						0
					]);
				}
				else
				{
					$this->where('order.kouan=? and order.need_kouan=?', [
						$post['kouan'],
						1
					]);
				}
			}
			if ($post['status'] != '')
			{
				$this->where('order.status=?', [
					$post['status']
				]);
			}
			if ($post['way_status'] != '')
			{
				$this->where('order.way_status=?', [
					$post['way_status']
				]);
			}
			
			// //////////////////////////////mychange///////根据地址号码来搜索
			if ($post['address_telephone'] != '')
			{
				$this->where('address.telephone like ? or address.name like ?', [
					'%' . trim($post['address_telephone']) . '%',
					'%' . trim($post['address_telephone']) . '%'
				]);
			}
			// //////////////////////////////
			if ($post['task'] != '')
			{
				if ($post['task'] == 1)
				{
					$this->where('task_user.orderno is not null');
				}
				else
				{
					$this->where('task_user.orderno is null');
				}
			}
			if (trim($post['note']) != '')
			{
				if ($post['note'] == 1)
				{
					$this->where('order.note != ""');
				}
				else
				{
					$this->where('order.note = ""');
				}
			}
		}
		else
		{
			$this->where('order.pay_status != 0'); // 默认不显示未支付的订单
		}
		
		$this->where('user.o_master=? or user.source=?', [
			$session_uid,
			$session_id
		]);
		return $this->select($parameter);
	}

	/*
	 * 普通渠道订单查看
	 */
	
	function source_datatables2($post, $session_uid, $session_id)
	{
		$this->table('user', 'left join', 'user.id=order.uid');
		$this->table('address', 'left join', 'address.id=order.address');
		$this->table('province', 'left join', 'province.id=address.province');
		$this->table('city', 'left join', 'city.id=address.city');
		$this->table('county', 'left join', 'county.id=address.county');
		$this->table('task_user', 'left join', 'task_user.orderno=order.orderno');
		
		$parameter = [];
		foreach ($post['columns'] as $index => $columns)
		{
			if (! empty($columns['name']))
			{
				$parameter[] = $columns['name'] . (empty($columns['data']) ? '' : (' as ' . $columns['data']));
				foreach ($post['order'] as $order)
				{
					if ($order['column'] == $index)
					{
						$this->orderby($columns['name'], $order['dir']);
					}
				}
			}
		}
		if (isset($post['action']) && $post['action'] === 'filter')
		{
			if (! empty($post['orderno']))
			{
				$this->table('suborder_store', 'left join', 'suborder_store.main_orderno=order.orderno');
				$this->where('order.orderno like ? or concat(replace(suborder_store.date,"-",""),suborder_store.id) like ?', [
					'%' . trim($post['orderno']) . '%',
					'%' . trim($post['orderno']) . '%'
				]);
			}
			if (! empty($post['createtime_from']))
			{
				$this->where('order.createtime >= ?', [
					strtotime($post['createtime_from'])
				]);
			}
			if (! empty($post['createtime_to']))
			{
				$this->where('order.createtime <= ?', [
					strtotime($post['createtime_to'])
				]);
			}
			if (! empty($post['uid']))
			{
				$this->where('order.uid=?', [
					$post['uid']
				]);
			}
			if (! empty($post['orderamount_from']))
			{
				$this->where('order.orderamount >= ?', [
					$post['orderamount_from']
				]);
			}
			if (! empty($post['orderamount_to']))
			{
				$this->where('order.orderamount <= ?', [
					$post['orderamount_to']
				]);
			}
			if (! empty($post['address_province']))
			{
				$this->where('address.province=?', [
					$post['address_province']
				]);
			}
			if (! empty($post['address_city']))
			{
				$this->where('address.city=?', [
					$post['address_city']
				]);
			}
			
			if ($post['pay_status'] != '')
			{
				$this->where('pay_status=?', [
					$post['pay_status']
				]);
			}
			
			if (! empty($post['pay_number']))
			{
				$this->where('pay_number=?', [
					trim($post['pay_number'])
				]);
			}
			if ($post['kouan'] != '')
			{
				if ($post['kouan'] == 3)
				{
					$this->where('order.need_kouan=?', [
						0
					]);
				}
				else
				{
					$this->where('order.kouan=? and order.need_kouan=?', [
						$post['kouan'],
						1
					]);
				}
			}
			if ($post['status'] != '')
			{
				$this->where('order.status=?', [
					$post['status']
				]);
			}
			if ($post['way_status'] != '')
			{
				$this->where('order.way_status=?', [
					$post['way_status']
				]);
			}
			
			// //////////////////////////////mychange///////根据地址号码来搜索
			if ($post['address_telephone'] != '')
			{
				$this->where('address.telephone like ? or address.name like ?', [
					'%' . trim($post['address_telephone']) . '%',
					'%' . trim($post['address_telephone']) . '%'
				]);
			}
			// //////////////////////////////
			if ($post['task'] != '')
			{
				if ($post['task'] == 1)
				{
					$this->where('task_user.orderno is not null');
				}
				else
				{
					$this->where('task_user.orderno is null');
				}
			}
			if (trim($post['note']) != '')
			{
				if ($post['note'] == 1)
				{
					$this->where('order.note != ""');
				}
				else
				{
					$this->where('order.note = ""');
				}
			}
		}
		else
		{
			$this->where('order.pay_status != 0'); // 默认不显示未支付的订单
		}
		
		$oiduser = $this->model("user")
			->where("oid=?", [
			$session_uid
		])
			->select([
			'id'
		]);
		
		$ouser = array();
		if ($oiduser)
		{
			foreach ($oiduser as &$o)
			{
				$ouser[] = $o['id'];
			
			}
			// $oiduser = implode(",", $oiduser);
			// /echo $oiduser;exit;
			
			$this->where('user.oid in (select user.id from user where user.oid=?) or user.source=? or user.oid=? or user.id=?', [
				$session_uid,
				$session_id,
				$session_uid,
				$session_uid
			]);
		
		}
		else
		{
			
			$this->where('user.source=? or user.oid=? or user.id=?', [
				$session_id,
				$session_uid,
				$session_uid
			]);
		}
		
		// $this->where('user.source=? or user.oid=?', [$session_id, $session_uid]);
		return $this->select($parameter);
	}

	/*
	 * 渠道订单总数
	 */
	function source_count($session_id, $session_uid)
	{
		
		$result = $this->table('user', 'left join', 'user.id=order.uid')
			->where('user.o_master=? or user.source=?', [
			$session_uid,
			$session_id
		])
			->find('count(*)');
		
		return $result['count(*)'];
	}

	/*
	 * 普通渠道订单总数
	 */
	function source_count2($session_id, $session_uid)
	{
		
		$result = $this->table('user', 'left join', 'user.id=order.uid')
			->where('user.source=? or user.oid =?', [
			$session_id,
			$session_uid
		])
			->find('count(*)');
		
		return $result['count(*)'];
	}

	function fetch(array $filter = [])
	{
		if (isset($filter['uid']))
		{
			$this->where('order.uid=?', [
				$filter['uid']
			]);
		}
		if (isset($filter['pay_status']))
		{
			$this->where('order.pay_status=?', [
				$filter['pay_status']
			]);
		}
		if (isset($filter['way_status']))
		{
			$this->where('order.way_status=?', [
				$filter['way_status']
			]);
		}
		if (isset($filter['receive']))
		{
			$this->where('order.receive=?', [
				$filter['receive']
			]);
		}
		if (isset($filter['status']))
		{
			$this->where('order.status=?', [
				$filter['status']
			]);
		}
		if (isset($filter['isdelete']))
		{
			$this->where('order.isdelete=?', [
				$filter['isdelete']
			]);
		}
		return parent::fetch($filter);
	}

	function fetchAll(array $filter = [])
	{
		return $this->fetch($filter);
	}
}