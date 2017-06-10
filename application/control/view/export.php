<?php
namespace application\control\view;

use system\core\view;
use application\message\json;

class export extends view
{
	private $_aid;
	
	function __construct()
	{
		$this->_csrf_token_refresh = false;
		parent::__construct();		
	}
	
	function __access()
	{
		$adminHelper = new admin();
		$this->_aid = $adminHelper->getAdminId();
		return array(
			array(
				'deny',
				'actions' => '*',
				'message' => new json(json::NOT_LOGIN),
				'express' => empty($this->_aid),
				'redict' => './index.php?c=admin&a=login',
			),
			array(
				'allow',
				'actions' => '*',
			)
		);
	}

	/**
	 * VIP订单导出
	 */
	function vip()
	{
		$vip_orderModel = $this->model('vip_order');
		$id = $this->post('id');
		if (is_array($id) && ! empty($id) && $this->post('all') == 'select')
		{
			$vip_orderModel->where('id in (?)', $id);
		}
		if ($this->post('all') == 'noselect')
		{
			if (! empty($id))
			{
				$vip_orderModel->where('id not in (?)', $id);
			}
		}
		$data = $vip_orderModel->select([
			'vip_order.id',
			'vip_order.orderno',
			'(select name from user where user.id=vip_order.uid) as username',
			'from_unixtime(vip_order.createtime)',
			'if(vip_from=0,if(vip_to=1,200,800),600) as money',
			'if(payprice=0,"未支付","已支付")',
			'concat(vip_from,"-",vip_to)',
			'ifnull((select money from swift where order_type=\'vip\' and swift.orderno=vip_order.orderno and source=5),0) as vip1',
			'ifnull((select money from swift where order_type=\'vip\' and swift.orderno=vip_order.orderno and source=6),0) as vip2',
			'ifnull((select money from swift where order_type=\'vip\' and swift.orderno=vip_order.orderno and source=7),0) as vip3'
		]);
		$template = ROOT . '/extends/PHPExcel/vip.xlsx';
		$this->model("admin_log")->insertlog($this->_aid, '导出vip订单记录成功', 1);
		$this->response($data, $template, 'VIP订单数据' . date('Y-m-d H:i:s'));
	}

	/**
	 * 订单信息导出
	 */
	function order()
	{
		$orderData = array();
		
		$orderModel = $this->model('order');
		$orders = $orderModel->select([
			'orderno',
			'(select group_concat(concat(replace(date,"-",""),id)) from suborder_store where suborder_store.main_orderno=order.orderno) as suborderno',
			'(select user.name from user where user.id=order.uid) as username',
			'from_unixtime(createtime) as createtime',
			'1 as status',//订单状态  暂时设置为1 作为占位使用，后面需要补上
			'if(erp=1,"已发送","未发送") as erp',
		]);
		foreach ($orders as $order)
		{
			$first_order_info = true;
			$order_info = $this->model('order')->where('orderno=?',[$order['orderno']])->find([
				'taxamount',
				'feeamount',
				'discount',
				'goodsamount',
				'orderamount',
				'pay_money',
				'address_province',
				'address_city',
				'address_county',
				'address_address',
				'address_telephone',
				'address_name',
				'address_identify',
				'note',
			]);
			
			$packages = $this->model('order_package')->where('orderno=?',[$order['orderno']])->select([
				'id as package_id',//包裹号
			]);
			
			$first_package = true;
			foreach ($packages as $package)
			{
				$products = $this->model('order_product')->where('package_id=?',[$package['package_id']])->select([
					'publish',
					'store_name',
					'name',
					'sku',
					'barcode',
					'num * bind as num',
					'inprice',
					'price',
				]);
				$first_product = true;
				foreach ($products as $product)
				{
					if ($first_product)
					{
						$order_product = array_merge($package,$product);
						$first_product = false;
					}
					else 
					{
						$order_product = array_merge(array_fill_keys(array_keys($package), ''),$product);
					}
					
					
					if ($first_package)
					{
						$order_package_product = array_merge($order,$order_product);
						$first_package = false;
					}
					else
					{
						$order_package_product = array_merge(array_fill_keys(array_keys($order), ''),$order_product);
					}
					
					
					if ($first_order_info)
					{
						$order_package_product = array($order_package_product,$order_info);
						$first_order_info = false;
					}
					else
					{
						$order_package_product = array_merge(array_fill_keys(array_keys($order_package_product), ''),$order_info);
					}
					
					$orderData[] = $order_package_product;
				}
			}
			$this->model('order_log')->add($order['orderno'], '订单导出',$this->_aid,1);
		}
		
		$template = ROOT . '/extends/PHPExcel/order.xlsx';
		$this->model("admin_log")->insertlog($this->_aid, '导出订单记录成功', 1);
		$this->response($orderData, $template, '订单数据' . date('Y-m-d H:i:s'));
	}

