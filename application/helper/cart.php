<?php
namespace application\helper;
use system\core\base;
class cart extends base
{
	/**
	 * 添加到购物车
	 * @param unknown $uid
	 * @param unknown $id
	 * @param unknown $content
	 * @param unknown $num 添加的数量
	 */
	function add($uid,$id,$content,$num,$bind = 0)
	{
		if (empty($num))
			return false;
		$result = $this->model('cart')->where('uid=? and pid=? and content=? and bind=?',[$uid,$id,$content,$bind])->find();
		if(empty($result))
		{
			if ($num<=0)
				return false;
			return $this->model('cart')->insert([
				'pid' => $id,
				'uid' => $uid,
				'content' => $content,
				'num' => $num,
				'time' => $_SERVER['REQUEST_TIME'],
				'bind' => $bind
			]);
		}
		else
		{
			if ($result['num'] + $num <= 0)
			{
				return $this->model('cart')->where('uid=? and pid=? and content=? and bind=?',[$uid,$id,$content,$bind])->delete();
			}
			else
			{
				return $this->model('cart')->where('uid=? and pid=? and content=? and bind=?',[$uid,$id,$content,$bind])->update([
					'num' => ($result['num'] + $num),
					'time' => $_SERVER['REQUEST_TIME'],
				]);
			}
		}
	}
	
	/**
	 * 清空用户的购物车
	 * @param unknown $uid 用户id
	 * @param string $stock 是否返回库存 默认false
	 * @return boolean
	 */
	function clearByUid($uid,$stock = false)
	{
		$cart = $this->model('cart')->where('uid')->select();
		if (!empty($cart) && is_array($cart) && $stock)
		{
			$productHelper = new product();
			foreach($cart as $product)
			{
				//增加库存
				$productHelper->increaseStock($product['pid'], $product['content'], $product['num']);
			}
		}
		return true;
	}
}