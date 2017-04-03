<?php
namespace application\control\api;
use application\message\json;
use application\helper\product;
class category extends common
{
	private $_response;
	
	function __construct()
	{
		parent::__construct();
		$this->_response = $this->init();
	}
	
	/**
	 * 分类下商品列表
	 */
	function product()
	{
		//if (!empty($this->_response))
			//return $this->_response;
		
		$id = $this->data('id',0);
		$product_filter = [
			'isdelete' => 0,
			'status' => 1,
			'cid' => $id,
			'start' => $this->data('start',0),
			'length' => $this->data('length',10),
			'sort' => [['product.sort','asc'],['product.modifytime','desc']],
			'parameter' => [
				'product.id',
				'product.name',
                'product.oldprice as oldprice',
                'product.price  as price',
                'product.v1price  as v1price',
                'product.v2price  as v2price',
				'product.short_description',
				'store.name as store',
				'product.origin',
                'product.selled',
				'product.outside',
			]
		];
		$product = $this->model('category_product')->fetchAll($product_filter);
		$productHelper = new product();
		foreach ($product as &$p)
		{
			$p['origin'] = $this->model('country')->get($p['origin']);
			$p['image']  = $productHelper->getListImage($p['id']);

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
			
			//商品价格
			$filter = [
				'pid' => $p['id'],
				'isdelete' => 0,
				'available' => 1,
				'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)',
			];
			$price_collection = $this->model('collection')->fetch($filter);
			if (!empty($price_collection))
			{
				if ($price_collection[0]['sum(stock)'] !== NULL)
				{
					$p['stock'] = $price_collection[0]['sum(stock)'];
				}
				if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)']!==NULL)
				{
					$p['price'] = $price_collection[0]['min(price)'].'起';//.'~'.$price_collection[0]['max(price)'];
				}
				if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)']!==NULL)
				{
					$p['v1price'] = $price_collection[0]['min(v1price)'].'起';//.'~'.$price_collection[0]['max(v1price)'];
				}
				if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)']!==NULL)
				{
					$p['v2price'] = $price_collection[0]['min(v2price)'].'起';//.'~'.$price_collection[0]['max(v2price)'];
				}
			}
		}
		
		$product_filter['parameter'] = 'count(*)';
		unset($product_filter['start']);
		unset($product_filter['length']);
		$total = $this->model('category_product')->fetchAll($product_filter);
		
		$productReturnModel = [
			'current' => count($product),
			'total' => isset($total[0]['count(*)'])?$total[0]['count(*)']:0,
			'start' => $this->data('start',0),
			'length' => $this->data('length',10),
			'data' => $product,
			'title' => $this->model('category')->where('id=?',[$id])->limit(1)->find('name')['name'],
		];
		return new json(json::OK,NULL,$productReturnModel);
	}
	
	/**
	 * 获取分类列表
	 */
	function lists()
	{
		$filter = [
			'start' => $this->data('start',0),
			'length' => $this->data('length',10),
			'isdelete' => 0,
			'cid' => 0,
			'sort' => ['category.sort','asc'],
			'parameter' => 'category.id,
							category.name,
							upload.path as logo,
							category.description',
		];
		$category = $this->model('category')->fetchAll($filter);
		
		$filter['parameter'] = 'count(*)';
		unset($filter['start']);
		unset($filter['length']);
		$total = $this->model('category')->fetchAll($filter);
		
		$categoryReturnModel = [
			'current' => count($category),
			'total' => isset($total[0]['count(*)'])?$total[0]['count(*)']:0,
			'start' => $this->data('start',0),
			'length' => $this->data('length',10),
			'data' => $category,
		];
		
		return new json(json::OK,NULL,$categoryReturnModel);
	}
}