<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class question extends ajax
{
	function remove()
	{
		$id = $this->post('id');
		if($this->model('question')->where('id=?',[$id])->update([
			'isdelete' => 1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
			return new json(json::OK);
		}
	}
	
	function remove_category()
	{
		$id = $this->post('id');
		if($this->model('question_category')->where('id=?',[$id])->update([
			'isdelete' => 1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
}