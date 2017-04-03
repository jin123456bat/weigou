<?php
namespace system\core;

class entityFilter
{
	function requiredFilter($value,$rule)
	{
		if (empty(trim($value)))
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
}