	/**
	 * 提现导出
	 */
	function drawal()
	{
		$drawalModel = $this->model('drawal');
		$id = $this->post('id');
		if (is_array($id) && ! empty($id) && $this->post('all') == 'select')
		{
			$drawalModel->where('id in (?)', $id);
		}
		if ($this->post('all') == 'noselect')
		{
			if (! empty($id))
			{
				$drawalModel->where('id not in (?)', $id);
			}
		}
		$data = $drawalModel->select([
			'drawal.id',
			'from_unixtime(drawal.createtime)',
			'(select name from user where user.id=drawal.uid) as username', // 户名
			'replace(replace((select concat_ws(" ",bankcard.type,bankcard.account,bankcard.name) from bankcard where bankcard.id=drawal.bankcard),"alipay","支付宝"),"bank","银行卡") as bankcard', // 提现账户
			'drawal.money',
			'replace(replace(drawal.pass,1,"通过"),0,"尚未通过")',
			'if(drawal.passtime!=0,from_unixtime(drawal.passtime),"")'
		]);
		$template = ROOT . '/extends/PHPExcel/drawal.xlsx';
		$this->model("admin_log")->insertlog($this->_aid, '导出提现记录成功', 1);
		$this->response($data, $template, '提现数据' . date('Y-m-d H:i:s'));
	}
	
	/**
	 * 导出商品完整信息
	 */
	function product()
	{
		$productModel = $this->model('product');
		
		if ($this->post('draft',NULL) !== NULL)
		{
			$productModel->where('draft=?', [
				$this->post('draft',0,'intval')
			]);
		}
		
		if ($this->post('stock',NULL) !== NULL)
		{
			$productModel->where('stock=?',[$this->post('stock',0,'intval')]);
		}
		
		if ($this->post('examine',NULL) !== NULL)
		{
			if (is_scalar($this->post('examine')))
			{
				$productModel->where('examine=?', [
					$this->post('examine',0,'intval')
				]);
			}
			else if (is_array($this->post('examine')))
			{
				$productModel->where('examine in (?)',$this->post('examine'));
			}
		}
		
		if ($this->post('isdelete',NULL)!==NULL)
		{
			$productModel->where('isdelete =?', [
				$this->post('isdelete',0,'intval')
			]);
		}
		
		if ($this->post('examine_final',NULL) !== NULL)
		{
			$productModel->where('examine_final=?',[
				$this->post('examine_final',1)
			]);
		}
		
		if ($this->post('status',NULL) !== NULL)
		{
			$productModel->where('status=?',[$this->post('status')]);
		}
			
		$id = $this->post('id');
		if (is_array($id) && ! empty($id))
		{
			$productModel->where('id in (?)', $id);
		}
		
		$bases = $productModel->select([
			'product.id as nid',
			'product.id',
			'(select bcategory.name from bcategory where id=(select bc_id from bcategory_product where bcategory_product.product_id=product.id limit 1) limit 1) as category',//分类
			'replace(replace(replace(replace(product.outside,0,"普通"),1,"进口"),2,"直供"),3,"直邮") as outside',//类别
			'product.name',
			'product.barcode',
			'(select name_cn from brand where brand.id=product.brand limit 1) as brand',//品牌
			'product.fee',
			'concat(ROUND(if(product.outside=2,(select (tax.xtax+tax.ztax)/(1-tax.xtax)*0.7 from tax where tax.id=product.ztax limit 1),if(product.outside=3,(select posttaxno.tax from posttaxno where posttaxno.id=product.postTaxNo),"0")),3)*100,"%") as tax',//税率
			'if(product.freetax=1,"是","否") as freetax',
			'(select dictionary.name from dictionary where dictionary.id=product.MeasurementUnit limit 1) as MeasurementUnit',//计量单位
			'if(product.status=1,"销售","下架") as status',//商品状态
		]);
		
		$product_publish_array = [];
		$i = 0;
		foreach ($bases as $base)
		{
			$product_first_info = false;
			$product_publish = $this->model('product_publish')->where('product_id=?',[$base['id']])->select();
			foreach ($product_publish as $publish)
			{
				if (!$product_first_info)
				{
					$product_publish_info = array_merge($base,array(
						'publish' => $this->model('publish')->where('id=?',[$publish['publish_id']])->scalar('name'),
						'sku' => $publish['sku'],
						'store' => $this->model('store')->where('id=?',[$publish['store']])->scalar('name'),
						'stock' => $publish['stock'],
					));
					$product_first_info = true;
				}
				else
				{
					$product_publish_info = array_merge(array_fill_keys(array_keys($base),''),array(
						'publish' => $this->model('publish')->where('id=?',[$publish['publish_id']])->scalar('name'),
						'sku' => $publish['sku'],
						'store' => $this->model('store')->where('id=?',[$publish['store']])->scalar('name'),
						'stock' => $publish['stock'],
					));
				}
				
				$product_publish_first_info = false;
				$product_publish_price = $this->model('product_publish_price')->where('product_id=? and publish_id=?',[$publish['product_id'],$publish['publish_id']])->select();
				foreach ($product_publish_price as $price)
				{
					if (!$product_publish_first_info)
					{
						$info = array_merge($product_publish_info,array(
							'num'=>$price['num'],
							'oldprice' => $price['oldprice'],
							'inprice' => $price['inprice'],
							'price' => $price['price'],
							'v1price' => $price['v1price'],
							'v2price' => $price['v2price'],
						));
						$product_publish_first_info = true;
					}
					else
					{
						$info = array_merge(array_fill_keys(array_keys($product_publish_info),''),array(
							'num'=>$price['num'],
							'oldprice' => $price['oldprice'],
							'inprice' => $price['inprice'],
							'price' => $price['price'],
							'v1price' => $price['v1price'],
							'v2price' => $price['v2price'],
						));
						$product_publish_first_info = true;
					}
					$info['nid'] = (++$i).'';
					$info['note'] = $this->model('product')->where('id=?',[$info['id']])->scalar('down_reason');
					$product_publish_array[] = $info;
				}
			}
		}
		$template = ROOT . '/extends/PHPExcel/product.xlsx';
		$this->model("admin_log")->insertlog($this->_aid, '导出商品信息成功', 1);
		$this->response($product_publish_array, $template, '商品数据' . date('Y-m-d H:i:s'));
	}

