<?php
namespace application\model;
use system\core\model;
class couponModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}

	function fetch(array $filter = [])
	{
		if (isset($filter['isdelete']))
		{
			$this->where('isdelete=?',[$filter['isdelete']]);
		}
		if (isset($filter['used']))
		{
			$this->where('used=?',[$filter['used']]);
		}
		if (isset($filter['uid']))
		{
			$this->where('uid=?',[$filter['uid']]);
		}
		return parent::fetch($filter);
	}

	function datatables($post)
	{
		$this->table('user','left join','user.id=coupon.uid');
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
			if (!empty($post['name']))
			{
				$this->where('name like ?',['%'.$post['name'].'%']);
			}
			if (!empty($post['createtime_from']))
			{
				$this->where('createtime >= ?',[strtotime($post['createtime_from'])]);
			}
			if (!empty($post['createtime_to']))
			{
				$this->where('createtime <= ?',[strtotime($post['createtime_to'])]);
			}
			if (!empty($post['uid']))
			{
				$this->where('uid = ?',[$post['uid']]);
			}
			if (!empty($post['max_from']))
			{
				$this->where('max >= ?',[$post['max_from']]);
			}
			if (!empty($post['max_to']))
			{
				$this->where('max <= ?',[$post['max_to']]);
			}
			if (!empty($post['value_from']))
			{
				$this->where('value >= ?',[$post['value_from']]);
			}
			if (!empty($post['value_to']))
			{
				$this->where('value <= ?',[$post['value_to']]);
			}
			if ($post['isdelete'] != '')
			{
				$this->where('isdelete=?',[$post['isdelete']]);
			}
			if (!empty($post['endtime_from']))
			{
				$this->where('endtime >= ? ',[strtotime($post['endtime_from'])]);
			}
			if (!empty($post['endtime_to']))
			{
				$this->where('endtime <= ?',[strtotime($post['endtime_to'])]);
			}
			if (!empty($post['couponno']))
			{
				$this->where('couponno=?',[$post['couponno']]);
			}
			if($post['source'] != '')
			{
				$this->where('source=?',[$post['source']]);
			}
			if ($post['used'] != '')
			{
				$this->where('used=?',[$post['used']]);
			}
		}
		return $this->select($parameter);
	}

	function count()
	{
		$result = $this->select('count(*)');
		return $result[0]['count(*)'];
	}
}