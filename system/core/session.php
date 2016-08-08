<?php
namespace system\core;

class session
{
	/**
	 * 180
	 * @var unknown
	 */
	public $_expire;
	
	private static $_instance = NULL;

	private function __construct($config = NULL)
	{
		/*$this->_expire = session_cache_expire();
		
		if(isset($config['expire']))
		{
			session_cache_expire($config['expire']);
			$this->_expire = $config['expire'];
		}
		
		if (!empty($config['save_path']))
		{
			ini_set('session.save_path', $config['save_path']);
		}
		
		if (!empty($config['cookie_path']))
		{
			ini_set('session.cookie_path',$config['cookie_path']);
		}
		
		if ($config['httponly'])
		{
			ini_set("session.cookie_httponly", 1);
		}
		*/
		
		if(!session_id())
		{
			session_start();
		}
	}
	
	public static function setExpire($new_expire)
	{
		session_cache_expire($new_expire);
		$this->_expire = $new_expire;
	}
	
	public static function destory()
	{
		session_destroy();
	}
	
	public static function getInstance()
	{
		if(empty(self::$_instance))
			self::$_instance = new self(config('session'));
		return self::$_instance;
	}

	function __get($name)
	{
		return isset($_SESSION[$name]) ? $_SESSION[$name] : NULL;
	}

	function __set($name, $value)
	{
		$_SESSION[$name] = $value;
	}

	function __isset($name)
	{
		return isset($_SESSION[$name]) && ! empty($_SESSION['name']);
	}

	function __unset($name)
	{
		unset($_SESSION[$name]);
	}
}