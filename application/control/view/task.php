<?php
namespace application\control\api;
use application\message\json;
class task extends common
{
	private $_response;
	
	function __construct()
	{
		parent::__construct();
		$this->_response = $this->init();
	}
	
	/**
	 * 团购商品列表
	 */
	function lists()
	{
		$start = $this->data('start',0);
		$length = $this->data('length',10);
		
		$product = $this->model('task')
		->table('product','left join','product.id=task.pid')
		->table('store','left join','product.store=store.id')
		->where('task.isdelete=?',[0])
		->where('(auto_stock=? and stock>?) or auto_stock=?',[1,0,0])
		->where('product.isdelete=?',[0])
		->where('(product.auto_status=? and product.status=?) or (product.auto_status=? and product.avaliabletime_from < ? and product.avaliabletime_to > ?)',[0,1,1,$_SERVER['REQUEST_TIME'],$_SERVER['REQUEST_TIME']])
		->orderby('task.sort','asc')
		->orderby('product.sort','asc')
		->orderby('product.createtime','desc')
		->limit($start,$length)
		->select([
			'task.id',
			'product.name',
			'task.price',
			'task.teamnum',
			'task.score',
			'task.day',
			'store.name as store',
			'task.pid',
			'product.oldprice',
			'product.origin',
			'product.outside',
		]);
		
		$productHelper = new \application\helper\product();
		foreach($product as &$p)
		{
			$p['image'] = $productHelper->getListImage($p['pid']);
			$p['origin'] = $this->model('country')->get($p['origin']);
			$p['tax'] = $productHelper->getTaxFields($p['pid']);
		}
		
		$total = $this->model('task')
		->table('product','left join','product.id=task.pid')
		->where('task.isdelete=?',[0])
		->where('(auto_stock=? and stock>?) or auto_stock=?',[1,0,0])
		->where('product.isdelete=?',[0])
		->where('(product.auto_status=? and product.status=?) or (product.auto_status=? and product.avaliabletime_from < ? and product.avaliabletime_to > ?)',[0,1,1,$_SERVER['REQUEST_TIME'],$_SERVER['REQUEST_TIME']])
		->find('count(*)');
		
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
	 * 我的团购列表
	 */
	function mylists()
	{
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		if (empty($uid))
			return new json(json::NOT_LOGIN);
		
		$start = $this->data('start',0,'intval');
		$length = $this->data('length',10,'intval');
	
		$order = $this->model('task_user')
		->table('task','left join','task.id=task_user.tid')
		->table('`order`','left join','order.orderno=task_user.orderno')
		->table('product','left join','product.id=task.pid')
		->table('store','left join','product.store=store.id')
		->where('order.uid=? and order.status=?',[$uid,1])
		->orderby('order.createtime','desc')
		->limit($start,$length)
		->select([
			'order.orderno',//订单号
			'order.pay_status',//是否支付
			'task_user.status as task_user_status',//团购是否成功 或者正在进行 或者取消
			'task_user.o_orderno',//是否是跟团还是开团
			'order.status as order_status',//订单是否有效
			
			'task.id',//任务id
			'product.name',//商品名称
			'task.price',//商品价格
			'task.teamnum',//拼团人数
			'task.score',//积分
			'task.day',//有效期
			'store.name as store',
			'task.pid',
			'product.oldprice',
			'product.origin'
		]);
		
		$productHelper = new \application\helper\product();
		foreach($order as &$product)
		{
			$product['image'] = $productHelper->getListImage($product['pid']);
			$product['origin'] = $this->model('country')->get($product['origin']);
			$product['tax'] = $productHelper->getTaxFields($product['pid']);
			
			if (empty($product['o_orderno']))
			{
				$complete_user_num = $this->model('task_user')
				->table('`order`','left join','order.orderno=task_user.orderno')
				->where('task_user.o_orderno=? and order.status=? and order.pay_status=?',[$product['orderno'],1,1])
				->find('count(*)');
				$product['complete_order_num'] = $complete_user_num['count(*)'];
				if ($product['pay_status']==1 && $product['order_status']==1)
				{
					$product['complete_order_num']++;
				}
			}
			else
			{
				$complete_user_num = $this->model('task_user')
				->table('`order`','left join','order.orderno=task_user.orderno')
				->where('task_user.o_orderno=? and order.pay_status=? and order.status=?',[$product['o_orderno'],1,1])
				->find('count(*)');
				$product['complete_order_num'] = $complete_user_num['count(*)'];
				$main_order = $this->model('order')->where('orderno=?',[$product['o_orderno']])->find();
				if ($main_order['pay_status']==1 && $main_order['status']==1)
				{
					$product['complete_order_num']++;
				}
			}
		}
		
		$total = $this->model('task_user')
		->table('`order`','left join','order.orderno=task_user.orderno')
		->where('order.uid=?',[$uid])
		->find('count(*)');
		
		$orderReturnModel = [
			'current' => count($order),
			'total' => isset($total[0]['count(*)'])?$total[0]['count(*)']:0,
			'start' => $this->data('start',0),
			'length' => $this->data('length',10),
			'data' => $order,
		];
		
		return new json(json::OK,NULL,$orderReturnModel);
	}
	
	/**
	 * 将团购任务升级为团购订单
	 */
	function start()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		$tid = $this->data('tid');
		$address = $this->data('address','','intval');
		if (empty($tid))
		{
			return new json(json::PARAMETER_ERROR);
		}
		
		if (empty($address) && !$this->data('prepay',0,'intval'))
		{
			return new json(json::PARAMETER_ERROR,'请选择收货地址');
		}
	
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		if (empty($uid))
		{
			return new json(json::NOT_LOGIN);
		}
		
		$o_orderno = $this->post('o_orderno');
		if (empty($o_orderno))
		{
			$o_orderno = NULL;
		}
			
			
		$order = $this->model('task_user')
		->table('`order`','left join','order.orderno=task_user.orderno')
		->where('task_user.tid=? and order.uid=? and task_user.status=? and order.status=? and order.pay_status=?',[$tid,$uid,0,1,0])
		->find([
			'`order`.*'	
		]);
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
			'price' => $task['price'],
		];
		$product = [$product];

