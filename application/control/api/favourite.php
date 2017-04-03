<?php
namespace application\control\api;
use application\message\json;
class favourite extends common
{
	private $_response;
	
	private $_uid;
	
	function __construct()
	{
		parent::__construct();
		$this->_response = $this->init();
		
		$userHelper = new \application\helper\user();
		$this->_uid = $userHelper->isLogin();
	}
		
	/**
	 * 收藏列表
	 */
	function lists()
	{
		if (empty($this->_uid))
			return new json(json::NOT_LOGIN);
		
		$product_filter = [
			'isdelete' => 0,
			'uid' => $this->_uid,
			'status' => 1,
			'start' => $this->data('start',0),
			'length' => $this->data('length',10),
			'sort' => ['favourite.createtime','asc'],
			'parameter' => [
				'product.id',
				'product.name',
				'product.oldprice',
				'product.price',
				'product.v1price',
				'product.v2price',
				'product.short_description',
				'store.name as store',
				'product.origin',
			]
		];
		
		$product = $this->model('favourite')->fetchAll($product_filter);
		
		$productHelper = new \application\helper\product();
		foreach ($product as &$p)
		{
			$p['origin'] = $this->model('country')->get($p['origin']);
			
			
			$p['image']  = $productHelper->getListImage($p['id']);
		
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
		$total = $this->model('favourite')->fetchAll($product_filter);
		
		$productReturnModel = [
			'current' => count($product),
			'total' => isset($total[0]['count(*)'])?$total[0]['count(*)']:0,
			'start' => $this->data('start',0),
			'length' => $this->data('length',10),
			'data' => $product,
		];
		
		return new json(json::OK,NULL,$productReturnModel);
	}
	
	/**
	 * 添加收藏
	 */
	function create()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		if (empty($this->_uid))
			return new json(json::NOT_LOGIN);
		
		$id = $this->data('id');
		if (empty($id))
			return new json(json::PARAMETER_ERROR);
		
		$result = $this->model('favourite')->where('uid=? and pid=? and isdelete=?',[$this->_uid,$id,0])->find();
		if (!empty($result))
			return new json(json::PARAMETER_ERROR,'已经收藏了');
		
		if($this->model('favourite')->insert([
			'uid' => $this->_uid,
			'pid' => $id,
			'createtime' => $_SERVER['REQUEST_TIME'],
			'isdelete' => 0,
			'deletetime' => 0,
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	/**
	 * 移除收藏
	 */
	function remove()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		if (empty($this->_uid))
			return new json(json::NOT_LOGIN);
		
		$id = $this->data('id');
		if (empty($id))
			return new json(json::PARAMETER_ERROR);
		
		if($this->model('favourite')->where('uid=? and pid=? and isdelete=?',[$this->_uid,$id,0])->update([
			'isdelete' => 1,
			'deletetime' => $_SERVER['REQUEST_TIME'],
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR,'尚未添加收藏');
	}
}