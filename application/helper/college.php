<?php
namespace application\helper;
use system\core\base;
class college extends base
{
	/**
	 * 计算课程的浏览量
	 */
	function getBrowse($id)
	{
		$init_num = $this->model('system')->get('initnum','college');
		$result = $this->model('college_user')->where('college_id=?',[$id])->select('count(*)');
		return isset($result[0]['count(*)'])?$result[0]['count(*)'] + $init_num:$init_num;
	}
	
	/**
	 * 创建浏览记录
	 */
	function createLog($id)
	{
		$userHelper = new user();
		$uid = $userHelper->isLogin();
		if(empty($uid))
		{
			$uid = NULL;
		}
		$ip = ip();
		if(empty($this->model('college_user')->where('ip=? and college_id=?',[$ip,$id])->find()))
		{
			return $this->model('college_user')->insert([
				'user_id' => $uid,
				'college_id' => $id,
				'time' => $_SERVER['REQUEST_TIME'],
				'ip' => $ip,
			]);
		}
		else
		{
			return $this->model('college_user')->where('ip=? and college_id=?',[$ip,$id])->update([
				'time' => $_SERVER['REQUEST_TIME'],
				'user_id' => $uid,
			]);
		}
	}
}