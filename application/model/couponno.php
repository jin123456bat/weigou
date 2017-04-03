<?php
namespace application\model;
use system\core\model;
class couponnoModel extends model
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
			if (!empty($columns['name']))
			{
				$parameter[] = $columns['name'].(empty($columns['data'])?'':(' as '.$columns['data']));
				foreach ($post['order'] as $order)
				{
					if ($order['column'] == $index)
					{
						$this->orderby($columns['name'],$index['dir']);
					}
				}
			}
		}
		if (isset($post['action']) && $post['action'] === 'filter')
		{
			if (!empty($post['couponno']))
			{
				$this->where('couponno like ?',['%'.$post['couponno'].'%']);
			}
			if (!empty($post['total_from']))
			{
				$this->where('total >= ?',[$post['total_from']]);
			}
			if (!empty($post['total_to']))
			{
				$this->where('total <= ?',[$post['total_to']]);
			}
			if (!empty($post['times_from']))
			{
				$this->where('times >= ?',[$post['times_from']]);
			}
			if (!empty($post['times_to']))
			{
				$this->where('times <= ?',[$post['times_to']]);
			}
			if (!empty($post['coupon_name']))
			{
				$this->where('coupon_name like ?',['%'.$post['coupon_name'].'%']);
			}
			if (!empty($post['coupon_max_from']))
			{
				$this->where('coupon_max >= ?',[$post['coupon_max_from']]);
			}
			if (!empty($post['coupon_max_to']))
			{
				$this->where('coupon_max <= ?',[$post['coupon_max_to']]);
			}
			if (!empty($post['coupon_value_from']))
			{
				$this->where('coupon_value >= ?',[$post['coupon_value_from']]);
			}
			if (!empty($post['coupon_value_to']))
			{
				$this->where('coupon_value <= ?',[$post['coupon_value_to']]);
			}
			if (!empty($post['coupon_time_from']))
			{
				$this->where('coupon_time >= ?',[$post['coupon_time_from']]);
			}
			if (!empty($post['coupon_time_to']))
			{
				$this->where('coupon_time <= ?',[$post['coupon_time_to']]);
			}
		}
		return $this->where('isdelete=?',[0])->select($parameter);
	}
	
	function count()
	{
		$result = $this->select('count(*)');
		return $result[0]['count(*)'];
	}
}