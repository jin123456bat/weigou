<?php
namespace application\model;
use system\core\model;
class bankcardModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function fetch(array $filter = array())
	{
		if (isset($filter['isdelete']))
		{
			$this->where('bankcard.isdelete=?',[$filter['isdelete']]);
		}
		if (isset($filter['uid']))
		{
			$this->where('bankcard.uid=?',[$filter['uid']]);
		}
		return parent::fetch($filter);
	}
	
	function fetchAll(array $filter = array())
	{
		$this->table('province','left join','province.id=bankcard.province');
		$this->table('city','left join','city.id=bankcard.city');
		return $this->fetch($filter);
	}
}