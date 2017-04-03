<?php
namespace system\core\exception;
class request extends \Exception
{
	function __construct($message, $code, $previous = NULL)
	{
		parent::__construct($message, $code, $previous);
	}
	
	
}