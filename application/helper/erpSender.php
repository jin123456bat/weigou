<?php
namespace application\helper;
use system\core\base;
class erpSender extends base
{
	private $_repeat_id;
	
	function __construct()
	{
		
	}
	
	function setRepeatId($repeat_id)
	{
		$this->_repeat_id = $repeat_id;
	}
	
	function getRepeatId()
	{
		return $this->_repeat_id;
	}
	
	/**
	 * 跳过erpSender直接执行erp中的方法，并返回其值
	 * @param unknown $erp
	 * @param unknown $action
	 * @param unknown $args
	 */
	function doAction($erp,$action,array $args = [])
	{
		$erp = $this->model('erp')->where('id=?',[$erp])->find();
		if(!empty($erp))
		{
			$classname = 'application\\helper\\erp\\'.$erp['name'];
			if (class_exists($classname,true))
			{
				$class = new $classname();
			
				if (method_exists($class, 'initlize'))
				{
					$class->initlize();
				}
			
				$class->setAppid($erp['appid']);
				$class->setAppsecret($erp['appsecret']);
				$class->setParameter($erp['parameter']);
				$class->setUrl($erp['url']);
				if (method_exists($class, $action))
				{
					$result = call_user_func_array([$class,$action],$args);
					return $result;
				}
			}
		}
		return false;
	}
	
	/**
	 * 调用成功的回调
	 * @param unknown $classname
	 * @param unknown $parameter
	 * @param unknown $method
	 * @param unknown $response
	 */
	function success($classname,$parameter,$method,$response)
	{
		if (empty($this->getRepeatId()))
		{
			$this->model('api_log')->insert([
				'classname' => $classname,
				'parameter' => $parameter,
				'method' => $method,
				'createtime' => $_SERVER['REQUEST_TIME'],
				'lasttime' => $_SERVER['REQUEST_TIME'],
				'times' => 1,
				'success' => 1,
				'response' => $response,
			]);
		}
		else
		{
			$this->model('api_log')->where('id=?',[$this->getRepeatId()])->update(['success'=>1,'lasttime'=>$_SERVER['REQUEST_TIME']]);
			$this->model('api_log')->where('id=?',[$this->getRepeatId()])->increase('times',1);
		}
	}
	
	/**
	 * 调用失败的回调
	 * @param unknown $classname
	 * @param unknown $parameter
	 * @param unknown $method
	 * @param unknown $response
	 */
	function failed($classname,$parameter,$method,$response)
	{
		if (empty($this->getRepeatId()))
		{
			$this->model('api_log')->insert([
				'classname' => $classname,
				'parameter' => $parameter,
				'method' => $method,
				'createtime' => $_SERVER['REQUEST_TIME'],
				'lasttime' => $_SERVER['REQUEST_TIME'],
				'times' => 1,
				'success' => 0,
				'response' => $response,
			]);
		}
		else
		{
			$this->model('api_log')->where('id=?',[$this->getRepeatId()])->increase('times',1);
			$this->model('api_log')->where('id=?',[$this->getRepeatId()])->update([
			    'lasttime'=>$_SERVER['REQUEST_TIME'],
			    'response' => $response,
			]);
		}
	}
	
	/**
	 * 发送订单数据到erp
	 */
	function doSendOrder($orderno,$focus = false)
	{
		$orderHelper = new \application\helper\order();
		$depart = $orderHelper->departByStore($orderno);
		
		if ($depart == 1 || $depart == 2)
		{
			//已经拆单成功，发送拆单后的数据
			$suborder = $this->model('suborder_store')->where('main_orderno=?',[$orderno])->select();
			
            foreach ($suborder as $order)
			{
				if ($order['erp'] == 1 && !$focus)
				{
					continue;
				}
				
				$erp = $this->model('store')->table('erp','left join','store.erp=erp.id')
				->where('store.id=?',[$order['store']])
				->find('erp.*');
				
				if (!empty($erp))
				{
				    $classname = 'application\\helper\\erp\\'.$erp['name'];
					if (class_exists($classname,true))
					{
						$class = new $classname();
						
						//接口尚未开启
						if (!$class->isOpen())
						{
							return false;
						}
						
						if (method_exists($class, 'initlize'))
						{
							$class->initlize();
						}
						
						$class->setAppid($erp['appid']);
						$class->setAppsecret($erp['appsecret']);
						$class->setParameter($erp['parameter']);
						$class->setUrl($erp['url']);
						
						$action =  $erp['do_send'];
						if (method_exists($class, $action))
						{
							$result = call_user_func_array([$class,$action],['suborder_id'=>$order['id']]);
							if($result)
							{
								//订单数据发送成功
								$this->model('suborder_store')->where('id=?',[$order['id']])->limit(1)->update([
									'erp' => 1,
									'erptime' => $_SERVER['REQUEST_TIME'],
								]);
								
								//成功的回调
								$this->success($classname, json_encode([
									'orderno'=>$orderno
								]), __FUNCTION__, $class->getResponseString());
								
							}
							else 
							{
								//失败的回调
								$this->failed($classname, json_encode([
									'orderno'=>$orderno
								]), __FUNCTION__, $class->getResponseString());
								
								return false;
							}
						}
					}
				}
			}
			
			//判断订单数据是否全部发送成功
			if(empty($this->model('order')
			->table('suborder_store','left join','suborder_store.main_orderno=order.orderno')
			->where('order.orderno=? and suborder_store.erp=?',[$orderno,0])
			->find()))
			{
				$this->model('order')->where('orderno=?',[$orderno])->limit(1)->update([
					'erp'=>1,
					'erp_time' => $_SERVER['REQUEST_TIME']
				]);
			}
			
			return true;
		}
		else
		{
			//拆单失败
			return false;
		}
	}
	
	/**
	 * 商品信息查询的方法，返回可以直接update进数据库的数组
	 * @param unknown $id 商品id
	 * @return bool|array 查询失败返回false
	 */
	function QueryGoods($id)
	{
		$product = $this->model('product')->where('id=?',[$id])->find();
		if (!empty($product))
		{
			$store = $this->model('store')->where('id=?',[$product['store']])->find();
			if (!empty($store) && !empty($store['erp']))
			{
				$erp = $this->model('erp')->where('id=?',[$store['erp']])->find();
				if (!empty($erp['QueryGoods']))
				{
					$response = $this->doAction($store['erp'], $erp['QueryGoods'],[$product['barcode']]);
					return $response;
				}
			}
		}
		return false;
	}
	
	/**
	 * 获取订单数据
	 */
	function doGetOrder($id)
	{
		$erp = $this->model('suborder_store')
		->table('store','left join','store.id=suborder_store.store')
		->table('erp','left join','erp.id=store.erp')
		->where('suborder_store.id=?',[$id])
		->find([
			'erp.*',
			'concat(replace(suborder_store.date,"-",""),suborder_store.id) as orderno',
		]);
		
		if (!empty($erp['name']))
		{
			$classname = 'application\\helper\\erp\\'.$erp['name'];
			$class = new $classname();
			
			
			if (method_exists($class, 'initlize'))
			{
				$class->initlize();
			}
			
			$class->setAppid($erp['appid']);
			$class->setAppsecret($erp['appsecret']);
			$class->setParameter($erp['parameter']);
			$class->setUrl($erp['url']);
			
			$result = call_user_func_array([$class,'GetOrderRoute'], [$erp['orderno']]);
			return $result;
		}
	}
}