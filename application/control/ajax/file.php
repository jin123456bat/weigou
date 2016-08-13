<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class file extends ajax
{
	function remove()
	{
		$id = $this->post('id');
		if (!empty($id))
		{
			$file = $this->model('upload')->where('id=?',[$id])->find();
			if (!empty($file))
			{
				unlink($file['path']);
				$this->model('upload')->where('id=?',[$id])->delete();
				return new json(json::OK);
			}
			return new json(json::PARAMETER_ERROR);
		}
		return new json(json::PARAMETER_ERROR);
	}
}