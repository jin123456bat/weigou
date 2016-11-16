<?php
namespace application\control\view;
use system\core\control;
use application\helper\productSearchEngine;

class search extends control
{
	
	
	
	
	
	
	/**
	 * 创建搜索的索引
	 */
	function build()
	{
		ini_set('max_execution_time', 0);
		$SearchEngine = new productSearchEngine();
		foreach ($this->model('product')->select() as $p)
		{
			$SearchEngine->rebuild($p);
		}
		echo "OK";
	}
}