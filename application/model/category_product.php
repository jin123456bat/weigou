<?php
namespace application\model;
use system\core\model;
class category_productModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function fetch(array $filter = [])
	{
		if (isset($filter['cid']))
		{
			$this->where('category_product.cid=?',[$filter['cid']]);
		}
		if(isset($filter['isdelete']))
		{
			$this->where('category_product.isdelete=? and product.isdelete=?',[$filter['isdelete'],$filter['isdelete']]);
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
	
	function fetchAll(array $filter = [])
	{
		$this->table('product','left join','product.id=category_product.pid');
		
		$this->table('store','left join','product.store=store.id');
		return $this->fetch($filter);
	}
}