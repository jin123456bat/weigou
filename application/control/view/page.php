<?php
namespace application\control\view;
use system\core\view;
class page extends view
{
	function __construct()
	{
		$this->_csrf_token_refresh = false;
		parent::__construct();
	}
	
	function detail()
	{
		$this->response->addHeader('Content-Type' ,'text/html;charset=utf-8');
		$id = $this->get('id');
		$page = $this->model('page')->where('id=?',[$id])->find();
		if ($this->get('html','true') == 'true')
		{
			$this->assign('page',$page);
			return $this;
		}
		else
		{
			return $page['content'];
		}
	}
}