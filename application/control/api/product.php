<?php
namespace application\control\api;

use application\message\json;
use application\helper\user;
use application\helper\productSearchEngine;
use system\core\http;

class product extends common
{

	private $_response;

	function __construct()
	{
		parent::__construct();
		$this->_response = $this->init();
	}
	
	/**
	 * 判断商品图片详情中是否有远程图片  有的话自动下载远程图片并且保存在本地  然后替换掉原来的地址
	 */
	function product_fix()
	{
		$hosts= array(
			'twillg.com',
			'www.twillg.com',
		);
		$products = $this->model('product')->where('product_description_image_fixed=0')->select();
		foreach ($products as $product)
		{
			$result = 0;
			$description = preg_replace_callback('/src="(?<src>[^"]*)"/', function($src) use($hosts,&$result){
				$url = parse_url($src['src']);
				if (!in_array($url['host'], $hosts))
				{
					$type = pathinfo($url['path'],PATHINFO_EXTENSION);
					$response = http::get($src['src']);
					$file = './application/upload/'.date('Y/m/d').'/'.md5($response).'.'.$type;
					if(!is_dir('./application/upload/'.date('Y/m/d').'/'))
					{
						mkdir('./application/upload/'.date('Y/m/d').'/',0777,true);
					}
					if (!empty($response))
					{
						if(file_put_contents($file, $response))
						{
							$result = 1;
							return 'src="'.$file.'"';
						}
					}
				}
				else
				{
					return 'src="'.$src['src'].'"';
				}
			}, $product['description']);
			if ($result)
			{
				$this->model('product')->where('id=?',array($product['id']))->limit(1)->update(array('description'=>$description,'product_description_image_fixed'=>1));
			}
		}
	}

