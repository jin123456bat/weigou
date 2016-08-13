<?php
namespace application\control\api;
use application\message\json;
class feedback extends common
{
	private $_response;
	function __construct()
	{
		parent::__construct();
		$this->_response = $this->init();
	}
	function submit()
	{
		if (!empty($this->_response))
		{
			return $this->_response;
		}
		
		$content = $this->data('content','');
		if (empty($content)) {
			return new json(json::PARAMETER_ERROR,'请填写反馈内容');
		}
		$telephone = $this->data('telephone');
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();

		if($this->model('feedback')->insert([
			'content' => $content,
			'time' => $_SERVER['REQUEST_TIME'],
			'telephone' => $telephone,
			'uid' => $uid,
			'isdelete' => 0,
			'deletetime' => 0,
		]))
		{
			return new json(json::OK,'感谢您的反馈，我们会第一时间与您联系');
		}
		return new json(json::PARAMETER_ERROR);
	}
}