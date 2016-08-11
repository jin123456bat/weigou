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
		/*
		$suborder_id_array = $this->model('order')->table('suborder_store','left join','suborder_store.main_orderno=order.orderno')
		->where('order.way_status=?',[0])
		->where('suborder_store.erp=?',[1])
		->select(['suborder_store.id']);
		
		$erpSender = new \application\helper\erpSender();
		foreach ($suborder_id_array as $suborder_id)
		{
			$result = $erpSender->doGetOrder($suborder_id['id']);
			$result = json_decode($result,true);
			if(!empty($result))
			{
				if (isset($result['Data']) && isset($result['Code']) && $result['Code']==0 && !empty($result['Data']))
				{
					$data = json_decode($result['Data'],true);
					if (!empty($data))
					{
						$select_orderno_array = [];
						
						foreach ($data as $sub_order)
						{
							$sub_orderno = $sub_order['ExternalOrder'];
							$sub_id = substr($sub_orderno, 8);
							
							if (!empty($sub_order['ShippingTrackingNumber']) && !empty($sub_order['ShippingCompany']))
							{
								$suborder = $this->model('suborder_store')->where('id=?',[$sub_id])->find();
								if (!empty($suborder))
								{
									$ship = $this->model('ship')->where('name=?',[$sub_order['ShippingCompany']])->find();
									$ship_type = isset($ship['code'])?$ship['code']:'';
									if (!empty($ship_type))
									{
										if($this->model('order_package')->where('orderno=? and store_id=?',[$suborder['main_orderno'],$suborder['store']])->update([
											'ship_type' => $ship_type,
											'ship_number' => $sub_order['ShippingTrackingNumber'],
											'ship_time' => $_SERVER['REQUEST_TIME'],
											'ship_status' => 1,
										]))
										{
											$select_orderno_array[] = $suborder['main_orderno'];
										}
									}
								}
							}
						}
						
						
						$select_orderno_array = array_unique($select_orderno_array);
						foreach ($select_orderno_array as $orderno)
						{
							if(empty($this->model('order_package')->where('orderno=? and ship_status=?',[$orderno,0])->find()))
							{
								$this->model('order')->where('orderno=?',[$orderno])->limit(1)->update([
									'way_status' => 1,
									'way_type' => 3,
									'way_time' => $_SERVER['REQUEST_TIME']
								]);
							}
						}
					}
				}
			}
		}
		*/
		
	}
	
}