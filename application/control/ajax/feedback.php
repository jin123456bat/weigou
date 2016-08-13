<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class feedback extends ajax
{
	function remove()
	{
		$id = $this->post('id',0,'intval');
		if (!empty($id))
		{
			if($this->model('feedback')->where('id=?',[$id])->limit(1)->update([
				'isdelete' => 1,
				'deletetime' => $_SERVER['REQUEST_TIME']
			]))
			{
				return new json(json::OK);
			}
		}
		return new json(json::PARAMETER_ERROR);
	}
}