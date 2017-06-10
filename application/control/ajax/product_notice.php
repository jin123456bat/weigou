<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
use application\helper\admin;
use application\helper\sms;

class product_notice extends ajax
{
	/**
	 * 发送短信
	 * @return \application\message\json
	 */
	function send()
	{
		$id = $this->post('id',0,'intval');
		$product_notice = $this->model('product_notice')->where('id=?',[$id])->find();
		if (empty($product_notice))
		{
			return new json(json::PARAMETER_ERROR,'没有该通知');
		}
		
		$product = $this->model('product')->where('id=?',[$product_notice['pid']])->find();
		
		$user = $this->model('user')->where('id=?',[$product_notice['uid']])->find();
		
		$template = $this->model('product_notice_template')->orderby('host','desc')->find();
		if (!empty($template))
		{
			$content = $template['content'];
			
			$content = str_replace(array(
				'{product_name}',
				'{user_name}',
				'{user_telephone}',
				'{date}',
			), array(
				$product['name'],
				$user['name'],
				$user['telephone'],
				date('Y-m-d',$product_notice['createtime'])
			), $content);
			
			$sms = new sms($this->model('system')->get('uid','sms'), $this->model('system')->get('key','sms'), $this->model('system')->get('sign','sms'));
			$num = $sms->send($user['telephone'], $content);
			if($num>0)
			{
				$this->model('product_notice')->where('id=?',[$id])->limit(1)->update([
					'send'=>1,
					'sendtime' =>$_SERVER['REQUEST_TIME'],
					'content' => $content,
				]);
				return new json(json::OK,NULL,$num);
			}
			else
			{
				switch ($num)
				{
					case '-1':
						return new json(json::PARAMETER_ERROR, '没有该用户账户');
					case '-2':
						return new json(json::PARAMETER_ERROR, '接口密钥不正确');
					case '-21':
						return new json(json::PARAMETER_ERROR, 'MD5接口密钥加密不正确');
					case '-11':
						return new json(json::PARAMETER_ERROR, '该用户被禁用');
					case '-14':
						return new json(json::PARAMETER_ERROR, '短信内容出现非法字符');
					case '-41':
						return new json(json::PARAMETER_ERROR, '手机号码为空');
					case '-42':
						return new json(json::PARAMETER_ERROR, '短信内容为空');
					case '-51':
						return new json(json::PARAMETER_ERROR, '短信签名格式不正确');
					case '-6':
						return new json(json::PARAMETER_ERROR, 'IP限制');
				}
				return new json(json::PARAMETER_ERROR);
			}
		}
		else 
		{
			return new json(json::PARAMETER_ERROR,'没有模板');
		}
	}
	
	
	function __access()
	{
		$adminHelper = new admin();
		return array(
			array(
				'deny',
				'actions' => ['send'],
				'message' => new json(json::NOT_LOGIN),
				'express' => empty($adminHelper->getAdminId()),
				'redict' => './index.php?c=admin&a=login',
			),
		);
	}
}