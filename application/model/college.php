<?php
namespace application\model;
use system\core\model;
class collegeModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function fetch(array $filter = [])
	{
		if (isset($filter['title']) && !empty($filter['title']))
		{
			$this->where('title like ?',['%'.$filter['title'].'%']);
		}
		if (isset($filter['uid']) && !empty($filter['uid']))
		{
			$this->where('uid=?',[$filter['uid']]);
		}
		if (isset($filter['createtime'][0]) && !empty($filter['createtime'][0]))
		{
			$this->where('createtime >= ? and createtime <= ?',[$filter['createtime'][0],$filter['createtime'][1]]);
		}
		if (isset($filter['isdelete']))
		{
			$this->where('college.isdelete=?',[$filter['isdelete']]);
		}
		return parent::fetch($filter);
	}
	
	function fetchAll(array $filter = [])
	{
		$this->table('user','left join','user.id=college.uid');
		$this->table('upload as upload1','left join','upload1.id=college.logo1');
		$this->table('upload as upload2','left join','upload2.id=college.logo2');
		return $this->fetch($filter);
	}
}