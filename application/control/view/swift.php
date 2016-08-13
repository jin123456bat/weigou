<?php
namespace application\control\view;
use system\core\view;
class swift extends view
{
	function __construct()
	{
		$this->_csrf_token_refresh = false;
		parent::__construct();
	}
	
	function detail()
	{
		$uid = $this->post('uid');
		if (!empty($uid))
		{
			//$swift = $this->model('swift')->where('uid=?',[$uid])->orderby('time','desc')->select();
			//$this->assign('swift', $swift);
			$this->assign('uuid', $uid);
			return $this;
		}
		return '404';
	}
	
	function ajax_detail(){
		$uid = $this->post('uid');
		$page = $this->post('pageNum');
		
		$total = $this->model('swift')->where('uid=?',[$uid])->select('count(*) as count');//总记录数
		$total = $total[0]['count'];
		
		$pageSize = 20; //每页显示数
		$totalPage = ceil($total/$pageSize); //总页数
		
		$startPage = $page*$pageSize;
		
		$arr = [];
		
		$arr['total'] = $total;
		$arr['pageSize'] = $pageSize;
		$arr['totalPage'] = $totalPage;
		
		$swift = $this->model('swift')->where('uid=?',[$uid])->limit($startPage,$pageSize)->orderby('time','desc')->select();
		
		$arr['list'] = $swift;
		//print_r($arr);
		
	
		echo json_encode($arr);exit;
		
		
		
	}
	
	
	
	
}