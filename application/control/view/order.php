<?php
namespace application\control\view;
use system\core\view;
use application\helper\pay;
use application\message\json;
use application\helper\express;
class order extends view
{
	function __construct()
	{
		$this->_csrf_token_refresh = false;
		parent::__construct();
	}
	
	function way()
	{
		$id = $this->post('id');
		if (!empty($id))
		{
			$package = $this->model('order_package')->where('id=?',[$id])->find();
			if (!empty($package['ship_type']) && !empty($package['ship_number']))
			{
				$response = express::queryJuhe($package['ship_type'], $package['ship_number']);
				$response = json_decode($response,true);
				if ($response['resultcode'] == 200)
				{
					$this->assign('way', $response['result']['list']);
				}
			}
			return $this;
		}
	}
	
	/**
	 * 发货列表
	 */
	function send()
	{
		$orderno = $this->post('orderno');
		if (!empty($orderno))
		{
			/* $product = $this->model('order_package')
			->table('order_product','left join','order_product.package_id=order_package.id')
			->table('product','left join','product.id=order_product.pid')
			->where('order_package.orderno=?',[$orderno])
			->select([
				'order_product.pid',
				'product.name',
				'order_product.content',
				'order_product.num',
				'order_package.ship_type',
				'order_package.ship_number',
			]); */
			
			
			//检索出当前所有包裹
			$package = $this->model('order_package')->table('store','left join','store.id=order_package.store_id')->where('orderno=?',[$orderno])->select([
				'order_package.id',
				'store.name',
				'order_package.ship_type',
				'order_package.ship_number',
			]);
			
			//检索包裹中的所有商品
			foreach ($package as &$p)
			{
				$product = $this->model('order_product')->table('product','left join','product.id=order_product.pid')->where('order_product.package_id=?',[$p['id']])->select([
					'product.name',
					'order_product.num',
					'order_product.content',
					'order_product.price',
					'order_product.pid'
				]);
				$p['product'] = $product;
			}
			
			$this->assign('package', $package);
			
			$this->assign('ship', $this->model('ship')->select());
		}
		return $this;
	}
	
	function crontab()
	{
		//关闭超时未支付的订单
		//create event `quit_order_not_payed` on schedule every 1 second do update `order` set order.status=0,order.quittime=unix_timestamp(now()) where pay_status=0 and status=1 and unix_timestamp(now())-createtime>=3600 and (select task_user.orderno from task_user where task_user.orderno=order.orderno) is null;
		
		
		//订单自动收货  15天
		//create event `auto_receive_order` on schedule every 1 second do update `order` set receive=1,receive_time=unix_timstamp(now()) where status=1 and way_status=1 and unix_timestamp(now()) - way_time > 15*3600*24 and receive=0
		
		
		//找出所有 未完成 有效 的团购主任务（不论是否支付成功）
		$task_user = $this->model('task_user')
		->table('`order`','left join','order.orderno=task_user.orderno')
		->table('task','left join','task.id=task_user.tid')
		->where('task_user.status=? and task_user.o_orderno is null and order.status=?',[0,1])
		->select([
			'task.day',//有效期
			'order.createtime',//创建时间
			'order.pay_status',
			'order.orderno',
		]);
		$orderHelper = new \application\helper\order();
		
		foreach ($task_user as $main_order)
		{
			if ($_SERVER['REQUEST_TIME'] - $main_order['createtime'] > $main_order['day']*3600*24)
			{
				//假如团购主任务超时
				//假如主订单已经支付,则退款
				if ($main_order['pay_status'] == 1)
				{
					if(!$orderHelper->refund($main_order['orderno']))
					{
						return new json(json::PARAMETER_ERROR,'订单退款失败'.$main_order['orderno']);
					}
				}
				//关闭订单,
				if(!$orderHelper->quitOrder($main_order['orderno']))
				{
					return new json(json::PARAMETER_ERROR,'关闭主订单失败'.$main_order['orderno']);
				}
				//标记团购失败
				if (!$this->model('task_user')->where('orderno=?',[$main_order['orderno']])->limit(1)->update([
					'status' => 2
				]))
				{
					return new json(json::PARAMETER_ERROR,'标记团购订单失败');
				}
				
				//子订单(不考虑是否支付)
				$sub_order = $this->model('task_user')
				->table('`order`','left join','order.orderno=task_user.orderno')
				->where('task_user.o_orderno=? and order.status=? and task_user.status=?',[$main_order['orderno'],1,0])
				->select([
					'order.orderno',
					'order.pay_status',
				]);
				foreach ($sub_order as $order)
				{
					//假如子订单也支付了
					if ($order['pay_status']==1)
					{
						if(!$orderHelper->refund($order['orderno']))
						{
							return new json(json::PARAMETER_ERROR,'订单退款失败'.$order['orderno']);
						}
					}
					//关闭订单
					if(!$orderHelper->quitOrder($order['orderno'],false))
					{
						return new json(json::PARAMETER_ERROR,'子订单关闭失败'.$order['orderno']);
					}
					//标记团购失败
					if (!$this->model('task_user')->where('orderno=?',[$order['orderno']])->limit(1)->update([
						'status' => 2
					]))
					{
						return new json(json::PARAMETER_ERROR,'标记团购订单失败');
					}
				}
			}
		}
		return new json(json::OK);
	}
	
