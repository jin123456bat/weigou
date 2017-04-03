<?php
namespace application\model;
use system\core\model;
class storeModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function fetch(array $filter = [])
	{
		if (isset($filter['isdelete']))
		{
			$this->where('store.isdelete=?',[$filter['isdelete']]);
		}
		return parent::fetch($filter);
	}
	
	function fetchAll(array $filter = [])
	{
		return $this->fetch($filter);
	}
}