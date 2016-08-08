<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class task extends ajax
{	
	function create()
	{
		$pid = $this->post('pid');
		$teamnum = $this->post('teamnum',1,'intval');
		$price = $this->post('price',0,'floatval');
		$score = $this->post('score',0,'intval');
		$day = $this->post('day',1,'intval');
		if(empty($pid))
			return new json(json::PARAMETER_ERROR);
		
		if(!empty($this->model('task')->where('isdelete=? and pid=?',[0,$pid])->find()))
		{
			return new json(json::PARAMETER_ERROR,'该商品已经存在');
		}
		
		if(!empty($this->model('collection')->where('pid=? and isdelete=?',[$pid,0])->find()))
		{
			return new json(json::PARAMETER_ERROR,'该商品拥有多种规格，无法做为任务商品上架');
		}
		
		$total = $this->model('task')->where('isdelete=?',[0])->find('count(*)');
		
		if($this->model('task')->insert([
			'pid' => $pid,
			'teamnum' => $teamnum,
			'price' => $price,
			'score' => $score,
			'sort' => isset($total['count(*)']) && !empty($total['count(*)'])?$total['count(*)']:0,
			'createtime' => $_SERVER['REQUEST_TIME'],
			'isdelete' => 0,
			'deletetime' => 0,
			'day' => $day,
		]))
		{
			$id = $this->model('task')->lastInsertId();
			
			$body = $this->model('task')->table('product','left join','product.id=task.pid')->where('task.id=?',[$id])->find([
				'task.id',
				'task.teamnum',
				'task.sort',
				'product.name',
				'task.price',
				'task.score',
				'task.day',
			]);
			
			return new json(json::OK,NULL,$body);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	function remove()
	{
		$id = $this->post('id');
		if (!empty($id))
		{
			if($this->model('task')->where('id=?',[$id])->update([
				'isdelete' => 1,
				'deletetime' => $_SERVER['REQUEST_TIME']
			]))
			{
				return new json(json::OK);
			}
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	function moveup()
	{
		$id = $this->post('id',0,'intval');
		if (empty($id))
			return new json(json::PARAMETER_ERROR);
		
		$filter = [
			'isdelete' => 0,
			'sort' => ['task.sort','asc'],
		];
		$task = $this->model('task')->fetch($filter);
		
		foreach($task as $index => $t)
		{
			if($t['id'] == $id && isset($task[$index-1]))
			{
				$temp = $task[$index];
				$task[$index] = $task[$index-1];
				$task[$index-1] = $temp;
			}
		}
		
		foreach($task as $index => $t)
		{
			$this->model('task')->where('id=?',[$t['id']])->limit(1)->update('sort',$index);
		}
		return new json(json::OK);
	}
	
	function movedown()
	{
		$id = $this->post('id',0,'intval');
		if (empty($id))
			return new json(json::PARAMETER_ERROR);
	
		$filter = [
			'isdelete' => 0,
			'sort' => ['task.sort','asc'],
		];
		$task = $this->model('task')->fetch($filter);
	
		foreach($task as $index => $t)
		{
			if($t['id'] == $id && isset($task[$index+1]))
			{
				$temp = $task[$index];
				$task[$index] = $task[$index+1];
				$task[$index+1] = $temp;
			}
		}
	
		foreach($task as $index => $t)
		{
			$this->model('task')->where('id=?',[$t['id']])->limit(1)->update('sort',$index);
		}
		return new json(json::OK);
	}
	
	function save()
	{
		$id = $this->post('id');
		$price = $this->post('price');
		$teamnum = $this->post('teamnum');
		$score = $this->post('score');
		$day = $this->post('day');
		
		if(empty($id) || empty($price) || empty($teamnum) || empty($score))
			return new json(json::PARAMETER_ERROR,'参数不能为0');
		
		$this->model('task')->where('id=?',[$id])->limit(1)->update([
			'price' => $price,
			'teamnum' => $teamnum,
			'score' => $score,
			'day' => $day
		]);
		return new json(json::OK);
	}
	
	function start()
	{
		$tid = $this->post('tid');
		$address = $this->post('address','','intval');
		if (empty($tid))
			return new json(json::PARAMETER_ERROR);
		
		if (empty($address) && !$this->post('prepay',0,'intval'))
			return new json(json::PARAMETER_ERROR,'请选择收货地址');
	
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		if (empty($uid))
			return new json(json::NOT_LOGIN);
		
		$o_orderno = $this->post('o_orderno');
		if (empty($o_orderno))
		{
			$o_orderno = NULL;
		}
		
		$order = $this->model('task_user')
		->table('`order`','left join','order.orderno=task_user.orderno')
		->where('task_user.tid=? and order.uid=? and task_user.status=? and order.status=? and order.pay_status=? and task_user.o_orderno=?',[$tid,$uid,0,1,0,$o_orderno])
		->find();
		if (!empty($order))
		{
			return new json(json::OK,NULL,$order);
		}
			
		$task = $this->model('task')->where('id=? and isdelete=?',[$tid,0])->find();
		if (empty($task))
			return new json(json::PARAMETER_ERROR);

		$productHelper = new \application\helper\product();
		if(!$productHelper->canBuy($task['pid'], ''))
		{
			return new json(json::PARAMETER_ERROR,'商品已下架或删除');
		}

		//商品
		$product = [
			'id' => $task['pid'],
			'num' => 1,
			'content' => '',
			'price' => $task['price'],//覆盖原价格
		];
		$product = [$product];

		//优惠券
		$coupon = '';

		$orderHelper = new \application\helper\order();
		$orderData = $orderHelper->createOrderData($uid, $product, $coupon, $address);
		$package = $orderHelper->createPackageData();

		if ($this->post('prepay',0) == 1)
		{
			return new json(json::OK,NULL,$orderData);
		}
		
		$this->model('order')->transaction();
		if($this->model('order')->insert($orderData))
		{
			foreach ($package as $p)
			{
				$p['orderno'] = $orderData['orderno'];
				if(!$this->model('order_package')->insert($p))
				{
					$this->model('order')->rollback();
					return new json(json::PARAMETER_ERROR,'订单包裹错误');
				}
					
				$package_id = $this->model('order_package')->lastInsertId();
				foreach ($p['product'] as $product)
				{
					$product['package_id'] = $package_id;
					if (!$this->model('order_product')->insert($product))
					{
						$this->model('order')->rollback();
						return new json(json::PARAMETER_ERROR,'订单商品错误');
					}
				}
			}
			
			$data = [
				'orderno' => $orderData['orderno'],
				'tid' => $tid,
				'status' => 0,
				'o_orderno' => $o_orderno
			];
				
			if(!$this->model('task_user')->insert($data))
			{
				$this->model('order')->rollback();
				return new json(json::PARAMETER_ERROR,'团购信息添加失败');
			}
				
			//减少商品库存
			if(!$productHelper->increaseStock($task['pid'], '', -1))
			{
				$this->model('order')->rollback();
				return new json(json::PARAMETER_ERROR,'库存不足');
			}
				
			$this->model('order')->commit();
			return new json(json::OK,NULL,$orderData);
		}
		else
		{
			return new json(json::PARAMETER_ERROR,'订单创建失败');
		}
	}
}