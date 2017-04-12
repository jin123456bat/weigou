<?php
namespace application\model;

use system\core\model;
class roleModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	/**
	 * 权限检查
	 * @param unknown $groupId 组id
	 * @param unknown $model 模块名称
	 * @param unknown $power 权限  可以是多个权限互加
	 */
	function checkPower($groupId,$model,$power)
	{

		$result = $this->where('id=?',array($groupId))->limit(1)->find();
		if(isset($result[$model]))
		{
			return ((int)$result[$model] & $power) === $power;
		}
		return false;
	}
}