		//优惠券
		$coupon = '';

		$orderHelper = new \application\helper\order();
		$orderData = $orderHelper->createOrderData($uid, $product, $coupon, $address);
		$package = $orderHelper->createPackageData();
		
		if ($this->data('prepay',0) == 1)
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
	
	/**
	 * 任务详情
	 */
	function taskdetail()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		$id = $this->data('id');
		
		$product = $this->model('task')
		->table('product','left join','product.id=task.pid')
		->table('store','left join','store.id=product.store')
		->where('(auto_stock=? and stock>?) or auto_stock=?',[1,0,0])
		->where('product.isdelete=? and task.isdelete=?',[0,0])
		->where('task.id=?',[$id])
		->where('(product.auto_status=? and product.status=?) or (product.auto_status=? and product.avaliabletime_from < ? and product.avaliabletime_to > ?)',[0,1,1,$_SERVER['REQUEST_TIME'],$_SERVER['REQUEST_TIME']])
		->find([
			'product.name',
			'task.id',
			'store.name as store',
			'product.description',
			'task.teamnum',
			'task.day',
			'task.score',
			'task.price',
			'task.pid',
			'product.oldprice',
			'product.outside',
			'product.short_description',
			'product.origin'
		]);
		
		$productHelper = new \application\helper\product();
		$product['image'] = $productHelper->getDetailImage($product['pid']);
		$product['origin'] = $this->model('country')->get($product['origin']);
		$product['tax'] = $productHelper->getTaxFields($product['pid']);
		
		return new json(json::OK,NULL,$product);
	}
	
	
	function taskorderdetail()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		$orderno = $this->data('orderno');
		if (empty($orderno))
			return new json(json::PARAMETER_ERROR);
		$task_user = $this->model('task_user')
		->table('`order`','left join','order.orderno=task_user.orderno')
		->table('task','left join','task.id=task_user.tid')
		->table('product','left join','product.id=task.pid')
		->table('store','left join','store.id=product.store')
		->where('task_user.orderno=? and order.status=?',[$orderno,1])
		->find([
			'task.id',
			'order.uid',
			'task_user.orderno',
			'order.pay_status',
			'task_user.status as task_user_status',
			'order.status as order_status',
			'product.name',
			'task.pid',
			'task.price',
			'task.teamnum',
			'task.day',
			'task.score',
			'store.name as store',
			'task_user.o_orderno',
			'product.oldprice',
			'product.outside',
			'product.description',
			'product.short_description',
			'product.origin'
		]);
		
		if (empty($task_user))
			return new json(json::PARAMETER_ERROR,'没有找到团购订单');
		
		$productHelper = new \application\helper\product();
		$task_user['origin'] = $this->model('country')->get($task_user['origin']);
		$task_user['image'] = $productHelper->getDetailImage($task_user['pid']);
		$task_user['tax'] = $productHelper->getTaxFields($task_user['pid']);
		
		$complete_user = [];
		if (empty($task_user['o_orderno']))
		{
			$main_order = $this->model('order')->where('orderno=? and status=?',[$task_user['orderno'],1])->find();
			$task_user['createtime'] = $main_order['createtime'];
			
			if ($main_order['pay_status'] == 1)
			{
				$complete_user_gravatar = $this->model('user')
				->table('upload','left join','upload.id=user.gravatar')
				->where('user.id=?',[$main_order['uid']])
				->find('upload.path as gravatar');
				$complete_user[] = $complete_user_gravatar['gravatar'];
			}
			$complete_user_gravatar = $this->model('task_user')
			->table('`order`','left join','order.orderno=task_user.orderno')
			->table('user','left join','user.id=order.uid')
			->table('upload','left join','user.gravatar=upload.id')
			->where('task_user.o_orderno=? and order.pay_status=? and order.status=?',[$orderno,1,1])
			->select([
				'upload.path as gravatar'
			]);
			foreach ($complete_user_gravatar as $gravatar)
			{
				$complete_user[] = $gravatar['gravatar'];
			}
		}
		else
		{
			$main_order = $this->model('order')
			->table('user','left join','user.id=order.uid')
			->table('upload','left join','upload.id=user.gravatar')
			->where('order.orderno=?',[$task_user['o_orderno']])
			->find(['upload.path as gravatar','order.pay_status','order.status','order.createtime']);
			if ($main_order['pay_status'] == '1' && $main_order['status']=='1')
			{
				array_push($complete_user, $main_order['gravatar']);
			}
			
			$task_user['createtime'] = $main_order['createtime'];
			
			$complete_user_gravatar = $this->model('task_user')
			->table('`order`','left join','order.orderno=task_user.orderno')
			->table('user','left join','user.id=order.uid')
			->table('upload','left join','upload.id=user.gravatar')
			->where('task_user.o_orderno=? and order.pay_status=?',[$task_user['o_orderno'],1])
			->select('upload.path as gravatar');
			foreach ($complete_user_gravatar as $gravatar)
			{
				array_push($complete_user, $gravatar['gravatar']);
			}
		}
		$task_user['complete_user'] = $complete_user;
		
		return new json(json::OK,NULL,$task_user);
	}
}