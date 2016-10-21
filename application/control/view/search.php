<?php
namespace application\control\view;
use system\core\http;
use system\core\control;
use application\message\json;

class search extends control
{
	/**
	 * 中文分词，返回分词后的数组
	 */
	function depart($string,$duality = false)
	{
		//去除无用的字符
		$string = str_replace("\u200d", '', str_replace("&nbsp;", '', strip_tags($string)));
		$string = str_replace(array(" \n\r"), '', $string);
		
		$url = 'http://www.xunsearch.com/scws/api.php';
		$response = http::post($url, [
			'data' => $string,
			'respond' => 'json',
			'charset' => 'utf8',
			'ignore' => 'yes',
			'multi' => '2',
			'traditional' => 'no',
			'duality' => !$duality?'no':'yes',
		]);
		$response = json_decode($response,true);
		if (strtolower(trim($response['status'])) == 'ok')
		{
			$temp = [];
			//去除idf为0的单词
			foreach ($response['words'] as $word)
			{
				if ($word['idf']>6)
				{
					$temp[] = $word;
				}
			}
			
			//然后进行排序
			usort($temp, function($a,$b){
				if ($a['idf'] < $b['idf'])
				{
					return 1;
				}
				else if($a['idf']==$b['idf'])
				{
					return 0;
				}
				else 
				{
					return -1;
				}
			});
			
			return $temp;
		}
	}
	
	function appendDepart($departs,$pid,$percent)
	{
		$data = [];
		foreach ($departs as $depart)
		{
			$data[] = [
				'keyword' => $depart['word'],
				'pid' => $pid,
				'percent' => $depart['idf'] * $percent,
			];
		}
		return $data;
	}
	
	/**
	 * 单独添加商品的索引或者更新商品索引
	 */
	function rebuild($id)
	{
		//删除原来的旧索引
		$this->model('searchIndex')->where('pid=?',[$id])->delete();
		if (is_array($id))
		{
			$product = $this->model('product')->where('id in (?)',$id)->select();
		}
		else
		{
			$product = $this->model('product')->where('id=?',[$id])->select();
		}
		//创建新索引
		$this->build($product);
	}
	
	/**
	 * 创建搜索的索引
	 */
	function build($product = NULL)
	{
		if (file_exists('./search_build.lock'))
		{
			return new json(json::PARAMETER_ERROR,'loading');
		}
		file_put_contents('./search_build.lock', 1);
		
		ini_set('max_execution_time', 0);
		
		$percent = [
			'title' => 50,//标题的权重
			'category_name' => 20,//分类名称的权重
			'category_description' => 5,//分类描述
			'short_description' => 40,//短描述的权重
			'description' => 10,//描述的权重
			'tags'=>80,//标签的权重
		];
		
		$data = [];
		
		if (empty($product))
		{
			$product = $this->model('product')->select();
		}
		
		foreach ($product as $p)
		{
			//标题
			$title = $p['name'];
			$title_departs = $this->depart($title);
			if (!empty($title_departs))
			{
				$data = array_merge($data,$this->appendDepart($title_departs, $p['id'], $percent['title']));
			}
			
			//短描述的权重
			if (!empty($p['short_description']))
			{
				$short_description = $this->depart($p['short_description']);
				if (!empty($short_description))
				{
					$data = array_merge($data,$this->appendDepart($short_description, $p['id'], $percent['short_description']));
				}
			}
			
			//描述的权重
			if (!empty($p['description']))
			{
				$description = $this->depart($p['description']);
				if (!empty($description))
				{
					$data = array_merge($data,$this->appendDepart($description, $p['id'], $percent['description']));
				}
			}
			
			//标签的权重
			if (!empty($p['tags']))
			{
				$tags = $this->depart($p['tags']);
				if (!empty($tags))
				{
					$data = array_merge($data,$this->appendDepart($tags, $p['id'], $percent['tags']));
				}
			}
			
			//分类
			$category = $this->model('category_product')
			->table('category','left join','category.id=category_product.cid')
			->where('category_product.pid=? and category.isdelete=?',array($p['id'],0))
			->select(array(
				'category.name',
				'category.description'
			));
			foreach ($category as $c)
			{
				if (!empty($c['name']))
				{
					$category_name_departs = $this->depart($c['name']);
					$data = array_merge($data,$this->appendDepart($category_name_departs, $p['id'], $percent['category_name']));
				}
				
				if (!empty($c['description']))
				{
					$category_description_departs = $this->depart($c['description']);
					$data = array_merge($data,$this->appendDepart($category_description_departs, $p['id'], $percent['category_description']));
				}
			}
		}
		
		$index = [];
		$percent = [];
		foreach($data as $searchIndex)
		{
			$array=[$searchIndex['keyword'],$searchIndex['pid']];
			if (!in_array($array, $index,true))
			{
				$index[] = $array;
				$percent[$searchIndex['keyword'].$searchIndex['pid']] = $searchIndex['percent'];
			}
			else
			{
				$percent[$searchIndex['keyword'].$searchIndex['pid']] += $searchIndex['percent'];
			}
		}
		
		
		foreach ($index as $i)
		{
			$this->model('searchIndex')->insert(array(
				'keyword'=>$i[0],
				'pid'=>$i[1],
				'createtime'=>date('Y-m-d H:i:s'),
				'percent' => $percent[$i[0].$i[1]]
			));
		}
		
		unlink('./search_build.lock');
		return new json(json::OK);
	}
}