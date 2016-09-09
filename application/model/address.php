<?php
namespace application\model;
use system\core\model;
class addressModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	/**
	 * 获取地址信息
	 * {@inheritDoc}
	 * @see \system\core\model::fetch()
	 */
	function fetch(array $filter = array())
	{
		if (isset($filter['isdelete']))
		{
			$this->where('address.isdelete = ?',[$filter['isdelete']]);
		}
		if (isset($filter['uid']))
		{
			$this->where('address.uid=?',[$filter['uid']]);
		}
		return parent::fetch($filter);
	}
	
	/**
	 * @param array $filter
	 * @return \system\core\Ambigous
	 */
	function fetchAll(array $filter = array())
	{
		$this->table('province','left join','province.id=address.province');
		$this->table('city','left join','city.id=address.city');
		$this->table('county','left join','county.id=address.county');
		return $this->fetch($filter);
	}
}