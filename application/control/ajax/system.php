<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class system extends ajax
{
	function set()
	{
		$name = $this->post('name');
		$type = $this->post('type');
		$prototype = $this->post('prototype');
		$value = $this->post('value');
		if($this->model('system')->where('name=? and type=?',[$name,$type])->limit(1)->update($prototype,$value))
		{
			return new json(json::OK);
		}
		return new json(json::OK);
	}
}