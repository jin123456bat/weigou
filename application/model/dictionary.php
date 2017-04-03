<?php
namespace application\model;
use system\core\model;
class dictionaryModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function get($id,$field= '*')
	{
		$result = $this->where('id=?',[$id])->find($field);
		if($field=='*')
			return $result;
		return isset($result[$field])?$result[$field]:NULL;
	}
}