<?php
namespace system\core\config;
use system\core\inter\config;
class sessionConfig extends config
{
	public $httponly;
	
	public $save_path;
	
	/**
	 * 
	 */
	function __construct()
	{
		$this->httponly = true;
		
		/**
		 * session文件的存放位置（服务器端）
		 */
		$this->save_path = '';
		
		/**
		 * cookie存放目录
		 */
		$this->cookie_path = $_SERVER['HTTP_HOST'];
	}
}