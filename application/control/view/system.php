<?php
namespace application\control\view;
use system\core\view;
class system extends view
{
	function task()
	{
		$name = $this->get('name','logo');
		$fileid = $this->model('system')->get($name,'task');
		$filepath = $this->model('upload')->get($fileid,'path');
		$this->response->addHeader('Content-Type','image/png');
		return file_get_contents($filepath);
	}
}