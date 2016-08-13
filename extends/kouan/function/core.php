<?php

/**
 * 载入模板
 * @param unknown $name
 * @return NULL|Ambigous <NULL>
 */
function loadTemplate($name)
{
	static $template = array();
	if (! isset($template[$name]))
	{
		
		$path = realpath(KOUAN_ROOT . '/template/' . $name . '.php');
		if (! empty($path))
		{
			$template[$name] = include $path;
		}
		else
		{
			return NULL;
		}
	}
	return $template[$name];
}

/**
 * RSA加密
 */
function RSA()
{
	
}