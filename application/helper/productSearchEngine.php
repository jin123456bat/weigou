<?php
namespace application\helper;

use system\core\http;

class productSearchEngine
{
	private $percent = [
		'title' => 50,//标题的权重
		'category_name' => 20,//分类名称的权重
		'category_description' => 5,//分类描述
		'short_description' => 40,//短描述的权重
		'description' => 10,//描述的权重
		'tags'=>80,//标签的权重
	];
	
	/**
	 * 中文分词，返回分词后的数组
	 */
	private function depart($string,$duality = false)
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
		return NULL;
	}
	
	private function appendDepart($departs,$pid,$percent)
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
	 * 删除商品索引数据
	 * @param int|product $p
	 */
	function remove($p)
	{
		$id = NULL;
		if (is_array($p))
		{
			if (isset($p['id']))
			{
				$id = $p['id'];
			}
		}
		else if (is_numeric($p))
		{
			$id = $p;
		}
		if (!empty($id))
		{
			//删除原来的旧索引
			$this->model('searchIndex')->where('pid=?',[$id])->delete();
		}
	}
	
	/**
	 * 创建搜索索引
	 * @param int|product $pid 商品id
	 * 
	 */
	function build($pid)
	{
		$data = array();
		$percent = $this->percent;
		
		if (is_array($pid))
		{
			$p = $pid;
		}
		else if (is_numeric($pid))
		{
			$p = $this->model('product')->where('id=?',array($pid))->find();
		}
		
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
		
		$index = [];
		$percent = [];
		foreach($data as $searchIndex)
		{
			//假如是纯数字则过滤掉
			$parttern = '$[^\d]+$';
			if (!preg_match($parttern, $searchIndex['keyword']))
			{
				continue;
			}
				
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
				'createtime'=>time(),
				'percent' => $percent[$i[0].$i[1]]
			));
		}
	}
	
	/**
	 * 更新商品索引
	 * @param int|product $p
	 * 
	 */
	function rebuild($p)
	{
		$this->remove($p);
		$this->build($p);
	}
}