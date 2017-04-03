<?php
namespace application\model;
use system\core\model;

class product_notice_templateModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function datatables($post)
	{
		if (isset($post['ajaxData']) && is_array($post['ajaxData']))
		{
			foreach($post['ajaxData'] as $key => $value)
			{
				$this->where($key.'=?',[$value]);
			}
		}
	
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
		if (isset($post['keywords']) && !empty($post['keywords']))
		{
			$this->where('title like ? or content like ?',['%'.trim($post['keywords']).'%','%'.trim($post['keywords']).'%']);
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