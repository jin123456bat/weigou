<?php
namespace application\model;
use system\core\model;
class smslogModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	/**
	 * 绑定手机号和验证码
	 * @param unknown $telephone
	 * @param unknown $code
	 * @return \system\core\Ambigous
	 */
	function create($telephone,$code)
	{
		return $this->insert([$telephone,$code,$_SERVER['REQUEST_TIME']]);
	}
	
	/**
	 * 检查手机号是否可以发送短信或者验证码是否匹配短信
	 * 验证码有效期3分钟  3分钟3条短信
	 * @param unknown $telephone
	 * @param string $code
	 */
	function check($telephone,$code = NULL)
	{
		if ($code === NULL)
		{
			//一分钟一条限制
			if(empty($this->where('time > ? and telephone = ?',[$_SERVER['REQUEST_TIME'] - 60,$telephone])->find()))
			{
				return true;
				//30分钟10次
				/* $result = $this->where('time > ? and telephone = ?',[$_SERVER['REQUEST_TIME'] - 1800 , $telephone])->select();
				if(count($result) <= 10)
				{
					;
				} */
			}
			else
			{
				return false;
			}
		}
		else
		{
			$result = $this->where('telephone =? and code = ? and time > ?',[$telephone,$code,$_SERVER['REQUEST_TIME'] - 180 ])->select();
			return !empty($result);
		}
	}
}