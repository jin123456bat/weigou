<?php
namespace application\model;
use system\core\model;
class categoryModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function fetch(array $filter = array())
	{
		if (isset($filter['isdelete']))
		{
			$this->where('category.isdelete=?',[$filter['isdelete']]);
		}
		if (isset($filter['cid']))
		{
			if (empty($filter['cid']))
			{
				$this->where('category.cid is null');
			}
			else
			{
				$this->where('category.cid = ?',[$filter['cid']]);
			}
		}
		return parent::fetch($filter);
	}
	
	function fetchAll(array $filter = array())
	{
		$this->table('upload','left join','upload.id=category.logo');
		$this->table('category as c_category','left join','c_category.id=category.cid');
		if (isset($filter['pid']))
		{
			if (isset($filter['isdelete']))
			{
				$this->where('category_product.isdelete=?',[$filter['isdelete']]);
			}
			$this->where('category_product.pid=?',[$filter['pid']]);
			$this->table('category_product','left join','category_product.cid=category.id');
		}
		return $this->fetch($filter);
	}
}