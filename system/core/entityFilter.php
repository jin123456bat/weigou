<?php
namespace system\core;

class entityFilter
{
	function requiredFilter($value,$rule)
	{
		if (empty($value))
		{
			return false;
		}
		return true;
	}
	
	function intFilter($value,$rule)
	{
		if (preg_match('/\D/', trim($value)))
		{
			return false;
		}
		return true;
	}
	
	function maxlengthFilter($value,$rule)
	{
		if (isset($rule['maxlength']))
		{
			if(mb_strlen($value)>$rule['maxlength'])
			{
				return false;
			}
		}
		return true;
	}
	
	function telephoneFilter($value)
	{
		if (strlen($value)==11)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * 数组个数最小
	 */
	function mincountFilter($data,$rule)
	{
		if (!is_array($data))
		{
			return false;
		}
		return count($data)>=$rule['mincount'];
	}
}