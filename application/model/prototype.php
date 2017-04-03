<?php
namespace application\model;
use system\core\model;
class prototypeModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function fetch(array $filter = [])
	{
		if (isset($filter['pid'])) {
			$this->where('pid=?',[$filter['pid']]);
		}
		if(isset($filter['isdelete']))
		{
			$this->where('isdelete=?',[$filter['isdelete']]);
		}
		if (isset($filter['type']))
		{
			$this->where('type=?',[$filter['type']]);
		}
		return parent::fetch($filter);
	}
}