<?php
namespace application\config;

use system\core\inter\config;

/**
 * 文件上传配置
 *
 * @author 程晨
 *        
 */
class fileConfig extends config
{

	function __construct()
	{
		/**
		 * 文件保存位置
		 */
		$this->path =  './application/upload/'.date('Y').'/'.date('m').'/'.date('d').'/';
		
		/**
		 * 允许上传类型
		 */
		$this->type = array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/jpg',
			'image/bmp',
			'image/x-ms-bmp',
			'video/mp4',
			'video/ogg',
		);
		
		/**
		 * 文件大小
		 * 100MB
		 */
		$this->size = 1024 * 1024 * 100;
	}
}