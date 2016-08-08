<?php
namespace application\control\view;
use system\core\view;
use application\helper\erpSender;
class api extends view
{
	function __construct()
	{
		$this->_csrf_token_refresh = false;
		parent::__construct();
	}
	
	/**
	 * 维护失败调用的接口定时发送  10分钟1次
	 */
	function crontab()
	{
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
	}
	
	function getOrderInfo()
	{
	    
	}
}