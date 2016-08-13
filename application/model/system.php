<?php
namespace application\model;
use system\core\model;
class systemModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	/**
	 * 获取系统配置
	 * @param unknown $name
	 * @param unknown $type
	 * @return NULL
	 */
	function get($name,$type)
	{
		$result = $this->where('name=? and type=?',[$name,$type])->find();
		return isset($result['value'])?$result['value']:NULL;
	}
	
	/**
	 * 添加或更改设置
	 * @param unknown $name
	 * @param unknown $type
	 * @param unknown $value
	 * @return \system\core\Ambigous
	 */
	function set($name,$type,$value,$description = '')
	{
		if ($this->get($name, $type) === NULL)
		{
			return $this->insert([$name,$type,$value,$description]);
		}
		else
		{
			return $this->where('name=? and type=?',[$name,$type])->update('value',$value);
		}
	}
}