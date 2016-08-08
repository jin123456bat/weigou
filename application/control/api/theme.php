<?php
namespace application\control\api;
use application\message\json;
use application\helper\product;
class theme extends common
{
	private $_response;
	
	function __construct()
	{
		parent::__construct();
		$this->_response = $this->init();
	}
	
	
	/**
	 * 获取主题详情
	 */
	function detail()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		$id = $this->data('id');
		if (empty($id))
			return new json(json::PARAMETER_ERROR);
		
		$theme = $this->model('theme')->table('upload','left join','theme.logo=upload.id')->where('theme.id=?',[$id])->find('theme.id,upload.path as logo,theme.title');
		
		$subtheme = $this->model('subtheme')->where('theme_id=?',[$id])->orderby('subtheme.sort','asc')->select();
		
		$productHelper = new product();
		foreach ($subtheme as &$st)
		{
			$product = $this->model('subtheme_product')
			->table('product','left join','subtheme_product.product_id=product.id')
			->where('subtheme_product.subtheme_id=?',[$st['id']])
			->select([
				'product.id',
				'product.name',
				'product.oldprice',
				'product.price',
				'product.v1price',
				'product.v2price',
				'product.origin',
			]);
			
			foreach ($product as &$p)
			{
				$p['image'] = $productHelper->getListImage($p['id']);
				$p['origin'] = $this->model('country')->get($p['origin']);
				
				//商品属性
				$filter = [
					'pid' => $id,
					'isdelete' => 0,
					'parameter' => 'name,type,value',
				];
				$p['prototype'] = $this->model('prototype')->fetch($filter);
				
				//商品的可选属性集合
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
						'available',
					]
				];
				$p['collection'] = $this->model('collection')->fetchAll($filter);
				
				//商品的价格等替换
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
						$p['price'] = $price_collection[0]['min(price)'].'~'.$price_collection[0]['max(price)'];
					}
					if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)']!==NULL)
					{
						$p['v1price'] = $price_collection[0]['min(v1price)'].'~'.$price_collection[0]['max(v1price)'];
					}
					if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)']!==NULL)
					{
						$p['v2price'] = $price_collection[0]['min(v2price)'].'~'.$price_collection[0]['max(v2price)'];
					}
				}
			}
			
			$st['product'] = $product;
		}
		
		$theme['subtheme'] = $subtheme;
		
		return new json(json::OK,NULL,$theme);
	}
}