<?php
namespace application\control\view;
use system\core\view;
use application\message\json;
use application\helper\admin;
use application\helper\order;

class html extends view
{
	function create_brand()
	{
		$country = $this->model('country')->select();
		$this->assign('country', $country);
		return $this;
	}
	
	/**
	 * 当调用action之前都会执行这个函数
	 */
	function __initlize()
	{
		$action = $this->get('a','');
		
		//选中的当前菜单的信息
		$menu = $this->model('admin_menu')->where('link=?',[$action])->find();
		$this->assign('menu', $menu);
		$global_site_type_action = [
			0=>'user',
			1=>'product',
		];
		$global_site_type_action[$menu['type']] = $action;
		$this->assign('global_site_type_action', $global_site_type_action);
		
		if (!empty($menu['extra']))
		{
			$extra = $this->model('admin_menu')->where('link=?',[$menu['extra']])->find();
		}
		
		//所有1级菜单
		$menu1 = $this->model('admin_menu')->orderby('sort','asc')->where('type=? and display=? and u_link is null',[$menu['type'],1])->select();
		foreach ($menu1 as &$m)
		{
			if ($m['id'] == $menu['u_link'] || $m['id'] == $extra['u_link'])
			{
				$m['active'] = true;
			}
			else
			{
				$m['active'] = false;
			}
			
			if (empty($m['link']))
			{
				$m['link'] = $this->model('admin_menu')->where('host=1 and u_link=?',[$m['id']])->scalar('link');
			}
		}
		$this->assign('menu1', $menu1);
		
		//所有2级菜单
		$menu2 = $this->model('admin_menu')->orderby('sort','asc')->where('(u_link=? or u_link=?) and display=?',[$menu['u_link'],$extra['u_link'],1])->select();
		foreach ($menu2 as &$m)
		{
			if ($m['link'] == $action || $m['link'] === $menu['extra'])
			{
				$m['active'] = true;
			}
			else
			{
				$m['active'] = false;
			}
		}
		$this->assign('menu2', $menu2);
		
		$page_title = $menu['name'];
		$page_title_reverse = $menu['name'];
		$temp = $menu['u_link'];
		while (!empty($temp))
		{
			$temp = $this->model('admin_menu')->where('id=?',[$temp])->find();
			if (!empty($temp))
			{
				$page_title_reverse = $temp['name'].' - '.$page_title_reverse;
				$page_title .= (' - '.$temp['name']);
				$temp = $temp['u_link'];
			}
			else
			{
				break;
			}
		}
		$this->assign('page_title_reverse', $page_title_reverse);
		$this->assign('page_title', $page_title);
	}
	
	function edit_brand()
	{
		$id = $this->get('id',0,'intval');

		$country = $this->model('country')->select();
		$this->assign('country', $country);
		
		$this->setViewname('create_brand');
		
		$brand = $this->model('brand')->where('id=?',[$id])->find();
		$this->assign('brand', $brand);
		
		return $this;
	}
	
	function product_create()
	{
		$province = $this->model('province')->select();
		$this->assign('province', $province);
		
		$bcategory = $this->model('bcategory')->where('bc_id is null')->select();
		$this->assign('bcategory', $bcategory);
		
		$this->assign('measurement', $this->model('dictionary')->where('type=?',['MeasurementUnit'])->select());
		
		$this->assign('publish', $this->model('publish')->where('isdelete=?',[0])->select());
		
		$this->assign('store', $this->model('store')->where('isdelete=?',[0])->select());
		
		$ztax = $this->model('tax')->select('id,name,gtax,xtax,ztax');
		foreach ($ztax as &$tax)
		{
			$tax['tax'] = ($tax['xtax'] + $tax['ztax']) / (1 - $tax['xtax']) * 0.7;
		}
		$this->assign('ztax', $ztax);
		
		$postTaxNo = $this->model('posttaxno')->select([
			'id','name','tax',
		]);
		$this->assign('posttaxno', $postTaxNo);
		return $this;
	}
	
	function product_edit()
	{
		$id = $this->get('id');
		if (!empty($id))
		{
			$province = $this->model('province')->select();
			$this->assign('province', $province);
		
			$bcategory = $this->model('bcategory')->where('bc_id is null')->select();
			$this->assign('bcategory', $bcategory);
		
			$this->assign('measurement', $this->model('dictionary')->where('type=?',['MeasurementUnit'])->select());
		
			$this->assign('publish', $this->model('publish')->where('isdelete=?',[0])->select());
		
			$this->assign('store', $this->model('store')->where('isdelete=?',[0])->select());
		
			$ztax = $this->model('tax')->select('id,name,gtax,xtax,ztax');
			foreach ($ztax as &$tax)
			{
				$tax['tax'] = ($tax['xtax'] + $tax['ztax']) / (1 - $tax['xtax']) * 0.7;
			}
			$this->assign('ztax', $ztax);
		
			$postTaxNo = $this->model('posttaxno')->select([
				'id','name','tax',
			]);
			$this->assign('posttaxno', $postTaxNo);
			
			$this->assign('id', $id);
			return $this;
		}
	}
	
