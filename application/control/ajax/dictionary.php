<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class dictionary extends ajax
{
	function setCountryLogo()
	{
		$id = $this->post('id',0,'intval');
		$logo = $this->post('logo',NULL,'intval');
		if(empty($id)||empty($logo))
			return new json(json::PARAMETER_ERROR);
		if($this->model('country')->where('id=?',[$id])->limit(1)->update('logo',$logo))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
}