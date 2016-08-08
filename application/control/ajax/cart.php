<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
use application\helper as helper;
class cart extends ajax
{
	/**
	 * 将商品删除或者添加到购物车
	 */
	function add()
	{
		$id = $this->post('id');
		$content = $this->post('content','');
		$num = $this->post('num',1,'intval');
		
		$userHelper = new helper\user();
		$uid = $userHelper->isLogin();
		if (empty($uid))
			return new json(json::NOT_LOGIN);
	
		$productHelper = new helper\product();
	
		if($productHelper->canBuy($id,$content))
		{
			$this->model('product')->transaction();
			if($productHelper->increaseStock($id, $content, -$num))
			{
				$cartHelper = new helper\cart();
				if($cartHelper->add($uid, $id, $content, $num))
				{
					$this->model('product')->commit();
					return new json(json::OK,'添加到购车成功');
				}
				else
				{
					$this->model('product')->rollback();
					return new json(json::PARAMETER_ERROR,'添加到购物车失败');
				}
			}
			$this->model('product')->rollback();
			return new json(json::PARAMETER_ERROR,'库存不足');
		}
		return new json(json::PARAMETER_ERROR,'该商品无法购买');
	}
}