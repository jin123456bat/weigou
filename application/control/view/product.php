<?php
namespace application\control\view;
use system\core\view;
use application\message\json;
class product extends view
{
	function datatables()
	{
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('product')->datatables($this->post());
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length')!=-1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'),$this->post('length'));
		}
		foreach ($resultObj->data as &$product)
		{
			//商品分类
			$filter = [
				'pid' => $product['id'],
				'parameter' => 'category.name as category',
				'isdelete' => 0,
			];
			$product['category'] = [];
			foreach($this->model('category')->fetchAll($filter) as $category)
			{
				$product['category'][] = $category['category'];
			}
			
			//商品价格
			$filter = [
				'pid' => $product['id'],
				'isdelete' => 0,
				'available' => 1,
				'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)',
			];
			$price_collection = $this->model('collection')->fetch($filter);
			if (!empty($price_collection))
			{
				if ($price_collection[0]['sum(stock)'] !== NULL)
				{
					$product['stock'] = $price_collection[0]['sum(stock)'];
				}
				if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)']!==NULL)
				{
					$product['price'] = $price_collection[0]['min(price)'].'~'.$price_collection[0]['max(price)'];
				}
				if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)']!==NULL)
				{
					$product['v1price'] = $price_collection[0]['min(v1price)'].'~'.$price_collection[0]['max(v1price)'];
				}
				if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)']!==NULL)
				{
					$product['v2price'] = $price_collection[0]['min(v2price)'].'~'.$price_collection[0]['max(v2price)'];
				}
			}
		}
		$resultObj->recordsTotal = $this->model('product')->count();
		return new json($resultObj);
	}
}