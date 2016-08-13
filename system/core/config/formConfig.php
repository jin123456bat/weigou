<?php
namespace system\core\config;
use system\core\inter\config;
class formConfig extends config
{
	function __construct()
	{
		/**
		 * 是否开启csrf验证
		 */
		$this->csrf = true;
		
		/**
		 * 是否允许表单重复提交
		 */
		$this->repeat = false;
		
		/**
		 * 是否允许表单频繁提交 单位秒
		 */
		$this->frequent = 60;
	}
}