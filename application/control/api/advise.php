<?php
namespace application\control\api;
use application\message\json;
class advise extends common
{
	private $_response;
	function __construct()
	{
		parent::__construct();
		$this->_response = $this->init();
	}
	
	function lists()
	{
		$advise = $this->model('advise')->where('isdelete=?',[0])->orderby('sort','asc')->select();
		return new json(json::OK,NULL,$advise);
	}
	
	function submit()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		$id = $this->data('id');
		$cid = $this->data('cid');
		if (!empty($id) && !empty($cid))
		{
			$ip = ip();
			$userHelper = new \application\helper\user();
			$uid = $userHelper->isLogin();
			
			if(empty($this->model('advise_user')->where('ip=? and cid=?',[$ip,$cid])->find()))
			{
				if (!empty($uid))
				{
					if(!empty($this->model('advise_user')->where('uid=? and cid=?',[$uid,$cid])->find()))
					{
						return new json(json::PARAMETER_ERROR,'每个用户只能投一次');
					}
				}
				if($this->model('advise_user')->insert([
					'ip' => $ip,
					'uid' => $uid,
					'cid'=> $cid,
					'aid' => $id,
					'createtime' => $_SERVER['REQUEST_TIME']
				]))
				{
					return new json(json::OK);
				}
				return new json(json::PARAMETER_ERROR);
			}
			return new json(json::PARAMETER_ERROR,'每个用户只能投一次');
		}
		return new json(json::PARAMETER_ERROR);
	}
}