<?php
namespace application\control\view;
use system\core\view;
use application\helper\pay;
use application\message\json;
class vip extends view
{
	function __construct()
	{
		$this->_csrf_token_refresh = false;
		parent::__construct();
	}
	
/**
	 * 订单支付页面
	 * @return string|\application\control\view\order
	 */
	function payment()
	{
		$paytype = $this->get('paytype','alipay');
		$id = $this->get('id');
		if (!empty($id))
		{
			$order = $this->model('vip_order')->where('id=?',[$id])->find();
			if (!empty($order))
			{
				$partner = $this->model('system')->get('partner',$paytype);
				$key = $this->model('system')->get('key',$paytype);
				
				$pay = new pay();
				$pay->setPartner($partner);
				$pay->setKey($key);
				$pay->setClient('web');
				$pay->setCharset('utf-8');
				$pay->setSigntype('MD5');
				$pay->setPayType($paytype);
				$pay->setId($order['orderno']);
				$pay->setTimeout(3600);
				$pay->setMoney($order['payamount']);
				//微信支付需要的参数
				$pay->setCurrency('CNY');
				if ($paytype == 'wechat')
				{
					//微信支付需要的参数
					$notify_url = 'http://'.$_SERVER['HTTP_HOST'].'/gateway/wechat/vip_notify.php';
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
					$notify_url = 'http://'.$_SERVER['HTTP_HOST'].'/gateway/alipay/vip_notify.php';
					$pay->setNotifyUrl($notify_url);
					$return_url = $this->http->url('','mobile','account');//跳转到个人中心
					$pay->setReturnUrl($return_url);
					$pay->createParameter([
						'service' => 'alipay.wap.create.direct.pay.by.user',
						'payment_type' => 1,
						'show_url' => $return_url,
						'total_fee' => $order['payamount'],
						'seller_id' => $partner,
						'it_b_pay' => date('Y-m-d H:i',$_SERVER['REQUEST_TIME'] + $pay->getTimeout()),
					]);
				}
				
				$pay->setProductName('购买会员从V'.$order['vip_from'].'到V'.$order['vip_to']);
				
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
	
	function notify()
	{
		$type = $this->get('type');//支付方式
		
		$pay = new pay();
		$pay->setPayType($type);
		$vipHelper = new \application\helper\vip();
		$options = [];
		
		switch ($type)
		{
			case 'alipay':
				file_put_contents('./vip_notify.txt', json_encode($_POST));
				
				if($this->post->notify_type == 'trade_status_sync')
				{
					$notify_id = $this->post->notify_id;//通知id
					$sign_type = $this->post->sign_type;//签名方式
					$sign = $this->post->sign;//签名
					$orderno = $this->post->out_trade_no;//订单号
					$paynumber = $this->post->trade_no;//支付单号
					$tradetotal = $this->post->total_fee;//外币总额
		
					$status = $this->post->trade_status;//状态
					
					$options['paytime'] = $this->post('gmt_payment',$_SERVER['REQUEST_TIME'],'strtotime');//支付时间
					
					$pay->setUrl('https://mapi.alipay.com/gateway.do');
					$pay->createParameter([
						'notify_id' => $notify_id,
						'rsa_public_key' => $this->model('system')->get('rsa_public_key','alipay'),//支付宝公钥地址
						'postParameter' => $this->post(),
					]);
					$pay->setSigntype($sign_type);
					
					$partner = $this->model('system')->get('partner','alipay');
					$key = $this->model('system')->get('key','alipay');
					$pay->setPartner($partner);
					$pay->setKey($key);
					if ($pay->auth())
					{
						if ($status == 'TRADE_FINISHED' || $status == 'TRADE_SUCCESS')
						{
							if($vipHelper->payedOrder($orderno,$type,$paynumber,$tradetotal,$options))
							{
								return 'success';
							}
							else
							{
								return '不要重复支付';
							}
						}
						else
						{
							return '支付状态错误';
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
				file_put_contents('./vip_wechat.txt', $content);
				$pay->createParameter('postParameter',$content);
				$pay->setKey($this->model('system')->get('key','wechat'));
				$content = $pay->auth();
				if ($content)
				{
					$orderno = $content['out_trade_no'];
					$pay_time = empty($content['time_end'])?$_SERVER['REQUEST_TIME']:strtotime($content['time_end']);
					$pay_number = $content['transaction_id'];
					$pay_money = $content['cash_fee']/100;
					$options['paytime'] = $pay_time;
					if($content['result_code'] == 'SUCCESS' && $content['result_code'] == 'SUCCESS')
					{
						if($vipHelper->payedOrder($orderno,$type,$pay_number,$pay_money,$options))
						{
							$this->model('wechatpay_cache')->where('orderno=?',[$orderno])->delete();
							return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
						}
					}
				}
				break;
		}
		
	}
}