<?php
namespace application\control\view;
use system\core\view;
use application\helper\erpSender;
use application\helper\erp\oms;
/**
 * @author fx
 *
 */
class api extends view
{
	function __construct()
	{
		$this->_csrf_token_refresh = false;
		parent::__construct();
	}
	
	/**
	 * oms推送订单状态到我们这边
	 * 
	 */
	function pushOrderStatus()
	{
		$uncode = $this->post("uncode");
		$appid = $this->post('appid');
		$xml = $this->post('xml');
		$md5 = $this->post('md5');
		
		$oms = new \application\helper\oms();
		$xml = $oms->desrypt($uncode, $appid, $xml, $md5);
		if ($xml===false)
		{
			return $oms->encrypt(false,'解密失败');
		}
		else
		{
			
		}
	}
	
	/**
	 * 同步商品库存
	 */
	function asyncStock()
	{
		$id = $this->post('id');
		if (is_array($id) && !empty($id))
		{
			$product = $this->model('product')->where('store=? and isdelete=?',[27,0])->where('id in (?)',$id)->select('id,barcode,store');
		}
		else
		{
			$product = $this->model('product')->where('store=? and isdelete=?',[27,0])->select('id,barcode,store');
		}
		
		$erpSender = new erpSender();
		
		//清空库存
		$this->model('store_stock')->delete();
		$this->model('product')->where('store=? and isdelete=?',[27,0])->update('stock',0);
		
		foreach ($product as $p)
		{
			//$stock = $erpSender->QueryGoodsInventory($p['id']);
			
			if (empty($p['barcode']) || empty($p['store']))
			{
				continue;
			}
			
			$stock = $erpSender->doAction(2, 'QueryGoodsInventory',[$p['barcode'],$p['store']]);
			
			if ($stock === false)
			{
				//查询失败
			}
			else
			{
				//这里是只对接一个仓库的时候
				/* $this->model('product')->where('id=?',[$p['id']])->limit(1)->update([
					'stock' => $stock,
					'auto_stock'=>0,//关闭不限制库存
					'modifytime' => $_SERVER['REQUEST_TIME']
				]); */
				
				if (is_array($stock))
				{
					//多个仓库按照仓库id为下标返回数组
					$sum_stock = 0;
					foreach ($stock as $s_store => $stock)
					{
						if (!empty($stock))
						{
							//更新子仓库库存
							$this->model('store_stock')->insert([
								'son_store' => $s_store,
								'pid' => $p['id'],
								'stock' => $stock
							]);
							$sum_stock+=$stock;
						}
					}
					//更新商品实际库存
					$this->model('product')->where('id=?',[$p['id']])->limit(1)->update([
						'stock' => $sum_stock,
						'auto_stock'=>1,//关闭不限制库存
						'modifytime' => $_SERVER['REQUEST_TIME']
					]);
				}
				else
				{
					//更新商品实际库存
					$this->model('product')->where('id=?',[$p['id']])->limit(1)->update([
						'stock' => $stock,
						'auto_stock'=>1,//关闭不限制库存
						'modifytime' => $_SERVER['REQUEST_TIME']
					]);
				}
			}
		}
	}
	
	/**
	 * 维护失败调用的接口定时发送  10分钟1次
	 */
	function crontab()
	{
		//对于调用失败的接口重新发起
		$api_log = $this->model('api_log')->where('success=? and times < ?',[0,10])->select();
		foreach ($api_log as $api)
		{
			$classname = new erpSender();
			$method = $api['method'];
			$parameter = json_decode($api['parameter'],true);
			$class = new $classname();
			$class->setRepeatId($api['id']);
			
			call_user_func_array([$class,$method], $parameter);
		}
		
		
		//同步订单的物流信息
		
		$suborder_id_array = $this->model('order')
		->table('suborder_store','left join','suborder_store.main_orderno=order.orderno')
		->where('order.way_status in (?)',[0,2])//尚未发货 或者部分发货
		->where('order.pay_status in (?)',[1,4])//已经支付,或部分退款
		->where('order.status=?',[1])//订单有效
		->where('suborder_store.erp=?',[1])//erp已经推送
		->where('suborder_store.store=?',[27])//限制发货仓库id为27
		->select(['suborder_store.id','suborder_store.main_orderno as orderno','suborder_store.store']);

		//本次查询的订单
		$select_orderno = [];
		
		$erpSender = new \application\helper\erpSender();
		foreach ($suborder_id_array as $suborder)
		{
			$orderStatus = $erpSender->doAction(2, 'QueryOrderStatus',[$suborder['id']]);//获取订单状态
			if ($orderStatus!==false)
			{
				//订单状态查询成功
				//订单已发货
				if ($orderStatus['OrderStatus'] == 40)
				{
					$ship_code = $orderStatus['ShippingExpressId'];//40
					//配送方式替换
					$ship = $this->model('ship')->where('oms=?',[$ship_code])->find();
					if (!empty($ship))
					{
						$ship_code = $ship['code'];
					}
					
					$ship_number = $orderStatus['ShippingCode'];//快递单号
					$ship_time = strtotime($orderStatus['ShippingTime']);//发货时间
					
					$this->model('order_package')->where('orderno=? and store_id=?',[$suborder['orderno'],$suborder['store']])
					->update([
						'ship_status' => 1,
						'ship_type' => $ship_code,
						'ship_time' => $ship_time,
						'ship_number' => $ship_number,
					]);
					
					$select_orderno[] = $suborder['orderno'];
				}
			}
		}
		
		foreach ($select_orderno as $orderno)
		{
			//查找是否还有未发货的包裹
			if(empty($this->model('order_package')->where('orderno=? and ship_status=?',[$orderno,0])->find()))
			{
				//标记订单为已发货
				$this->model('order')->where('orderno=?',[$orderno])->limit(1)->update([
					'way_status' => 1,
					'way_type' => 3,
					'way_time' => $_SERVER['REQUEST_TIME']
				]);
			}
			else
			{
				//标记订单为部分发货
				$this->model('order')->where('orderno=?',[$orderno])->limit(1)->update([
					'way_status' => 2,
					'way_type' => 3,
					'way_time' => $_SERVER['REQUEST_TIME']
				]);
			}
		}
		
		
	}
	
}