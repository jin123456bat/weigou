<?php
namespace application\helper;
class api
{
	/**
	 * 获取调用api时候的user
	 */
	static function getUser()
	{
		return isset($_POST['partner'])?$_POST['partner']:'';
	}
}