	/**
	 * 订单回调页面
	 */
	function notify()
	{
		$pay = new pay();
		$pay->setPayType($this->get('type'));
		$options = [];
		$orderHelper = new \application\helper\order();
		
		switch ($this->get('type','alipay','strtolower'))
		{
			case 'alipay':
				$log = json_encode($_POST);
				file_put_contents('./alipay_notify.txt', $log);
				if($this->post->notify_type == 'trade_status_sync')
				{
					$notify_id = $this->post->notify_id;//通知id
					$sign_type = $this->post->sign_type;//签名方式
					$orderno = $this->post->out_trade_no;//订单号
					$pay_number = $this->post->trade_no;//支付单号
					$pay_money = $this->post->total_fee;//外币总额
					$status = $this->post->trade_status;//状态
					
					$partner = $this->model('system')->get('partner','alipay');
					$key = $this->model('system')->get('key','alipay');
					
					$options['pay_time'] = $this->post('gmt_payment',$_SERVER['REQUEST_TIME'],'strtotime');//支付时间
					
					$pay->setUrl('https://mapi.alipay.com/gateway.do');
					$pay->createParameter([
						'notify_id' => $notify_id,
						'rsa_public_key' => $this->model('system')->get('rsa_public_key','alipay'),//支付宝公钥地址
						'postParameter' => $this->post(),
					]);
					$pay->setSigntype($sign_type);
					
					$pay->setPartner($partner);
					$pay->setKey($key);
					if ($pay->auth())
					{
						if ($status == 'TRADE_FINISHED' || $status == 'TRADE_SUCCESS')
						{
							if($orderHelper->payedOrder($orderno,'alipay',$pay_number,$pay_money,$options))
							{
								return 'success';
							}
							else
							{
								return 'failed';
							}
						}
						else
						{
							return '状态错误';
						}
					}
					else
					{
						return '验证失败';
					}
				}
				break;
			case 'wechat':
				$content = file_get_contents('php://input');
				file_put_contents('./wechat_notify.txt', $content);
				$pay->createParameter('postParameter',$content);
				$pay->setKey($this->model('system')->get('key','wechat'));
				$content = $pay->auth();
				if ($content)
				{
					$orderno = $content['out_trade_no'];
					$pay_time = empty($content['time_end'])?$_SERVER['REQUEST_TIME']:strtotime($content['time_end']);
					$pay_number = $content['transaction_id'];
					$pay_money = $content['cash_fee']/100;
					$options['pay_time'] = $pay_time;
					$options['device'] = $content['device_info'];
					if($content['result_code'] == 'SUCCESS' && $content['result_code'] == 'SUCCESS')
					{
						if($orderHelper->payedOrder($orderno,'wechat',$pay_number,$pay_money,$options))
						{
							$this->model('wechatpay_cache')->where('orderno=?',[$orderno])->delete();
							return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
						}
					}
				}
				break;
		}
	}
	
