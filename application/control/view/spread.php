<?php
namespace application\control\view;
use system\core\view;
class spread extends view
{
	function __construct()
	{
		$this->_csrf_token_refresh = false;
		parent::__construct();
	}
	
	function enter()
	{
		$share_uid = $this->get('share_uid');
		if (!empty($share_uid))
		{
		
			$userHelper = new \application\helper\user();
			$uid = $userHelper->isLogin();
			if(empty($uid))
			{
				//开始获取当前用户的id
				if (isWechat())
				{
				
				}
			}
			
			$o_user = $this->model('user')->where('oid=?',[$share_uid])->find();
			if(!empty($o_user))
			{
				if($o_user['master'] == 1)
				{
					$this->model('user')->where('id=?',[$uid])->limit(1)->update([
						'o_master' => $share_uid,
						'oid' => $share_uid,
					]);
				}
				else
				{
					$this->model('user')->where('id=?',[$uid])->limit(1)->update([
						'o_master' => $o_user['o_master'],
						'oid' => $share_uid,
					]);
				}
			}
		}
	}
}