<?php
namespace application\model;
use system\core\model;
class order_logModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function add($orderno,$content,$aid,$status = 1,$note = '')
	{
		return $this->insert([$orderno,$_SERVER['REQUEST_TIME'],$content,$aid,$note,$status]);
	}
}