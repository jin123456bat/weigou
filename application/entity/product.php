<?php
namespace application\entity;

use system\core\entity;

class product extends entity
{
	function __rule()
	{
		$data = array(
			array('bcategory'=>'required,int','message'=>'请选择商品分类'),
			array('name' => 'required','message'=>'请填写商品名称'),
			array('brand'=>'required,int','message'=>'请选择商品品牌'),
			array('name'=>'maxlength','maxlength'=>128,'message'=>'商品名称不能超过128个字符'),
		);
		if ($this->getData()['outside'] == 2)
		{
			$data[] = array('ztax'=>'required,int','message'=>'请选择商品税率');
		}
		else if ($this->getData()['outside'] == 3)
		{
			$data[] = array('postTaxNo'=>'required,int','message'=>'请选择商品税率');
		}
		return $data;
	}
	
	function __bcategory($product_id,$bcategory)
	{
		return array(
			'bcategory_product' => array(
				'insert' => array(
					['bc_id' => $bcategory,'product_id' => $product_id]
				),
				'delete' => array(
					'product_id' => $product_id,
				)
			),
		);
	}
	
	function __image($product_id,$image)
	{
		if (empty($image['list']))
		{
			$this->addError('image', '请上传列表图');
		}
		
		$detail = array(
			['pid'=>$product_id,'fid'=>$image['list'],'sort'=>1,'position'=>1,'createtime'=>$_SERVER['REQUEST_TIME'],'isdelete'=>0,'deletetime'=>$_SERVER['REQUEST_TIME']],
		);
		foreach ($image['detail'] as $index => $img_id)
		{
			$detail[] = [
				'pid'=>$product_id,
				'fid'=>$img_id,
				'sort' => $index,
				'position'=>2,
				'createtime' => $_SERVER['REQUEST_TIME'],
				'isdelete'=>0,
				'deletetime'=>$_SERVER['REQUEST_TIME'],
			];
		}
		return array(
			'product_img' => array(
				'insert' => $detail,
				'delete' => array(
					'pid' => $product_id,
				)
			),
		);
	}
	
	function __province($product_id,$province)
	{
		$insert = array();
		foreach ($province as $p)
		{
			$insert[] = [
				'product_id' =>$product_id,
				'province_id' => $p,
			];
		}
		return array(
			'product_province' =>array(
				'insert' => $insert,
				'delete' => array(
					'product_id' => $product_id,
				)
			)
		);
	}
	
	function __price($product_id,$price)
	{
		$insert_product_publish = array();
		$insert_product_publish_price = array();
		
		foreach ($price as $product_publish)
		{
			if (empty($product_publish['publish']) || empty($product_publish['stock']) || empty($product_publish['sku']) || empty($product_publish['store']))
			{
				continue;
			}
			$insert_product_publish[] = [
				'product_id' => $product_id,
				'publish_id' => $product_publish['publish'],
				'stock' => $product_publish['stock'],
				'sku' => $product_publish['sku'],
				'store' => $product_publish['store'],
			];
			
			foreach ($product_publish['price'] as $product_publish_price)
			{
				if (
					empty($product_publish_price['num']) || 
					empty($product_publish_price['oldprice']) || 
					empty($product_publish_price['inprice']) || 
					empty($product_publish_price['price']) ||
					empty($product_publish_price['v1price'])||
					empty($product_publish_price['v2price']) 
				)
				{
					continue;
				}
				$insert_product_publish_price[] = [
					'product_id' => $product_id,
					'publish_id' => $product_publish['publish'],
					'num'=>$product_publish_price['num'],
					'oldprice'=>$product_publish_price['oldprice'],
					'inprice'=>$product_publish_price['inprice'],
					'price'=>$product_publish_price['price'],
					'v1price'=>$product_publish_price['v1price'],
					'v2price'=>$product_publish_price['v2price'],
				];
			}
		}
		
		return array(
			'product_publish' => array(
				'insert' => $insert_product_publish,
				'delete' => array(
					'product_id' => $product_id,
				),
			),
			'product_publish_price' => array(
				'insert' => $insert_product_publish_price,
				'delete' => array(
					'product_id' => $product_id,
				),
			)
		);
	}
}