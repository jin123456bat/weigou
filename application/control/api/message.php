<?php
namespace application\control\api;
use application\message\json;
class message extends common
{
	function __construct()
	{
		
	}
	
	function read()
	{
		$id = $this->data('id');
		if(empty($id))
		{
			return new json(json::PARAMETER_ERROR,'id不能为空');
		}
		
		if($this->model('message')->read($id))
		{
			$message = $this->model('message')->where('id=?',[$id])->find();
			return new json(json::OK,NULL,$message);
		}
		return new json(json::PARAMETER_ERROR,'消息不存在');
	}
	
	/**
	 * 消息列表
	 * @return \application\message\json
	 */
	function lists()
	{
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		if(empty($uid))
		{
			return new json(json::NOT_LOGIN);
		}
		
		$start = $this->data('start',0);
		$length = $this->data('length',10);
		
		$filter = [
			'uid' => $uid,
			'isdelete' => 0,
			'sort' => [['isread','asc'],['createtime','desc']],
			'start' => $start,
			'length' => $length,
			'parameter' => ['id','title','isread','createtime'],
		];
		$message = $this->model('message')->fetch($filter);
		
		unset($filter['start']);
		unset($filter['length']);
		$filter['parameter'] = 'count(*)';
		$total = $this->model('message')->fetch($filter);
		
		$messageReturnModel = [
			'total' =>  isset($total['count(*)'])?$total['count(*)']:0,
			'count' => count($message),
			'start' => $start,
			'length' => $length,
		];
		return new json(json::OK,NULL,$messageReturnModel);
	}
	
	/**
	 * 消息删除
	 * @return \application\message\json
	 */
	function remove()
	{
		$id = $this->data('id');
		if (empty($id))
		{
			return new json(json::PARAMETER_ERROR,'id不能为空');
		}
		if($this->model('message')->remove($id))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR,'消息不存在');
	}
}