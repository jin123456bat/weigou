<?php
namespace application\helper;
use system\core\base;
class product extends base
{
	function createProductData($product)
	{
		$data = [
			'sku' => '',
			'name' => '',
			'price' => 0,
			'v1price' => 0,
			'v2price' => 0,
			'oldprice' => 0,
			'auto_stock' => 1,
			'stock' => 0,
			'short_description' => '',
			'description' => '',
			'auto_status' => 1,
			'avaliabletime_from' => 0,
			'avaliabletime_to' => 0,
			'status' => 0,
			'isdelete' => 0,
			'createtime' => $_SERVER['REQUEST_TIME'],
			'modifytime' => $_SERVER['REQUEST_TIME'],
			'deletetime' => 0,
			'store' => NULL,
			'outside' => 0,
			'postTaxNo' => NULL,
			'package' => NULL,
			'origin' => NULL,
			'categoryCode' => '',
			'grossWeight' => 0,
			'goodsItemNo' => '',
			'goodsModel' => '',
			'currencyType' => NULL,
			'purpose' => '',
			'firstUnit' => NULL,
			'productRecordNo' => '',
			'sort' => 1,
			'tags' => '',
			'publish' => NULL,
			'freetax' => 0,
			'fee' => 0,
			'ztax' => NULL,
			'inprice' => 0,
			'barcode' => '',
			'selled' => 0,
			'down_reason' => '',
		];
		
		$product['origin'] = empty(intval($product['origin']))?NULL:$product['origin'];
		$product['store'] = empty(intval($product['store']))?NULL:$product['store'];
		$product['postTaxNo'] = empty(intval($product['postTaxNo']))?NULL:$product['postTaxNo'];
		$product['package'] = empty(intval($product['package']))?NULL:$product['package'];
		$product['currencyType'] = empty(intval($product['currencyType']))?NULL:$product['currencyType'];
		$product['firstUnit'] = empty(intval($product['firstUnit']))?NULL:$product['firstUnit'];
		$product['ztax'] = empty(intval($product['ztax']))?NULL:$product['ztax'];

		if ($product['auto_status'] == 1)
		{
			$product['avaliabletime_from'] = strtotime($product['avaliabletime_from']);
			$product['avaliabletime_to'] = strtotime($product['avaliabletime_to']);
		}
		else
		{
			$product['avaliabletime_from'] = 0;
			$product['avaliabletime_to'] = 0;
		}
		return array_merge($data,$product);
	}
	
	/**
	 * 增加或减少库存
	 */
	function increaseStock($id,$content,$stock)
	{
		$product = $this->model('product')->where('id=? and isdelete=?',[$id,0])->find();
		if(!empty($product))
		{
			if ($product['auto_stock'] == 0)
			{
				return true;
			}
			else
			{
				$collection = $this->model('collection')->where('pid=? and content=? and isdelete=?',[$id,$content,0])->find();
				if (!empty($collection))
				{
					if ($collection['stock'] + $stock >= 0)
					{
						$result = $this->model('collection')->where('pid=? and content=? and isdelete=?',[$id,$content,0])->increase('stock',$stock);
						return $result;
					}
				}
				else
				{
					if ($product['stock'] + $stock >= 0)
					{
						return $this->model('product')->where('id=? and isdelete=?',[$id,0])->increase('stock',$stock);
					}
				}
			}
		}
		return false;
	}
	
	/**
	 * 获取列表图
	 */
	function getListImage($id,$full = false)
	{
		$filter = [
			'isdelete' => 0,
			'sort' => [['product_img.sort','asc'],['product_img.createtime','desc']],
			'pid' => $id,
			'position' => 1,
			'start' => 0,
			'length' => 1,
			'parameter' => 'upload.path',
		];
		$image = $this->model('product_img')->fetchAll($filter);
		$path = isset($image[0]['path'])?$image[0]['path']:NULL;
		if ($full)
		{
			$path = ($this->http->isHttps()?'https://':'http://').$this->http->host().$this->http->path().ltrim($path,'.');
		}
		return $path;
	}
	
	/**
	 * 获取商品详情图
	 */
	function getDetailImage($id)
	{
		$filter = [
			'isdelete' => 0,
			'sort' => [['product_img.sort','asc'],['product_img.createtime','desc']],
			'pid' => $id,
			'position' => 2,
			'parameter' => 'upload.path',
		];
		$image = $this->model('product_img')->fetchAll($filter);
		$temp = [];
		foreach ($image as $img)
		{
			$temp[] = $img['path'];
		}
		return $temp;
	}
	
	/**
	 * 判断商品是否收藏
	 * @param unknown $uid
	 * @param unknown $pid
	 */
	function isFavourite($uid,$pid)
	{
		return !empty($this->model('favourite')->where('uid=? and pid=? and isdelete=?',[$uid,$pid,0])->find());
	}
	
