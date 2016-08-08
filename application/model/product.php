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
		$this->table('store','left join','store.id=product.store');
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
			if (!empty($post['sku']))
			{
				$this->where('product.sku like ?',['%'.$post['sku'].'%']);
			}
			if (!empty($post['name']))
			{
				$this->where('product.name like ?',['%'.$post['name'].'%']);
			}
			if (!empty($post['category']))
			{
				$this->table('category_product','left join','category_product.pid=product.id');
				$this->where('category_product.cid=?',[$post['category']]);
			}
			if (!empty($post['price_from']))
			{
				$this->where('product.price >= ? or product.v1price >= ? or product.v2price >= ?',[$post['price_from'],$post['price_from'],$post['price_from']]);
			}
			if(!empty($post['price_to']))
			{
				$this->where('product.price <= ? or product.v1price <= ? or product.v2price <= ?',[$post['price_to'],$post['price_to'],$post['price_to']]);
			}
			if (!empty($post['store']))
			{
				$this->where('product.store = ?',[$post['store']]);
			}
			if (!empty($post['stock_from']))
			{
				$this->where('product.stock >= ?',[$post['stock_from']]);
			}
			if (!empty($post['stock_to']))
			{
				$this->where('product.stock <= ?',[$post['stock_to']]);
			}
			if ($post['product_status'] != '')
			{
				if ($post['product_status'] == 1)
				{
					$this->where('(auto_status = 0 and status = 1) or (auto_status = 1 and avaliabletime_from <= ? and avaliabletime_to >= ?)',[$_SERVER['REQUEST_TIME'],$_SERVER['REQUEST_TIME']]);
				}
				else if ($post['product_status'] == 0)
				{
					$this->where('(auto_status = 0 and status = 0) or (auto_status = 1 and (avaliabletime_from > ?  or  avaliabletime_to < ?))',[$_SERVER['REQUEST_TIME'],$_SERVER['REQUEST_TIME']]);
				}
			}
			if ($post['outside'] != '')
			{
				$this->where('product.outside=?',[$post['outside']]);
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