<?php
namespace system\core\config;

use system\core\inter\config;
class cookieConfig extends config
{
	public $expire = NULL;
	public $path = NULL;
	public $domain = NULL;
	public $secure = NULL;
	public $httponly = NULL;
}