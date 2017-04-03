<?php
namespace system\core;
class actionsFilter
{
	/**
	 * 存储当validate失败的时候的信息
	 * @var unknown
	 */
	private $_message;
	
	/**
	 * @var control object
	 */
	private $_control;
	
	/**
	 * @var string action的名称
	 */
	private $_action;
	
	/**
	 * 当需要重写http状态码的时候这里填写重写后的http状态码
	 * @var int
	 */
	private $_http_code;
	
	/**
	 * 是否需要重写403的状态码
	 * @var string
	 */
	private $_rewrite_code = false;
	
	/**
	 * 重定向页面的地址
	 * @var string
	 */
	private $_redict;
	
	function __construct($control,$action)
	{
		$this->_control = $control;
		$this->_action = $action;
	}
	
	function validate()
	{
		if (method_exists($this->_control, '__access'))
		{
			$_access = $this->_control->__access();
			foreach ($_access as $access)
			{
				if (isset($access['actions']))
				{
					if (is_array($access['actions']))
					{
						if ($access['actions'] == ['*'] || in_array($this->_action, $access['actions']))
						{
							if ($access[0] == 'deny')
							{
								if ((isset($access['express']) && $access['express']) || !isset($access['express']))
								{
									if (isset($access['message']))
									{
										$this->_message = $access['message'];
									}
									else
									{
										$this->_message = 'Forbidden';
									}
									
									if (isset($access['httpCode']))
									{
										$this->_rewrite_code = true;
										$this->_http_code = $access['httpCode'];
									}
									else
									{
										$this->_http_code = 403;
									}
									
									if (isset($access['redict']))
									{
										$this->_redict = $access['redict'];
									}
									return false;
								}
							}
						}
					}
					else if (is_scalar($access['actions']))
					{
						if ($access['actions'] == '*' || $this->_action == $access['actions'])
						{
							if ($access[0]=='deny')
							{
								if ((isset($access['express']) && $access['express']) || !isset($access['express']))
								{
									if (isset($access['message']))
									{
										$this->_message = $access['message'];
									}
									else
									{
										$this->_message = 'Forbidden';
									}
									
									if (isset($access['httpCode']))
									{
										$this->_rewrite_code = true;
										$this->_http_code = $access['httpCode'];
									}
									else
									{
										$this->_http_code = 403;
									}
									
									if (isset($access['redict']))
									{
										$this->_redict = $access['redict'];
									}
									return false;
								}
							}
						}
					}
				}
			}
		}
		return true;
	}
	
	/**
	 * 是否重写返回的http状态码，默认的304
	 */
	function rewriteCode()
	{
		return $this->_rewrite_code;
	}
	
	/**
	 * 返回重写后的http状态码
	 */
	function getHeaderCode()
	{
		return $this->_http_code;
	}
	
	/**
	 * 获取validate的信息
	 */
	function getMessage()
	{
		return $this->_message;
	}
	
	/**
	 * 当validate为false的时候，获取重定向地址
	 * @return string
	 */
	function getRedict()
	{
		return $this->_redict;
	}
}