	function refund()
	{
		if ($this->post('notify_type') === 'batch_refund_notify')
		{
			$pay = new \application\helper\pay();
			
			$pay->setPayType('alipay');
			$partner = $this->model('system')->get('partner','alipay');
			$key = $this->model('system')->get('key','alipay');
			$refundtime = $this->post('notify_time',$_SERVER['REQUEST_TIME'],'strtotime');//退款时间
			$pay->setUrl('https://mapi.alipay.com/gateway.do');
			$pay->createParameter([
				'notify_id' => $this->post('notify_id'),
				'postParameter' => $this->post(),
			]);
			$pay->setSigntype($this->post('sign_type'));
			$pay->setPartner($partner);
			$pay->setKey($key);
			if ($pay->auth())
			{
				$batch_no = $this->post('batch_no');
				$success_num = $this->post('success_num');//代表退款成功
				if (!empty($batch_no))
				{
					$refundno = substr($batch_no, 8);//去除前8位日期
					$refund = $this->model('refund')->where('refundno=?',[$refundno])->find();
					$order = $this->model('order')->where('orderno=?',[$refund['orderno']])->find();
					if (empty($order))
					{
						return new json(json::PARAMETER_ERROR,'订单不存在');
					}
					
					if ($order['pay_status'] == 0)
					{
						return new json(json::PARAMETER_ERROR,'订单尚未支付，无法完成退款');
					}
					if ($order['pay_status'] == 2)
					{
						return new json(json::PARAMETER_ERROR,'订单已退款，无法完成退款');
					}
					
					if (empty($refund['order_product_id']))
					{
						if ($success_num >= 1)
						{
							//订单标记为退款成功
							if($this->model('order')->where('orderno=?',[$refund['orderno']])->limit(1)->update([
								'pay_status' => 2,
								'refundtime' => $refundtime,
							]))
							{
								//加载回调
								$class_name = '\application\callback\order';
								if (class_exists($class_name,true) && method_exists($class_name, 'refundOrder') && is_callable([$class_name,'refundOrder']))
								{
									if(!call_user_func([new $class_name(),'refundOrder'],$refundno))
									{
										return false;
									}
								}
								
								return 'success';
							}
							else
							{
								return new json(json::PARAMETER_ERROR,'订单更改失败');
							}
						}
						else
						{
							//先记录一下退款失败原因
							$this->model('refund')->where('refundno=?',[$refundno])->limit(1)->update('reason',json_encode($_POST));
							//退款失败，回滚
						}
					}
					else
					{
						if ($success_num >= 1)
						{
							$order_product = $this->model('order_product')->where('id=?',[$refund['order_product_id']])->find();
							if (empty($order_product))
							{
								return new json(json::PARAMETER_ERROR,'商品不存在');
							}
							if ($order_product['refund'] == 2)
							{
								//商品标记为退款成功
								if($this->model('order_product')->where('id=?',[$refund['order_product_id']])->limit(1)->update([
									'order_product.refund' => 1,
									'order_product.refundtime' => $refundtime,
								]))
								{
									if($this->model('order')->where('orderno=?',[$refund['orderno']])->limit(1)->update([
										'refundtime' => $refundtime,
										'pay_status' => 4,
									]))
									{
										//商品退款成功 调用相应的回调
										$class_name = '\application\callback\refund';
										if (class_exists($class_name,true) && method_exists($class_name, 'product') && is_callable([$class_name,'product']))
										{
											if(!call_user_func([new $class_name(),'product'],$refundno))
											{
												return new json(json::PARAMETER_ERROR,'商品退款回调失败');
											}
										}
										
										return 'success';
									}
									else
									{
										return new json(json::PARAMETER_ERROR,'订单标记为部分退款失败');
									}
								}
								else
								{
									return new json(json::PARAMETER_ERROR,'标记商品为退款状态失败');
								}
							}
							else 
							{
								return new json(json::PARAMETER_ERROR,'商品退款状态错误');
							}
						}
						else
						{
							//先记录一下退款失败原因
							$this->model('refund')->where('refundno=?',[$refundno])->limit(1)->update('reason',json_encode($_POST));
							//退款失败  回滚
						}
					}
				}
			}
			else
			{
				return '验证失败';
			}
		}
	}
	
