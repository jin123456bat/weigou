<?php
namespace application\model;
use system\core\model;
class productModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function fetch(array $filter = array())
	{
		if (isset($filter['isdelete']))
		{
			$this->where('product.isdelete=?',[$filter['isdelete']]);
		}
		if (isset($filter['name']) && !empty($filter['name']))
		{
			$this->where('product.name like ? or product.sku like ?',[$filter['name'],$filter['name']]);
		}
		if (isset($filter['status']))
		{
			if ($filter['status']==1)
			{
				$this->where('(auto_status = 0 and status = 1) or (auto_status = 1 and avaliabletime_from <= ? and avaliabletime_to >= ?)',[$_SERVER['REQUEST_TIME'],$_SERVER['REQUEST_TIME']]);
			}
			else if ($filter['status']==0)
			{
				$this->where('(auto_status = 0 and status = 0) or (auto_status = 1 and (avaliabletime_from > ?  or  avaliabletime_to < ?))',[$_SERVER['REQUEST_TIME'],$_SERVER['REQUEST_TIME']]);
			}
		}
		return parent::fetch($filter);
	}
	
	function fetchAll(array $filter = array())
	{
		$this->table('store','left join','store.id=product.store');
		return $this->fetch($filter);
	}
	
	function datatables($post)
	{
		if (isset($post['ajaxData']) && is_array($post['ajaxData']))
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
			$this->where('product.id=? or name like ?',[trim($post['keywords']),'%'.trim($post['keywords']).'%']);
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
	
	function count()
	{
		$result = $this->select('count(*)');
		return $result[0]['count(*)'];
	}
}