	/**
	 * 获取商品详情
	 */
	function detail()
	{
		if (! empty($this->_response))
			return $this->_response;
		
		$id = $this->data('id');
		$product = $this->model('product')
			->table('store', 'left join', 'product.store=store.id')
			->where('product.id=? and product.isdelete=?', [
			$id,
			0
		])
			->find([
			'product.id', // id
			'product.name',
			'product.description',
			'product.origin',
			'product.oldprice  as oldprice', // 下面4个是价格
			'product.price  as price',
			'product.v1price  as v1price',
			'product.v2price  as v2price',
			'product.auto_status', // 下面4个状态判断
			'product.status',
			'product.avaliabletime_from',
			'product.avaliabletime_to',
			'product.auto_stock', // 是否库存限制
			'product.stock', // 库存
			'store.name as store', // 发货仓库
			'product.tags', // 商品标签
			'product.outside', // 是否是海外商品
			'product.short_description', // 短描述
			'product.freetax',
			'product.selled'
		]); // 起售数

		
		// 商品是否存在
		if (empty($product))
		{
			return new json(json::PARAMETER_ERROR, '商品不存在');
		}
		
		//捆绑价格替换
		$bind = $this->model('bind')->where('pid=?',[$id])
		->orderby('num','desc')
		->find([
			'price',
			'v1price',
			'v2price',
		]);
		if (!empty($bind))
		{
			$product['price'] = $bind['price'];
			$product['v1price'] = $bind['v1price'];
			$product['v2price'] = $bind['v2price'];
		}
		
		// 商品价格
		$filter = [
			'pid' => $product['id'],
			'isdelete' => 0,
			'available' => 1,
			'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)'
		];
		$price_collection = $this->model('collection')->fetch($filter);
		if (! empty($price_collection))
		{
			if ($price_collection[0]['sum(stock)'] !== NULL)
			{
				$product['stock'] = $price_collection[0]['sum(stock)'];
			}
			if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)'] !== NULL)
			{
				$product['price'] = $price_collection[0]['min(price)'] . '~' . $price_collection[0]['max(price)'];
			}
			if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL)
			{
				$product['v1price'] = $price_collection[0]['min(v1price)'] . '~' . $price_collection[0]['max(v1price)'];
			}
			if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL)
			{
				$product['v2price'] = $price_collection[0]['min(v2price)'] . '~' . $price_collection[0]['max(v2price)'];
			}
		}
		
		// 字典替换
		$product['origin'] = $this->model('country')->get($product['origin']);
		
		// 收藏判断
		$userHelper = new user();
		$uid = $userHelper->isLogin();
		$productHelper = new \application\helper\product();
		$product['favourite'] = intval($productHelper->isFavourite($uid, $id));
		
		// 商品详情图
		$product['image'] = $productHelper->getDetailImage($id);
		$product['tax'] = $productHelper->getTaxFields($id);
		
		// 商品属性
		$filter = [
			'pid' => $id,
			'isdelete' => 0,
			'parameter' => 'name,type,value'
		];
		$product['prototype'] = $this->model('prototype')->fetch($filter);
		
		// 商品的可选属性集合
		$filter = [
			'pid' => $id,
			'isdelete' => 0,
			'parameter' => [
				'content',
				'price',
				'v1price',
				'v2price',
				'sku',
				'stock',
				'upload.path as logo',
				'available'
			]
		];
		$collections = $this->model('collection')->fetchAll($filter);
		foreach ($collections as &$collection)
		{
			$collection['price'] = $collection['price'] * $product['selled'];
			$collection['v1price'] = $collection['v1price'] * $product['selled'];
			$collection['v2price'] = $collection['v2price'] * $product['selled'];
		}
		$product['collection'] = $collections;
		
		$bind = $this->model('bind')
		->where('pid=?', [$id])
		->orderby('num', 'desc')
		->select();
		foreach ($bind as &$b)
		{
			if ($b['content'] != '')
			{
				$stock = $this->model("collection")
					->where("pid=? and content=?", [
					$b['pid'],
					$b['content']
				])
					->find([
					'stock'
				]);
				$b['stock'] = $stock['stock'];
			}
			else
			{
				$b['stock'] = $product['stock'];
			}
			$b['price'] = $b['price'] * $b['num'];
			$b['v1price'] = $b['v1price'] * $b['num'];
			$b['v2price'] = $b['v2price'] * $b['num'];
			$b['inprice'] = $b['inprice'] * $b['num'];
		}
		$product['bind'] = $bind;
		
		return new json(json::OK, NULL, $product);
	}

	/**
	 * 搜索热词
	 */
	function searchwords()
	{
		if (! empty($this->_response))
			return $this->_response;
		
		$start = $this->data('start', 0);
		$length = $this->data('length', 10);
		// 一个月以内的热搜词
		$words = $this->model('search_log')
			->where('time > ?', [
			$_SERVER['REQUEST_TIME'] - 30 * 3600 * 24
		])
			->limit($start, $length)
			->groupby('keywords')
			->orderby('num', 'desc')
			->orderby('time', 'desc')
			->select([
			'count(*) as num',
			'keywords',
			'time'
		]);
		
		$total = $this->model('search_log')
			->where('time > ?', [
			$_SERVER['REQUEST_TIME'] - 30 * 3600 * 24
		])
			->groupby('keywords')
			->orderby('num', 'desc')
			->orderby('time', 'desc')
			->select([
			'count(*) as num',
			'keywords',
			'time'
		]);
		
		$searchWordsReturnModel = [
			'current' => count($words),
			'total' => count($total),
			'start' => $this->data('start', 0),
			'length' => $this->data('length', 10),
			'data' => $words
		];
		
		return new json(json::OK, NULL, $searchWordsReturnModel);
	}

	/**
	 * 商品搜索
	 *
	 * @return \application\message\json
	 */
	function search()
	{
		if (! empty($this->_response))
		{
			return $this->_response;
		}
		
		$parameter = [
			'product.id',
			'product.name',
			'product.oldprice as oldprice',
			'product.price as price',
			'product.v1price as v1price',
			'product.v2price as v2price',
			'product.short_description',
			'store.name as store',
			'product.origin',
			'product.selled',
			'product.outside',
			'sum(product.percent) as percent'
		];
		
		$keywords = htmlspecialchars($this->data('keywords', '', 'trim'));
		$keywords = substr($keywords, 0, 32);
		
		$product = $this->model('product')
			->where('id=?', [
			$keywords
		])
			->find();
		if (! empty($product))
		{
			$product = [
				$product
			];
		}
		else
		{
			$searchHelper = new productSearchEngine();
			$keyword = $searchHelper->depart($keywords);
			if (empty($keyword))
			{
				// 分词失败，使用原来的关键词进行搜索
				$keyword = [$keywords];
			}
			else
			{
				$temp_key = [];
				foreach ($keyword as $key)
				{
					$temp_key[] = $key['word'];
				}
				$keyword = $temp_key;
			}
			$start = $this->data('start', 0);
			$length = $this->data('length', 10);
			
			$fkey = array_shift($keyword);
			$name_param = ['%'.$fkey.'%'];
			$keyword_param = [$fkey];
			$name_where = 'name like ?';
			$keyword_where = 'searchIndex.keyword = ?';
			foreach ($keyword as $word)
			{
				$name_param[] = '%'.$word.'%';
				$keyword_param[] = $word;
				$name_where = $name_where.' and name like ?';
				$keyword_where = $keyword_where.' or searchIndex.keyword=?';
			}
			$name_where = '('.$name_where.')';
			$keyword_where = '('.$keyword_where.')';
			$name_param[] = 0;
			$name_param[] = $_SERVER['REQUEST_TIME'];
			$name_param[] = $_SERVER['REQUEST_TIME'];
			$keyword_param[] = 0;
			$keyword_param[] = $_SERVER['REQUEST_TIME'];
			$keyword_param[] = $_SERVER['REQUEST_TIME'];
			
			// 对于商品标题，默认使用1000的关键度
			$sql = 'select ' . implode(',', $parameter) . ' 
        			from (
        			(
        				select product.*,1000 as percent
        				from product 
        				where 
        					'.$name_where.' and 
        					isdelete=? and 
        					(
        						(product.auto_status = 0 and product.status = 1) or
        						(auto_status = 1 and avaliabletime_from <= ? and avaliabletime_to >= ?)
        					)
        				order by product.sort asc,product.id desc
        			)
					union
					(
        				select product.*,searchIndex.percent
        				from product 
        				left join searchIndex 
        				on searchIndex.pid=product.id 
        				where 
        				'.$keyword_where.'
        				and product.isdelete=? and 
        				(
        					(product.auto_status = 0 and product.status = 1) or
        					(product.auto_status = 1 and product.avaliabletime_from <= ? and product.avaliabletime_to >= ?)
        				) 
        				order by percent desc
        			)
        		) as product
        			left join store
        			on store.id=product.store
					group by product.id
        			order by percent desc,product.sort asc,product.id desc
        			limit ' . $start . ',' . $length;
			
			$param = array_merge($name_param,$keyword_param);
			$product = $this->model('product')->query($sql, $param);
		}
		
		$productHelper = new \application\helper\product();
		foreach ($product as &$p)
		{
			$p['origin'] = $this->model('country')->get($p['origin']);
			$p['image'] = $productHelper->getListImage($p['id']);
			
			$bind = $this->model('bind')->where('pid=?',[$p['id']])
			->orderby('num','desc')
			->find([
				'price',
				'v1price',
				'v2price',
			]);
			if (!empty($bind))
			{
				$p['price'] = $bind['price'];
				$p['v1price'] = $bind['v1price'];
				$p['v2price'] = $bind['v2price'];
			}
			
			// 商品价格
			$filter = [
				'pid' => $p['id'],
				'isdelete' => 0,
				'available' => 1,
				'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)'
			];
			$price_collection = $this->model('collection')->fetch($filter);
			if (! empty($price_collection))
			{
				if ($price_collection[0]['sum(stock)'] !== NULL)
				{
					$p['stock'] = $price_collection[0]['sum(stock)'];
				}
				if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)'] !== NULL)
				{
					$p['price'] = $price_collection[0]['min(price)']; // '~'.$price_collection[0]['max(price)'];
				}
				if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL)
				{
					$p['v1price'] = $price_collection[0]['min(v1price)']; // '~'.$price_collection[0]['max(v1price)'];
				}
				if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL)
				{
					$p['v2price'] = $price_collection[0]['min(v2price)']; // '~'.$price_collection[0]['max(v2price)'];
				}
			}
		}
		
		$product_filter = [
			'isdelete' => 0,
			'status' => 1,
			'parameter' => 'count(*)'
		];
		$total = $this->model('product')->fetchAll($product_filter);
		
		$productReturnModel = [
			'current' => count($product),
			'total' => isset($total[0]['count(*)']) ? $total[0]['count(*)'] : 0,
			'start' => $this->data('start', 0),
			'length' => $this->data('length', 10),
			'data' => $product
		];
		
		if (! empty($keywords))
		{
			$userHelper = new user();
			$this->model('search_log')->insert([
				'ip' => ip(),
				'keywords' => $keywords,
				'time' => $_SERVER['REQUEST_TIME'],
				'uid' => $userHelper->isLogin(),
				'total' => isset($total[0]['count(*)']) ? $total[0]['count(*)'] : 0,
				'userAgent' => \application\helper\api::getUser()
			]);
		}
		
		return new json(json::OK, NULL, $productReturnModel);
	}

	/**
	 * 首页商品
	 */
	function top()
	{
		$product_filter = [
			'isdelete' => 0,
			'sort' => [
				['product_top.sort','asc'],
				['product.sort','asc'],
				['product.modifytime','desc'],
			],
			'status' => 1,
			'start' => $this->data('start', 0),
			'length' => $this->data('length', 10),
			'parameter' => [
				'product.id',
				'product.name',
				'product.oldprice  as oldprice',
				'product.price  as price',
				'product.v1price as v1price',
				'product.v2price as v2price',
				'product.short_description',
				'store.name as store',
				'product.origin',
				'product.stock',
				'product.selled',
				'product.outside',
			]
		];
		$product = $this->model('product_top')->fetchAll($product_filter);
		
		$productHelper = new \application\helper\product();
		foreach ($product as &$p)
		{
			$p['origin'] = $this->model('country')->get($p['origin']);
			$p['image'] = $productHelper->getListImage($p['id']);
			
			$bind = $this->model('bind')->where('pid=?',[$p['id']])
			->orderby('num','desc')
			->find([
				'price',
				'v1price',
				'v2price',
			]);
			if (!empty($bind))
			{
				$p['price'] = $bind['price'];
				$p['v1price'] = $bind['v1price'];
				$p['v2price'] = $bind['v2price'];
			}
			
			// 商品价格
			$filter = [
				'pid' => $p['id'],
				'isdelete' => 0,
				'available' => 1,
				'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)'
			];
			$price_collection = $this->model('collection')->fetch($filter);
			if (! empty($price_collection))
			{
				if ($price_collection[0]['sum(stock)'] !== NULL)
				{
					$p['stock'] = $price_collection[0]['sum(stock)'];
				}
				if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)'] !== NULL)
				{
					$p['price'] = $price_collection[0]['min(price)'] . '~' . $price_collection[0]['max(price)'];
				}
				if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL)
				{
					$p['v1price'] = $price_collection[0]['min(v1price)'] . '~' . $price_collection[0]['max(v1price)'];
				}
				if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL)
				{
					$p['v2price'] = $price_collection[0]['min(v2price)'] . '~' . $price_collection[0]['max(v2price)'];
				}
			}
		}
		
		$product_filter['parameter'] = 'count(*)';
		unset($product_filter['start']);
		unset($product_filter['length']);
		$total = $this->model('product_top')->fetchAll($product_filter);
		$total = isset($total[0]['count(*)'])?$total[0]['count(*)']:0;
		
		$productReturnModel = [
			'current' => count($product),
			'total' => $total,
			'start' => $this->data('start', 0),
			'length' => $this->data('length', 10),
			'data' => $product
		];
		return new json(json::OK, NULL, $productReturnModel);
	}
}