	/**
	 * 订单支付页面
	 * @return string|\application\control\view\order
	 */
	function payment()
	{
		$paytype = $this->get('paytype','alipay');
		$orderno = $this->get('orderno');
		if (!empty($orderno))
		{
			$order = $this->model('order')->where('orderno=?',[$orderno])->find();
			if (!empty($order))
			{
				if ($order['pay_status'] != 0)
				{
					$this->response->setCode(302);
					$this->response->addHeader('Location',$this->http->url('','mobile','orderinfo',[
						'orderno' => $orderno
					]));
				}
				else
				{
					$partner = $this->model('system')->get('partner',$paytype);
					$key = $this->model('system')->get('key',$paytype);
					
					$pay = new pay();
					$pay->setPartner($partner);
					$pay->setKey($key);
					$pay->setClient('web');
					$pay->setCharset('utf-8');
					$pay->setSigntype('md5');
					$pay->setPayType($paytype);
					$pay->setId($orderno);
					$pay->setMoney($order['orderamount']);
					$pay->setCurrency('CNY');
					$pay->setTimeout(3600);
					if ($paytype == 'wechat')
					{
						//微信支付需要的参数
						$notify_url = 'http://'.$_SERVER['HTTP_HOST'].'/gateway/wechat/order_notify.php';
						$pay->setNotifyUrl($notify_url);
						$pay->createParameter('trade_type','JSAPI');
						
						$user = $this->model('user')->where('id=?',[$order['uid']])->find();
						$order_user_openid = $user['wx_openid_web'];
						if (empty($order_user_openid))
						{
							//假如下单用户没有openid则使用当前登录的用户的openid来支付
							$appid = $this->model('system')->get('appid','wechat');
							$appsecret = $this->model('system')->get('appsecret','wechat');
							$this->_wechat = new \application\helper\wechat($appid, $appsecret);
							if ($this->get->code === NULL)
							{
								$location = $this->_wechat->getCode($this->http->url(), 'snsapi_base');
								header('Location: '.$location,true,302);
								exit();
							}
							else
							{
								$code = $this->get->code;
								$codeinfo = $this->_wechat->getOpenid($code);
								if (isset($codeinfo['openid']))
								{
									$openid = $codeinfo['openid'];
								}
							}
						}
						else
						{
							$openid = $order_user_openid;
						}
							
							
						$pay->createParameter('openid',$openid);//用户的openid
						$appid = $this->model('system')->get('appid','wechat');
						if (empty($appid))
							return new json(json::PARAMETER_ERROR,'尚未绑定公众账号，无法使用微信支付');
						$pay->createParameter('appid',$appid);//公众号的appid
					}
					else
					{
						//支付宝需要的参数
						$notify_url = 'http://'.$_SERVER['HTTP_HOST'].'/gateway/alipay/order_notify.php';
						$pay->setNotifyUrl($notify_url);
						$return_url = $this->http->url('','mobile','orderinfo',[
							'orderno' => $orderno,
						]);
						$pay->setReturnUrl($return_url);
						$pay->createParameter([
							'service' => 'alipay.wap.create.direct.pay.by.user',
							'payment_type' => 1,
							'show_url' => $return_url,
							'total_fee' => $order['orderamount'],
							'seller_id' => $partner,
							'it_b_pay' => date('Y-m-d H:i',$_SERVER['REQUEST_TIME'] + $pay->getTimeout()),
						]);
					}
					$product = $this->model('order_product')
					->table('order_package','left join','order_product.package_id=order_package.id')
					->table('product','left join','product.id=order_product.pid')
					->where('order_package.orderno=?',[$orderno])
					->select('product.name,order_product.content,order_product.num');
					$productName = '';
					$productNum = 0;
					$productCNum = 0;
					
					foreach ($product as $p)
					{
						$productCNum++;
						if (empty($productName))
						{
							$productName = $p['name'];
						}
						$productNum += $p['num'];
					}
					$productName = mb_substr($productName, 0,100);
					if ($productCNum > 1)
					{
						$productName .= '等'.$productNum.'件商品';
					}
					$pay->setProductName($productName);
					
					$parameter = $pay->createPayParameter();
					if (!$parameter)
					{
						$msg = $pay->getLastError();
						$this->assign('msg', $msg);
						$this->setViewname('pay_failed');
						return $this;
					}
					switch (strtolower($paytype))
					{
						case 'alipay':
							$data = [
								'url' => 'https://mapi.alipay.com/gateway.do',
								'method' => 'get',
								'parameter'=>$parameter,
							];
							$this->assign('data', $data);
							if (isWechat())
							{
								$this->setViewname('jump');
							}
							else
							{
								$this->setViewname('alipay');
							}
							break;
						case 'wechat':
							$this->assign('data', $parameter);
							$this->setViewname('wechat');
							break;
						default:
					}
					return $this;
				}
			}
		}
	}
}