<?php
namespace application\model;
use system\core\model;
class product_imgModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function fetch(array $filter = [])
	{
		if (isset($filter['isdelete']))
		{
			$this->where('product_img.isdelete=?',[$filter['isdelete']]);
		}
		if(isset($filter['pid']))
		{
			$this->where('product_img.pid=?',[$filter['pid']]);
		}
		if (isset($filter['position']))
		{
			$this->where('product_img.position=?',[$filter['position']]);
		}
		return parent::fetch($filter);
	}
	
	function fetchAll(array $filter = [])
	{
		$this->table('upload','left join','product_img.fid=upload.id');
		return $this->fetch($filter);
	}
}