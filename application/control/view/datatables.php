<?php
namespace application\control\view;

use system\core\view;
use application\message\json;

/**
 * @author jin12
 *
 */
class datatables extends view
{

	function __construct()
	{
		$this->_csrf_token_refresh = false;
		parent::__construct();
	}

	function taskorder()
	{
		$resultObj = new \stdClass();
		if ($this->post('customActionType') == 'group_action')
		{
			switch ($this->post('customActionName'))
			{
				default:
			}
		}
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('task_user')->datatables($this->post());
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('task_user')->count();
		return new json($resultObj);
	}
	
	/**
	 * 商品库存盘点
	 * @return \application\message\json
	 */
	function product_stock()
	{
		$resultObj = new \stdClass();
	
		$columns = $this->post('columns',array());
		$orders = $this->post('order',array());
		$ajaxData = $this->post('ajaxData',array());
		$keywords = $this->post('keywords',array(),'trim');
		$status = $this->post('status');
		$parameter = array();
		foreach ($columns as $index => $column)
		{
			if (! empty($column['name']) && $column['name']!='product_publish_price')
			{
				$parameter[] = $column['name'] . (empty($column['data']) ? '' : (' as ' . $column['data']));
				foreach ($orders as $order)
				{
					if ($order['column'] == $index)
					{
						$this->orderby($column['name'], $order['dir']);
					}
				}
			}
		}
	
		$resultObj->data = $this->model('product_publish')
		->table('product','left join','product.id=product_publish.product_id')
		->table('publish','left join','publish.id=product_publish.publish_id');
		foreach ($ajaxData as $key => $value)
		{
			$this->model('product_publish')->where($key.'=?',[$value]);
		}
		if (!empty($keywords))
		{
			$this->model('product_publish')->where('product.id=? or name like ?',[trim($keywords),'%'.trim($keywords).'%']);
		}
		if (!empty($status))
		{
			$status = explode(',', $status);
			foreach ($status as $stat)
			{
				list($name,$value) = explode(':', $stat);
				$this->model('product_publish')->where('product.'.$name.'=?',[$value]);
			}
		}
		$resultObj->data = $this->model('product_publish')->select($parameter);
	
		$resultObj->recordsFiltered = count($resultObj->data);
		$resultObj->recordsTotal = $this->model('product_publish')->count();
		if ($this->post('length') != - 1){
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));	
		}
		$resultObj->draw = $this->post('draw');
	
		return new json($resultObj);
	}
	
	/**
	 * 商品价格盘点
	 * @return \application\message\json
	 */
	function product_price()
	{
		$resultObj = new \stdClass();
		
		$columns = $this->post('columns',array());
		$orders = $this->post('order',array());
		$ajaxData = $this->post('ajaxData',array());
		$keywords = $this->post('keywords',array(),'trim');
		$status = $this->post('status');
		$parameter = array();
		foreach ($columns as $index => $column)
		{
			if (! empty($column['name']) && $column['name']!='product_publish_price')
			{
				$parameter[] = $column['name'] . (empty($column['data']) ? '' : (' as ' . $column['data']));
				foreach ($orders as $order)
				{
					if ($order['column'] == $index)
					{
						$this->orderby($column['name'], $order['dir']);
					}
				}
			}
		}
		
		$resultObj->data = $this->model('product_publish')
		->table('product','left join','product.id=product_publish.product_id')
		->table('publish','left join','publish.id=product_publish.publish_id');
		foreach ($ajaxData as $key => $value)
		{
			$this->model('product_publish')->where($key.'=?',[$value]);
		}
		if (!empty($keywords))
		{
			$this->model('product_publish')->where('product.id=? or name like ?',[trim($keywords),'%'.trim($keywords).'%']);
		}
		if (!empty($status))
		{
			$status = explode(',', $status);
			foreach ($status as $stat)
			{
				list($name,$value) = explode(':', $stat);
				$this->model('product_publish')->where('product.'.$name.'=?',[$value]);
			}
		}
		$resultObj->data = $this->model('product_publish')->select($parameter);
		
		$resultObj->recordsFiltered = count($resultObj->data);
		$resultObj->recordsTotal = $this->model('product_publish')->count();
		if ($this->post('length') != - 1){
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
			
			foreach ($resultObj->data as &$product)
			{
				$product['product_publish_price'] = $this->model('product_publish_price')->where('product_id=? and publish_id=?',[$product['product_id'],$product['publish_id']])->select();
			}
		}
		$resultObj->draw = $this->post('draw');
		
		return new json($resultObj);
	}

	function product()
	{
		if ($this->post('customActionType') == 'group_action')
		{
			$post_id = $this->post('id');
			if (is_array($post_id) && ! empty($post_id))
			{
				$adminHelper = new \application\helper\admin();
				foreach ($post_id as $id)
				{
					switch ($this->post('customActionName'))
					{
						case 'remove'://进入回收站
							if($adminHelper->checkPower(0, 'button','recycle_product'))
							{
								$this->model('product')
									->where('id=?', [
									$id
								])->limit(1)->update([
									'isdelete' => 1,
									'deletetime' => $_SERVER['REQUEST_TIME']
								]);
							}
						break;
						case 'examine_pass'://基础信息审核通过
							$this->model('product')
							->where('id=?',[$id])
							->limit(1)->update([
								'examine' => 1,
								'examine_time' => $_SERVER['REQUEST_TIME'],
							]);
						break;
						case 'clear_delete'://彻底删除
							if($adminHelper->checkPower(0, 'button','delete_product'))
							{
								$this->model('product')->where('id=?',[$id])->limit(1)->delete();
							}
						break;
						case 'sale'://上架
							//上架的时候要保证必须不在下架编辑状态
							if($adminHelper->checkPower(0, 'button','up_product'))
							{
								$this->model('product')->where('id=? and downStatus=?',[$id,0])->limit(1)->update('status',1);
							}
						break;
						case 'unshelf'://下架
							if($adminHelper->checkPower(0, 'button','down_product'))
							{
								$this->model('product')->where('id=?',[$id])->limit(1)->update('status',0);
							}
					}
				}
			}
		}
		
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('product')->datatables($this->post());
		
		$pk = array();
		foreach ($resultObj->data as $data)
		{
			$pk[] = $data['id'];
		}
		$resultObj->pk = $pk;
		
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		
		$resultObj->recordsTotal = $this->model('product')->where('product.isdelete=?', [0])
			->count();
		return new json($resultObj);
	}

	function recycle()
	{
		if ($this->post('customActionType') == 'group_action')
		{
			$post_id = $this->post('id');
			if (is_array($post_id) && ! empty($post_id))
			{
				foreach ($post_id as $id)
				{
					switch ($this->post('customActionName'))
					{
						case 'remove':
							$this->model('product')
								->where('id=?', [
								$id
							])
								->delete();
						case 'restore':
							$this->model('product')
								->where('id=?', [
								$id
							])
								->limit(1)
								->update('isdelete', 0);
						default:
					}
				}
			}
		}
		
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('product')
			->where('product.isdelete=?', [
			1
		])
			->datatables($this->post());
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		foreach ($resultObj->data as &$product)
		{
			// 商品分类
			$filter = [
				'pid' => $product['id'],
				'parameter' => 'category.name as category',
				'isdelete' => 0
			];
			$product['category'] = [];
			foreach ($this->model('category')->fetchAll($filter) as $category)
			{
				$product['category'][] = $category['category'];
			}
			
			// 商品价格
			$filter = [
				'pid' => $product['id'],
				'isdelete' => 0,
				'available' => 1,
				'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)'
			];
			$price_collection = $this->model('collection')->fetch($filter);
			if (! empty($price_collection))
			{
				if ($price_collection[0]['sum(stock)'] !== NULL)
				{
					$product['stock'] = $price_collection[0]['sum(stock)'];
				}
				if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)'] !== NULL)
				{
					$product['price'] = $price_collection[0]['min(price)'] . '~' . $price_collection[0]['max(price)'];
				}
				if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL)
				{
					$product['v1price'] = $price_collection[0]['min(v1price)'] . '~' . $price_collection[0]['max(v1price)'];
				}
				if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL)
				{
					$product['v2price'] = $price_collection[0]['min(v2price)'] . '~' . $price_collection[0]['max(v2price)'];
				}
			}
		}
		$resultObj->recordsTotal = $this->model('product')
			->where('product.isdelete=?', [
			1
		])
			->count();
		return new json($resultObj);
	}

	/**
	 * 用户列表 V2.0
	 * @return \application\message\json
	 */
	function user()
	{
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('user')->datatables($this->post());
		$pk = array();
		foreach ($resultObj->data as $data)
		{
			$pk[] = $data['id'];
		}
		$resultObj->pk = $pk;
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('user')->count();
		return new json($resultObj);
	}

	function team()
	{
		$resultObj = new \stdClass();
		if ($this->post('customActionType') == 'group_action')
		{
			switch ($this->post('customActionName'))
			{
			}
		}
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('user')->datatables($this->post());
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		
		$resultObj->recordsTotal = $this->model('user')->count();
		return new json($resultObj);
	}

	/**
	 * 渠道
	 */
	function source_user()
	{
		$resultObj = new \stdClass();
		if ($this->post('customActionType') == 'group_action')
		{
			switch ($this->post('customActionName'))
			{
			}
		}
		$resultObj->draw = $this->post('draw');
		
		$resultObj->data = $this->model('user')->source_datatables($this->post(), $this->session->id);
		
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('user')->source_count($this->session->id);
		return new json($resultObj);
	}

	/**
	 * 普通渠道
	 */
	function source_user2()
	{
		$resultObj = new \stdClass();
		if ($this->post('customActionType') == 'group_action')
		{
			switch ($this->post('customActionName'))
			{
			}
		}
		$resultObj->draw = $this->post('draw');
		
		$resultObj->data = $this->model('user')->source_datatables2($this->post(), $this->session->id);
		
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('user')->source_count2($this->session->id);
		return new json($resultObj);
	}

	/**
	 * 渠道金额统计
	 */
	function source_under()
	{
		$resultObj = new \stdClass();
		if ($this->post('customActionType') == 'group_action')
		{
			switch ($this->post('customActionName'))
			{
			}
		}
		$resultObj->draw = $this->post('draw');
		
		$resultObj->data = $this->model('user')->sourceunder_datatables($this->post(), $this->session->id);
		
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('user')->sourceunder_count($this->session->id);
		return new json($resultObj);
	}

	/**
	 * 优惠券
	 */
	function coupon()
	{
		$resultObj = new \stdClass();
		if ($this->post('customActionType') == 'group_action')
		{
			switch ($this->post('customActionName'))
			{
				case 'remove':
					foreach ($this->post('id') as $id)
					{
						$this->model('coupon')
							->where('id=?', [
							$id
						])
							->update([
							'isdelete' => 1,
							'deletetime' => $_SERVER['REQUEST_TIME']
						]);
					}
				default:
			}
		}
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('coupon')->datatables($this->post());
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('coupon')->count();
		return new json($resultObj);
	}

	/**
	 * 优惠券
	 */
	function couponno()
	{
		$resultObj = new \stdClass();
		if ($this->post('customActionType') == 'group_action')
		{
			switch ($this->post('customActionName'))
			{
				case 'remove':
					foreach ($this->post('id') as $id)
					{
						$this->model('couponno')
							->where('id=?', [
							$id
						])
							->limit(1)
							->update([
							'isdelete' => 1,
							'deletetime' => $_SERVER['REQUEST_TIME']
						]);
					}
				default:
			}
		}
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('couponno')->datatables($this->post());
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('couponno')->count();
		return new json($resultObj);
	}

	/**
	 * 订单相关的 V2.0
	 * @return \application\message\json
	 */
	function order()
	{
		$resultObj = new \stdClass();
		if ($this->post('customActionType') == 'group_action')
		{
			switch ($this->post('customActionName'))
			{
				case 'remove':
					$this->model('order')->where('orderno in (?)',$this->post('id',array()))->update([
						'isdelete' => 1,
						'deletetime' => $_SERVER['REQUEST_TIME'],
					]);
				default:
			}
		}
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('order')->datatables($this->post());
		
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('order')->count();
		return new json($resultObj);
	}

	/*
	 * 渠道订单表
	 */
	function source_order()
	{
		$resultObj = new \stdClass();
		if ($this->post('customActionType') == 'group_action')
		{
			switch ($this->post('customActionName'))
			{
				default:
			}
		}
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('order')->source_datatables($this->post(), $this->session->uid, $this->session->id);
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('order')->source_count($this->session->id, $this->session->uid);
		return new json($resultObj);
	}

	/*
	 * 普通渠道订单表
	 */
	function source_order2()
	{
		$resultObj = new \stdClass();
		if ($this->post('customActionType') == 'group_action')
		{
			switch ($this->post('customActionName'))
			{
				default:
			}
		}
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('order')->source_datatables2($this->post(), $this->session->uid, $this->session->id);
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('order')->source_count2($this->session->id, $this->session->uid);
		return new json($resultObj);
	}

	function feedback()
	{
		$resultObj = new \stdClass();
		if ($this->post('customActionType') == 'group_action')
		{
			switch ($this->post('customActionName'))
			{
				case 'remove':
					$post_id = $this->post('id');
					if (is_array($post_id) && ! empty($post_id))
					{
						foreach ($post_id as $id)
						{
							$this->model('feedback')
								->where('id=?', [
								$id
							])
								->limit(1)
								->update([
								'isdelete' => 1,
								'deletetime' => $_SERVER['REQUEST_TIME']
							]);
						}
					}
				default:
			}
		}
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('feedback')->datatables($this->post());
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('feedback')->count();
		return new json($resultObj);
	}

	function package()
	{
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('order_package')->datatables($this->post());
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		
		foreach ($resultObj->data as &$package)
		{
			$product = $this->model('order_product')
				->table('product', 'left join', 'product.id=order_product.pid')
				->where('order_product.package_id=?', [
				$package['id']
			])
				->select([
				'product.name',
				'order_product.content',
				'order_product.num'
			]);
			$package['product'] = $product;
		}
		$resultObj->recordsTotal = $this->model('order_package')->count();
		return new json($resultObj);
	}
	
	/**
	 * 提现列表 V2.0
	 */
	function drawal()
	{
		$parameter = array();
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		
		$columns = $this->post('columns',array());
		$orders = $this->post('order',array());
		$ajaxData = $this->post('ajaxData',array());
		$keywords = $this->post('keywords','');
		$pk = $this->post('pk',array());
		
		foreach ($columns as $column)
		{
			if (! empty($column['name']))
			{
				$parameter[] = $column['name'] . (empty($column['data']) ? '' : (' as ' . $column['data']));
			}
		}
		
		foreach ($orders as $order)
		{
			$this->model('drawal')->orderby($columns[$order['column']]['name'],$order['dir']);
		}
		
		if (!empty($pk))
		{
			foreach ($pk as $p)
			{
				$this->model('drawal')->where($p['key'].'=?',[$p['value']]);
			}
		}
		
		foreach ($ajaxData as $key => $value)
		{
			$this->model('drawal')->where($key.'=?',[$value]);
		}
		
		if (!empty($keywords))
		{
			$this->model('drawal')->where(' uid in (select user.id from user where user.name like ? or user.telephone like ?)',['%'.trim($keywords).'%','%'.trim($keywords).'%']);
		}
		$this->model('drawal')->table('bankcard','left join','bankcard.id=drawal.bankcard');
		$resultObj->data = $this->model('drawal')->select($parameter);
		
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start',0), $this->post('length',10));
		}
		$resultObj->recordsTotal = $this->model('drawal')->count();
		return new json($resultObj);
	}

	/**
	 * VIP订单V2.0
	 * @return \application\message\json
	 */
	function viporder()
	{
		$parameter = array();
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
	
		$columns = $this->post('columns',array());
		$orders = $this->post('order',array());
		$ajaxData = $this->post('ajaxData',array());
		$keywords = $this->post('keywords','');
	
		foreach ($columns as $column)
		{
			if (! empty($column['name']))
			{
				$parameter[] = $column['name'] . (empty($column['data']) ? '' : (' as ' . $column['data']));
			}
		}
	
		foreach ($orders as $order)
		{
			$this->model('vip_order')->orderby($columns[$order['column']]['name'],$order['dir']);
		}
	
		foreach ($ajaxData as $key => $value)
		{
			$this->model('vip_order')->where($key.'=?',[$value]);
		}
		
		if (!empty($keywords))
		{
			$this->model('vip_order')->where(' uid in (select user.id from user where user.name like ? or user.telephone like ?)',['%'.trim($keywords).'%','%'.trim($keywords).'%']);
		}
		
		$resultObj->data = $this->model('vip_order')->select($parameter);
		
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start',0), $this->post('length',10));
		}
		$resultObj->recordsTotal = $this->model('vip_order')->count();
		return new json($resultObj);
	}

	function source_viporder()
	{
		
		// 获取当前渠道的用户id
		
		// 生成json
		
		// 筛选
		
		// 获取当前渠道的用户id
		
		// 生成json
		
		// 筛选
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('vip_order')->vipdatatables($this->post(), $this->session->id);
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('vip_order')->vipcount($this->session->id);
		foreach ($resultObj->data as &$data)
		{
			
			$data['oidname'] = $this->model("user")
				->where("id=(select oid from user where id=?)", [
				$data['uid']
			])
				->find([
				'user.name'
			]);
			
			$data['oidname'] = $data['oidname']['name'];
		}
		return new json($resultObj);
	}

	function refund()
	{
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('refund')->datatables($this->post());
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('refund')->count();
		return new json($resultObj);
	}

	function brand()
	{
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('brand')->datatables($this->post());
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('brand')->count();
		return new json($resultObj);
	}
	
	/**
	 * 学生列表
	 * @return \application\message\json
	 */
	function student()
	{
		$parameter = array();
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
	
		$columns = $this->post('columns',array());
		$orders = $this->post('order',array());
		$ajaxData = $this->post('ajaxData',array());
		$keywords = $this->post('keywords','');
	
		foreach ($columns as $column)
		{
			if (! empty($column['name']))
			{
				$parameter[] = $column['name'] . (empty($column['data']) ? '' : (' as ' . $column['data']));
			}
		}
	
		foreach ($orders as $order)
		{
			$this->model('student_info')->orderby($columns[$order['column']]['name'],$order['dir']);
		}
	
		foreach ($ajaxData as $key => $value)
		{
			$this->model('student_info')->where($key.'=?',[$value]);
		}
		
		if (!empty($keywords))
		{
			$this->model('student_info')->where('user.name like ? or student_info.name like ? or user.telephone like ?',['%'.trim($keywords).'%','%'.trim($keywords).'%','%'.trim($keywords).'%']);
		}
		
		$this->model('student_info')->table('user','left join','user.id=student_info.uid');
		
		$resultObj->data = $this->model('student_info')->select($parameter);
		
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start',0), $this->post('length',10));
		}
		$resultObj->recordsTotal = $this->model('student_info')->count();
		return new json($resultObj);
	}
	
	/**
	 * 用户的资金流水导出 V2.0
	 */
	function swift()
	{
		$parameter = array();
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		
		$columns = $this->post('columns',array());
		$orders = $this->post('order',array());
		$ajaxData = $this->post('ajaxData',array());
		
		foreach ($columns as $column)
		{
			if (! empty($column['name']))
			{
				$parameter[] = $column['name'] . (empty($column['data']) ? '' : (' as ' . $column['data']));
			}
		}
		
		foreach ($orders as $order)
		{
			$this->model('swift')->orderby($columns[$order['column']]['name'],$order['dir']);
		}
		
		foreach ($ajaxData as $key => $value)
		{
			$this->model('swift')->where($key.'=?',[$value]);
		}
		$resultObj->data = $this->model('swift')->select($parameter);
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start',0), $this->post('length',10));
		}
		$resultObj->recordsTotal = $this->model('swift')->count();
		return new json($resultObj);
	}
	
	/**
	 * 团购任务 v2.0
	 */
	function task()
	{
		$parameter = array();
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		
		if ($this->post('customActionType') == 'group_action')
		{
			switch ($this->post('customActionName'))
			{
				case 'remove':
					$this->model('task')->where('id in (?)',$this->post('id',array()))->update([
						'isdelete'=>1,
						'deletetime' =>$_SERVER['REQUEST_TIME'],
					]);
			}
		}
		
		$columns = $this->post('columns',array());
		$orders = $this->post('order',array());
		$ajaxData = $this->post('ajaxData',array());
		
		foreach ($columns as $column)
		{
			if (! empty($column['name']))
			{
				$parameter[] = $column['name'] . (empty($column['data']) ? '' : (' as ' . $column['data']));
			}
		}
		
		foreach ($orders as $order)
		{
			$this->model('task')->orderby($columns[$order['column']]['name'],$order['dir']);
		}
		
		foreach ($ajaxData as $key => $value)
		{
			$this->model('task')->where($key.'=?',[$value]);
		}
		$resultObj->data = $this->model('task')->table('product','left join','product.id=task.pid')
		->select($parameter);
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start',0), $this->post('length',10));
		}
		$resultObj->recordsTotal = $this->model('task')->where('isdelete=?',[0])->count();
		return new json($resultObj);
	}
	
	/**
	 * 角色列表V2.0
	 */
	function role()
	{
		$parameter = array();
	
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
	
		$columns = $this->post('columns',array());
		$orders = $this->post('order',array());
		$ajaxData = $this->post('ajaxData',array());
		$keywords = $this->post('keywords','');
		if (!empty($keywords))
		{
			$this->model('role')->where('name like ?',['%'.$keywords.'%']);
		}
	
		foreach ($columns as $column)
		{
			if (! empty($column['name']))
			{
				$parameter[] = $column['name'] . (empty($column['data']) ? '' : (' as ' . $column['data']));
			}
		}
	
		foreach ($orders as $order)
		{
			$this->model('role')->orderby($columns[$order['column']]['name'],$order['dir']);
		}
	
		foreach ($ajaxData as $key => $value)
		{
			$this->model('role')->where($key.'=?',[$value]);
		}
	
		$resultObj->data = $this->model('role')->select($parameter);
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start',0), $this->post('length',10));
		}
		$resultObj->recordsTotal = $this->model('role')->count();
		return new json($resultObj);
	}
	
	/**
	 * 管理员列表V2.0
	 */
	function admin()
	{
		$parameter = array();
		
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		
		$columns = $this->post('columns',array());
		$orders = $this->post('order',array());
		$ajaxData = $this->post('ajaxData',array());
		$keywords = $this->post('keywords','');
		if (!empty($keywords))
		{
			$this->model('admin')->where('realname like ? or username like ? or telephone like ?',['%'.$keywords.'%','%'.$keywords.'%','%'.$keywords.'%']);
		}
		
		foreach ($columns as $column)
		{
			if (! empty($column['name']))
			{
				$parameter[] = $column['name'] . (empty($column['data']) ? '' : (' as ' . $column['data']));
			}
		}
		
		foreach ($orders as $order)
		{
			$this->model('admin')->orderby($columns[$order['column']]['name'],$order['dir']);
		}
		
		foreach ($ajaxData as $key => $value)
		{
			$this->model('admin')->where($key.'=?',[$value]);
		}
		
		$resultObj->data = $this->model('admin')->select($parameter);
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start',0), $this->post('length',10));
		}
		$resultObj->recordsTotal = $this->model('admin')->count();
		return new json($resultObj);
	}
	
	/**
	 * 没有参加团购商品的商品 v2.0
	 */
	function product_untask()
	{
		$parameter = array();
	
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
	
		$columns = $this->post('columns',array());
		$orders = $this->post('order',array());
		$ajaxData = $this->post('ajaxData',array());
		$keywords = $this->post('keywords','');
		if (!empty($keywords))
		{
			$this->model('product')->where('id=? or name like ?',[$keywords,'%'.$keywords.'%']);
		}
		$brand = $this->post('brand','');
		if (!empty($brand))
		{
			$this->model('product')->where('brand=?',[$brand]);
		}
		$category = $this->post('category',array());
		if (!empty($category))
		{
			$t = array_pop($category);
			$temp_category = array($t);
			$last_category = array($t);
			while (!empty($last_category))
			{
				$c = array_shift($last_category);
				$new_category = $this->model('category')->where('bc_id=?',[$c])->select('id');
				foreach ($new_category as $nc)
				{
					$temp_category[] = $nc['id'];
					$last_category[] = $nc['id'];
				}
			}
			$product_id_array = [];
			$product_id = $this->model('bcategory_product')->where('bc_id in (?)',$temp_category)->select('product_id');
			foreach ($product_id as $ids)
			{
				$product_id_array[] = $ids['product_id'];
			}
			if (empty($product_id_array))
			{
				//为空的话故意选择没有任何结果集的商品
				$this->model('product')->where('id<?',[0]);
			}
			else
			{
				$this->model('product')->where('id in (?)',$product_id_array);
			}
		}
		foreach ($columns as $column)
		{
			if (! empty($column['name']))
			{
				$parameter[] = $column['name'] . (empty($column['data']) ? '' : (' as ' . $column['data']));
			}
		}
		
		foreach ($orders as $order)
		{
			$this->model('product')->orderby($columns[$order['column']]['name'],$order['dir']);
		}
		foreach ($ajaxData as $key => $value)
		{
			$this->model('product')->where($key.'=?',[$value]);
		}
	
		$this->model('product')->where('product.id not in (select pid from task where isdelete=0)');
	
		$resultObj->data = $this->model('product')->select($parameter);
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start',0), $this->post('length',10));
		}
		$resultObj->recordsTotal = $this->model('product')->where('id not in (select pid from task where isdelete=0)')->count();
		return new json($resultObj);
	}
	
	/**
	 * 不在首页商品中的商品 v2.0
	 */
	function product_untop()
	{
		$parameter = array();
		
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		
		$columns = $this->post('columns',array());
		$orders = $this->post('order',array());
		$ajaxData = $this->post('ajaxData',array());
		$keywords = $this->post('keywords','');
		if (!empty($keywords))
		{
			$this->model('product')->where('id=? or name like ?',[$keywords,'%'.$keywords.'%']);
		}
		$brand = $this->post('brand','');
		if (!empty($brand))
		{
			$this->model('product')->where('brand=?',[$brand]);
		}
		$category = $this->post('category',array());
		if (!empty($category))
		{
			$t = array_pop($category);
			$temp_category = array($t);
			$last_category = array($t);
			while (!empty($last_category))
			{
				$c = array_shift($last_category);
				$new_category = $this->model('category')->where('bc_id=?',[$c])->select('id');
				foreach ($new_category as $nc)
				{
					$temp_category[] = $nc['id'];
					$last_category[] = $nc['id'];
				}
			}
			$product_id_array = [];
			$product_id = $this->model('bcategory_product')->where('bc_id in (?)',$temp_category)->select('product_id');
			foreach ($product_id as $ids)
			{
				$product_id_array[] = $ids['product_id'];
			}
			if (empty($product_id_array))
			{
				//为空的话故意选择没有任何结果集的商品
				$this->model('product')->where('id<?',[0]);
			}
			else
			{
				$this->model('product')->where('id in (?)',$product_id_array);
			}
		}
		foreach ($columns as $index => $column)
		{
			if (! empty($column['name']))
			{
				$parameter[] = $column['name'] . (empty($column['data']) ? '' : (' as ' . $column['data']));
				foreach ($orders as $order)
				{
					if ($order['column'] == $index)
					{
						$this->model('product')->orderby($column['name'], $order['dir']);
					}
				}
			}
		}
		foreach ($ajaxData as $key => $value)
		{
			$this->model('product')->where($key.'=?',[$value]);
		}
		
		$this->model('product')->where('product.id not in (select pid as id from product_top)');
		
		$resultObj->data = $this->model('product')->select($parameter);
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start',0), $this->post('length',10));
		}
		$resultObj->recordsTotal = $this->model('product')->where('id not in (select pid as id from product_top)')->count();
		return new json($resultObj);
	}
	
	/**
	 * 首页商品列表 v2.0
	 */
	function product_top()
	{
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		
		if ($this->post('customActionType') == 'group_action')
		{
			switch ($this->post('customActionName'))
			{
				case 'remove':
					$this->model('product_top')->where('pid in (?)',$this->post('id',array()))->delete();
			}
		}
		
		$columns = $this->post('columns',array());
		$orders = $this->post('order',array());
		$ajaxData = $this->post('ajaxData',array());
		
		$parameter = array();
		foreach ($columns as $column)
		{
			if (! empty($column['name']))
			{
				$parameter[] = $column['name'] . (empty($column['data']) ? '' : (' as ' . $column['data']));
			}
		}
		foreach ($orders as $order)
		{
			$this->model('product_top')->orderby($columns[$order['column']]['name'],$order['dir']);
		}
		
		$this->model('product_top')->table('product','left join','product.id=product_top.pid');
		
		foreach ($ajaxData as $key => $value)
		{
			$this->model('product_top')->where($key.'=?',[$value]);
		}
		
		$resultObj->data = $this->model('product_top')->select($parameter);
		
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start',0), $this->post('length',10));
		}
		$resultObj->recordsTotal = $this->model('product_top')->count();
		return new json($resultObj);
	}

	function product_notice()
	{
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('product_notice')->datatables($this->post());
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('product_notice')->count();
		return new json($resultObj);
	}

	function product_notice_template()
	{
		$resultObj = new \stdClass();
		$resultObj->draw = $this->post('draw');
		$resultObj->data = $this->model('product_notice_template')->datatables($this->post());
		$resultObj->recordsFiltered = count($resultObj->data);
		if ($this->post('length') != - 1)
		{
			$resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
		}
		$resultObj->recordsTotal = $this->model('product_notice_template')->count();
		return new json($resultObj);
    }
}
