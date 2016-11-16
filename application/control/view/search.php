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
	
	function rebuild()
	{
		$id = $this->get('id');
		if(!empty($id))
		{
			$SearchEngine = new productSearchEngine();
			$SearchEngine->rebuild($id);
			file_put_contents('./searchEngine.log', $id.' index build success');
			echo "OK";
		}
	}
}