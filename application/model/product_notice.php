<?php
namespace application\model;
use system\core\model;

class product_noticeModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function datatables($post)
	{
		$this->table('product','left join','product.id=product_notice.pid');
		$this->table('user','left join','user.id=product_notice.uid');
		
		if (isset($post['ajaxData']) && is_array($post['ajaxData']))
		{
			foreach($post['ajaxData'] as $key => $value)
			{
				$this->where($key.'=?',[$value]);
			}
		}
	
		$parameter = [];
		foreach ($post['columns'] as $columns)
		{
			if (!empty($columns['name']))
			{
				$parameter[] = $columns['name'].(empty($columns['data'])?'':(' as '.$columns['data']));
			}
		}
		if(isset($post['order']))
		{
			foreach ($post['order'] as $order)
			{
				$this->orderby($columns[$order['column']]['name'],$order['dir']);
			}
		}
		if (isset($post['keywords']) && !empty($post['keywords']))
		{
			$this->where('user.name like ? or telephone like ?',['%'.trim($post['keywords']).'%','%'.trim($post['keywords']).'%']);
		}
		if (isset($post['status']) && !empty($post['status']))
		{
			$status = explode(',', $post['status']);
			foreach ($status as $stat)
			{
				list($name,$value) = explode(':', $stat);
				$this->where($name.'=?',[$value]);
			}
		}
		return $this->select($parameter);
	}
}