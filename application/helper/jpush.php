<?php
namespace application\helper;

use system\core\base;

class jpush extends base
{
	private $_appKey;
	
	private $_secret;
	
	private $_uid = [];
	
	private $_jpush_instance;
	
	function __construct($AppKey,$Secret)
	{
		$this->_appKey = $AppKey;
		$this->_secret = $Secret;
		
		
		include ROOT.'\application\helper\jpush\vendor\autoload.php';
		
		$this->_jpush_instance = new \JPush\Client($AppKey, $Secret);
	}
	
	/**
	 * 推送
	 * @param unknown $title
	 * @param unknown $content
	 */
	function push($title,$content)
	{
		$push = $this->_jpush_instance->push();
		if (empty($this->_uid))
		{
			$push->addAllAudience();
		}
		$push->setNotificationAlert($content,$title)->setPlatform('all')->send();
	}
	
	function setUid($uid)
	{
		if (is_array($uid))
		{
			$this->_uid = $uid;
		}
		$this->_uid[] = $uid;
	}
}