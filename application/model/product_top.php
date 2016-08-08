<?php
namespace application\model;
use system\core\model;
class product_topModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function fetch(array $filter = [])
	{
		if (isset($filter['isdelete']))
		{
			$this->where('product.isdelete=?',[$filter['isdelete']]);
		}
		if (isset($filter['status']))
		{
			if ($filter['status']==1)
			{
				$this->where('(auto_status = 0 and status = 1) or (auto_status = 1 and avaliabletime_from <= ? and avaliabletime_to >= ?)',[$_SERVER['REQUEST_TIME'],$_SERVER['REQUEST_TIME']]);
			}
			else if ($filter['status']==0)
			{
				$this->where('(auto_status = 0 and status = 0) or (auto_status = 1 and avaliabletime_from > ?  or  avaliabletime_to < ?)',[$_SERVER['REQUEST_TIME'],$_SERVER['REQUEST_TIME']]);
			}
		}
		return parent::fetch($filter);
	}
	
	function fetchAll($filter)
	{
		$this->table('product','left join','product.id=product_top.pid');
		$this->table('store','left join','product.store=store.id');
		return $this->fetch($filter);
	}
}