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
		$this->table('user', 'left join', 'user.id=order.uid');
		$this->table('address', 'left join', 'address.id=order.address');
		//$this->table('province', 'left join', 'province.id=address.province');
		//$this->table('city', 'left join', 'city.id=address.city');
		//$this->table('county', 'left join', 'county.id=address.county');
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
				// 修复搜索主订单号，数据显示异常的问题，
				// $this->table('suborder_store','left join','suborder_store.main_orderno=order.orderno');
				// $this->where('order.orderno like ? or concat(replace(suborder_store.date,"-",""),suborder_store.id) like ?',['%'.trim($post['orderno']).'%','%'.trim($post['orderno']).'%']);
				
				$this->where('order.orderno like ? or order.orderno = (select main_orderno from suborder_store where order.orderno=suborder_store.main_orderno and concat(replace(suborder_store.date,"-",""),suborder_store.id) like ? limit 1)', [
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
				if ($post['pay_status'] == 1)
				{
					$this->where('pay_status=1 or pay_status=4');
				}
				else
				{
					$this->where('pay_status=?', [
						$post['pay_status']
					]);
				}
			}
			
			if (! empty($post['pay_number']))
			{
				$this->where('pay_number=?', [
					trim($post['pay_number'])
				]);
			}
			if ($post['erp'] != '')
			{
				$this->where('order.erp=?',[$post['erp']]);
			}
			if ($post['status'] != '')
			{
				$this->where('order.status=?', [
					$post['status']
				]);
			}
			if ($post['way_status'] != '')
			{
				if ($post['way_status'] == 0)
				{
					$this->where('order.way_status in (?)',[0,2]);
				}
				else
				{
					$this->where('order.way_status=?', [
						$post['way_status']
					]);
				}
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
		
		return $this->select($parameter);
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

	function count()
	{
		$result = $this->find('count(*)');
		return $result['count(*)'];
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