<?php
namespace application\model;
use system\core\model;
class uploadModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function get($id,$name = '*')
	{
		$result = $this->where('id=?',[$id])->find($name);
		return isset($result[$name])?$result[$name]:NULL;
	}
}