	/**
	 * 计算税率
	 * @param unknown $pid
	 */
	function getTaxFields($pid)
	{
		$product = $this->model('product')->where('id=?',[$pid])->find();
		if ($product['outside'] == 2)
		{
			if (!empty($product['ztax']))
			{
				$result = $this->model('tax')->where('id=?',[$product['ztax']])->find([
					'gtax','xtax','ztax'
				]);
				return ($result['xtax'] + $result['ztax']) / (1 - $result['xtax']) * 0.7;
			}
		}
		else if ($product['outside'] == 3)
		{
			if (!empty($product['postTaxNo']))
			{
				$result = $this->model('posttaxno')->where('id=?',[$product['postTaxNo']])->find([
					'tax'
				]);
				return $result['tax'];
			}
		}
		return NULL;
	}
	
	/**
	 * 计算税收
	 * @param unknown $pid 商品id
	 * @param unknown $price 税收标准
	 */
	function calculationTax($pid,$price)
	{
		$product = $this->model('product')->where('id=?',[$pid])->find();
		//普通商品和进口商品不需要税费
		if ($product['outside'] == 0 || $product['outside'] == 1)
		{
			return 0;
		}
		$tax = $this->getTaxFields($pid);
		if ($product['freetax'] == 0)
		{
			if ($product['outside'] == 2)
			{
				//计算税费  综合税
				return $price * $tax;
			}
			else if ($product['outside'] == 3)
			{
				$tempTax = $price * $tax;
				if ($tempTax <= 50)
				{
					return 0;
				}
				else
				{
					return $tempTax;
				}
			}
		}
		return 0;
	}
	
	/**
	 * 通过捆绑参数获取商品的单价信息
	 * @param array('id','content','num','bind') $p
	 */
	function getPriceByBind($p)
	{
		if (isset($p['bind']) && !empty($p['bind']))
		{
			$bind = $this->model('bind')->where('pid=? and content=? and num=?',[$p['id'],$p['content'],$p['bind']])->find();
			if (!empty($bind))
			{
				return $bind;
			}
		}
		return false;
	}
	
	/**
	 * 创建订单的时候，获取并计算product参数中的销售数量，假如不符合规则则使用默认的销售规则
	 * @param array('id','content','num','bind') $p
	 */
	function getSelled($p)
	{
		if ($this->hasBind($p['id']) && isset($p['bind']) && !empty($p['bind']))
		{
			//设定了捆绑参数，也传递过来了，说明是新版本的接口，  这里使用接口传递过来的数量
			//这里应该验证一下bind参数的数字是否匹配
			if (!empty($this->model('bind')->where('pid=? and content=? and num=?',[$p['id'],$p['content'],$p['bind']])))
			{
				$selled = intval($p['bind']);
			}
			else
			{
				//对于使用了错误的bind参数，无视他，继续使用默认的selled参数
				$selled = $this->model('product')->where('id=?', [$p['id']])->find('selled');
				$selled = isset($selled['selled']) && !empty($selled['selled']) ? $selled['selled'] : 1;
			}
		}
		else
		{
			//没有设定捆绑参数,使用默认的捆绑数量
			$selled = $this->model('product')->where('id=?', [$p['id']])->find('selled');
			$selled = isset($selled['selled']) && !empty($selled['selled']) ? $selled['selled'] : 1;
		}
		return $selled;
	}
	
	/**
	 * 检查商品是否设定了捆绑
	 */
	function hasBind($id)
	{
		return !empty($this->model('bind')->where('pid=?',[$id])->find());
	}
	
	/**
	 * 判断商品是否可以被购买
	 */
	function canBuy($id,$content)
	{
		$product = $this->model('product')->where('id=? and isdelete=?',[$id,0])->find();
		if (!empty($product))
		{
			$filter = [
				'pid' => $id,
				'isdelete' => 0,
				'available' => 1,
			];
			$product_collection = $this->model('collection')->fetch($filter);
			
			if ($product['auto_status'] == 1)
			{
				if ($product['avaliabletime_from'] <= $_SERVER['REQUEST_TIME'] && $_SERVER['REQUEST_TIME'] <= $product['avaliabletime_to']);
				{
					if (empty($product_collection))
					{
						return true;
					}
					else
					{
						foreach ($product_collection as $collection)
						{
							if ($collection['content'] == $content)
								return true;
						}
						return false;
					}
				}
			}
			else if ($product['auto_status'] == 0)
			{
				if($product['status'] == 1)
				{
					if (empty($product_collection))
					{
						return true;
					}
					else
					{
						foreach ($product_collection as $collection)
						{
							if ($collection['content'] == $content)
								return true;
						}
						return false;
					}
				}
			}
		}
		return false;
	}
}