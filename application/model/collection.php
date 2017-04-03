<?php
namespace application\model;
use system\core\model;
class collectionModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function get($pid,$content)
	{
		return $this->where('pid=? and content=?',[$pid,$content])->find();
	}
	
	function fetch(array $filter = [])
	{
		if (isset($filter['pid']))
		{
			$this->where('collection.pid=?',[$filter['pid']]);
		}
		if(isset($filter['isdelete']))
		{
			$this->where('collection.isdelete=?',[$filter['isdelete']]);
		}
		if (isset($filter['available']))
		{
			$this->where('collection.available = ?',[$filter['available']]);
		}
		return parent::fetch($filter);
	}
	
	function fetchAll(array $filter = [])
	{
		$this->table('upload','left join','upload.id=collection.logo');
		return $this->fetch($filter);
	}
}