	function orderdetail()
	{
		$orderno = $this->get('orderno','');
		$orderHelper = new order();
		$order = $this->model('order')->where('orderno=?',[$orderno])->find();
		$order['convertStatus'] = $orderHelper->convertStatus($order);
		$order['user'] = $this->model('user')->where('id=?',[$order['uid']])->find();
		
		$order['user']['ouser'] = $this->model('user')->where('id=?',[$order['user']['oid']])->find();
		$order['user']['omasteruser'] = $this->model('user')->where('id=?',[$order['user']['o_master']])->find();
		
		//订单商品
		$product = [];
		$productHelper = new \application\helper\product();
		$package = $this->model('order_package')->where('orderno=?',[$orderno])->select();
		foreach ($package as $pack)
		{
			$products = $this->model('order_product')->where('package_id=?',[$pack['id']])->select();
			foreach ($products as $p)
			{
				$p['image'] = $productHelper->getListImage($p['pid']);
				
				$temp_product = $this->model('product')->where('id=?',[$p['pid']])->find();
				$p['brand'] = $this->model('brand')->where('id=?',[$temp_product['brand']])->scalar('name_cn');
				$p['stock'] = $temp_product['stock'];
				$p['status'] = $temp_product['status'];
				
				$p['ship_type'] = $pack['ship_type'];
				$p['ship_number'] = $pack['ship_number'];
				
				//是否允许退款
				if ($order['pay_status']==1 || $order['pay_status']==4)
				{
					if ($p['refund']==0)
					{
						$p['allowRefund'] = true;
					}
					else if ($p['refund']==1)
					{
						$p['allowRefund'] = false;
					}
				}
				
				//是否允许查看物流  是否允许更改物流信息
				if ($pack['ship_status']==1)
				{
					$p['allowLogistics'] = true;
					$p['changeWay'] = true;
					$p['send'] = false;
				}
				else
				{
					$p['allowLogistics'] = false;
					$p['changeWay'] = false;
					$p['send'] = true;
				}
				
				
					
				$product[] = $p;
			}
		}
		$order['product'] = $product;
		
		$this->assign('order', $order);
		
		
		$publish = $this->model('publish')->where('isdelete=?',[0])->select();
		$this->assign('publish', $publish);
		
		$store = $this->model('store')->where('isdelete=?',[0])->select();
		$this->assign('store', $store);
		
		$ship = $this->model('ship')->select();
		$this->assign('ship', $ship);
		
		$log = $this->model('order_log')->orderby('time','desc')->where('orderno=?',[$orderno])->select();
		foreach ($log as &$l)
		{
			$l['auser'] = $this->model('admin')->where('id=?',[$l['aid']])->find();
		}
		$this->assign('log', $log);
		
		return $this;
	}
	
	function edit_product_notice_template()
	{
		$id = $this->get('id');
		if (!empty($id))
		{
			$product_notice_template = $this->model('product_notice_template')->where('id=?',[$id])->find();
			$this->assign('template', $product_notice_template);
			return $this;
		}
	}
	
	function order()
	{
		$ship = $this->model('ship')->select();
		$this->assign('ship', $ship);
		return $this;
	}
	
	function edit_task()
	{
		$id = $this->get('id',0,'intval');
		if (!empty($id))
		{
			$task = $this->model('task')->where('id=?',[$id])->find();
			$task['product'] = $this->model('product')->where('id=?',[$task['pid']])->find();
			$productHelper = new \application\helper\product();
			$task['product']['logo'] = $productHelper->getListImage($task['pid']);
			$this->assign('task', $task);
			return $this;
		}
	}
	
	function admin_create()
	{
		$role = $this->model('role')->select('id,name');
		$this->assign('role', $role);
		
		$privileges = $this->model('privileges')->select('id,name');
		$this->assign('privileges', $privileges);
		return $this;
	}
	
	function __access()
	{
		$adminHelper = new admin();
		return array(
			array(
				'deny',
				'actions' => '*',
				'message' => new json(json::NOT_LOGIN),
				'express' => empty($adminHelper->getAdminId()),
				'redict' => './index.php?c=admin&a=login',
			),
		);
	}
}