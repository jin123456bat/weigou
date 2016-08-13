<?php
namespace application\model;
use system\core\model;
class shipModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function get($code)
	{
		$name = $this->where('code=?',[$code])->find('name');
		return $name['name']?$name['name']:NULL;
	}
}