<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
use application\helper\user;
class favourite extends ajax
{
	/**
	 * 添加收藏
	 */
	function create()
	{
		$userHelper = new user();
		$uid = $userHelper->isLogin();
		if(empty($uid))
		{
			return new json(json::NOT_LOGIN);
		}
	
		$id = $this->post('id');
		if (empty($id))
			return new json(json::PARAMETER_ERROR);
	
		$result = $this->model('favourite')->where('uid=? and pid=? and isdelete=?',[$uid,$id,0])->find();
		if (!empty($result))
			return new json(json::PARAMETER_ERROR,'已经收藏了');
	
		if($this->model('favourite')->insert([
			'uid' => $uid,
			'pid' => $id,
			'createtime' => $_SERVER['REQUEST_TIME'],
			'isdelete' => 0,
			'deletetime' => 0,
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	/**
	 * 移除收藏
	 */
	function remove()
	{
		$userHelper = new user();
		$uid = $userHelper->isLogin();
		if(empty($uid))
		{
			return new json(json::NOT_LOGIN);
		}
		
		$id = $this->post('id');
		if (empty($id))
			return new json(json::PARAMETER_ERROR);
	
		if($this->model('favourite')->where('uid=? and pid=? and isdelete=?',[$uid,$id,0])->update([
			'isdelete' => 1,
			'deletetime' => $_SERVER['REQUEST_TIME'],
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR,'尚未添加收藏');
	}
}