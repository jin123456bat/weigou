<?php
namespace application\model;
use system\core\model;
class taskModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function fetch(array $filter = [])
	{
		if (isset($filter['isdelete']))
		{
			$this->where('task.isdelete=?',[$filter['isdelete']]);
			
		}
		
		return parent::fetch($filter);
	}
	
	function fetchAll(array $filter = [])
	{
		if (isset($filter['isdelete']))
		{
			$this->where('product.isdelete=?',[$filter['isdelete']]);
		}
		if (isset($filter['status']))
		{
			if ($filter['status']==1)
			{
				$this->where('(product.auto_status = 0 and product.status = 1) or (product.auto_status = 1 and product.avaliabletime_from <= ? and product.avaliabletime_to >= ?)',[$_SERVER['REQUEST_TIME'],$_SERVER['REQUEST_TIME']]);
			}
			else if ($filter['status']==0)
			{
				$this->where('(product.auto_status = 0 and product.status = 0) or (product.auto_status = 1 and (product.avaliabletime_from > ?  or  product.avaliabletime_to < ?))',[$_SERVER['REQUEST_TIME'],$_SERVER['REQUEST_TIME']]);
			}
		}
		$this->table('product','left join','product.id=task.pid');
		$this->table('store','left join','store.id=product.store');
		return $this->fetch($filter);
	}
}