	/**
	 * 用户导出
	 */
	function user()
	{
		$adminHelper = new \application\helper\admin();
		if(!$adminHelper->checkPower(0, 'button','export_user'))
		{
			$this->response->setCode(302);
			$this->response->addHeader('Location','./index.php?c=html&a=nopower');
		}
		else
		{
			$userModel = $this->model('user');
			$close = $this->post('close',NULL);
			if ($close !== NULL)
			{
				$userModel->where('close=?',[$close]);
			}
			$id = $this->post('id', array());
			// 数据内容
			if (is_array($id) && ! empty($id))
			{
				$userModel->where('id in (?)', $id);
			}
			$y_profit_time_start = strtotime(date('Y-m-d', time() - 24 * 3600));
			$y_profit_time_end = $y_profit_time_start + 24 * 3600;
			$data = $this->model('user')->select([
				'user.id',
				'user.name',
				'user.telephone',
				'user.invit',
				'ifnull((select sum(money) from swift where source in (2,3,4,5,6,7) and uid=user.id and time > ' . $y_profit_time_start . ' and time < ' . $y_profit_time_end . '),0) as yesterday_profit', // 昨日收益
				'user.money',
				'ifnull((select sum(money) from swift where source in (2,3,4,5,6,7) and type=0 and uid=user.id),0) as profit', // 累计收益
				'ifnull((select sum(money) from swift where source in (2,3) and uid=user.id),0) as product', // 产品推广
				'ifnull((select sum(money) from swift where source in (5,6) and uid=user.id),0) as pintai', // 平台推广
				'ifnull((select sum(money) from swift where source in (4,7) and uid=user.id),0) as team', // 团队管理
				'ifnull((select sum(money) from drawal where uid=user.id and pass=0),0) as drawaling', // 提现中
				'ifnull((select sum(money) from drawal where uid=user.id and pass=1),0) as drawaled', // 已提现
				'replace(replace(replace(user.vip,1,"白金会员"),0,"普通用户"),2,"钻石会员")',
				'replace(replace(user.master,1,"是"),0,"否")',
				'(select name from user as user2 where user2.id=user.o_master) as o_master',
				'(select name from user as user3 where user3.id=user.oid) as oid',
				'user.wechat_no'
			]);
			$template = ROOT . '/extends/PHPExcel/user.xlsx';
			$this->model("admin_log")->insertlog($this->_aid, '导出用户信息成功', 1);
			$this->response($data, $template, '用户数据' . date('Y-m-d H:i:s'));
		}
	}
	
	/**
	 * 导出库存盘点文件
	 */
	function stock_manager()
	{
		$bcategory = $this->get('bcategory');
		if (!empty($bcategory))
		{
			$bcategory = explode(',', $bcategory);
		}
		
		$bc = current($bcategory);
		while(!empty($bc))
		{
			$new = $this->model('bcategory')->where('bc_id=?',[$bc])->select('id');
			if (!empty($new))
			{
				foreach ($new as $new_id)
				{
					$bcategory[] = $new_id['id'];
				}
			}
			$bc = next($bcategory);
		}
		
		if (!empty($bcategory))
		{
			$product_id_array = [];
			$result = $this->model('bcategory_product')->where('bc_id in (?)',$bcategory)->select('product_id');
			foreach ($result as $product_id)
			{
				$product_id_array[] = $product_id['product_id'];
				if (!empty($product_id_array))
				{
					$result = $this->model('product_publish')
					->table('product','left join','product.id=product_publish.product_id')
					->table('publish','left join','product.publish=publish.id')
					->table('store','left join','store.id=product_publish.store')
					->where('product.id in (?)',$product_id_array)
					->select([
						'product.id',
						'product_publish.sku',
						'product.name',
						'publish.name as publish',
						'store.name as store',
						'product.barcode',
						'(select dictionary.name from dictionary where dictionary.id=product.MeasurementUnit limit 1) as MeasurementUnit',
						'product_publish.stock',
					]);
					
					$template = ROOT . '/extends/PHPExcel/product_stock.xlsx';
					$this->response($result,$template,'库存盘点' . date('Y-m-d H:i:s'));
				}
			}
		}
	}
	
	/**
	 * 导出价格盘点文件
	 */
	function price_manager()
	{
		$bcategory = $this->get('bcategory');
		if (!empty($bcategory))
		{
			$bcategory = explode(',', $bcategory);
		}
	
		$bc = current($bcategory);
		while(!empty($bc))
		{
			$new = $this->model('bcategory')->where('bc_id=?',[$bc])->select('id');
			if (!empty($new))
			{
				foreach ($new as $new_id)
				{
					$bcategory[] = $new_id['id'];
				}
			}
			$bc = next($bcategory);
		}
	
		if (!empty($bcategory))
		{
			$product_id_array = [];
			$result = $this->model('bcategory_product')->where('bc_id in (?)',$bcategory)->select('product_id');
			foreach ($result as $product_id)
			{
				$product_id_array[] = $product_id['product_id'];
				if (!empty($product_id_array))
				{
					$result = $this->model('product_publish_price')
					->table('product_publish','left join','product_publish.product_id=product_publish_price.product_id and product_publish.publish_id=product_publish_price.publish_id')
					->table('product','left join','product.id=product_publish.product_id')
					->table('publish','left join','product.publish=publish.id')
					->table('store','left join','store.id=product_publish.store')
					->where('product.id in (?)',$product_id_array)
					->select([
						'product.id',
						'product_publish.sku',
						'product.name',
						'publish.name as publish',
						'store.name as store',
						'product.barcode',
						'product_publish_price.num',
						'product_publish_price.inprice',
						'product_publish_price.oldprice',
						'product_publish_price.price',
						'product_publish_price.v1price',
						'product_publish_price.v2price',
					]);
					
					$template = ROOT . '/extends/PHPExcel/product_price.xlsx';
					$this->response($result,$template,'价格盘点' . date('Y-m-d H:i:s'));
				}
			}
		}
	}

	private function response(array $data, $template, $filename, $start_line = 2)
	{
		$excel = new \application\helper\excel();
		$filepath = $excel->phpexcel($data, $template, $start_line);
		// excel文件下载
		header("Accept-Ranges:bytes");
		header("Content-type:application/vnd.ms-excel");
		header("Content-Disposition:attachment;filename=" . $filename . "." . pathinfo($template, PATHINFO_EXTENSION) . "");
		header("Pragma: no-cache");
		readfile($filepath);
		unlink($filepath);
	}
}
