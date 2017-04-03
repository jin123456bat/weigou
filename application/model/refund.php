<?php
namespace application\model;

use system\core\model;

class refundModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function datatables($post)
	{
		$this->table('order_product','left join','refund.order_product_id = order_product.id');
		$this->table('product','left join','product.id=order_product.pid');
		
		$parameter = [];
		foreach ($post['columns'] as $index => $columns) {
			if (!empty($columns['name'])) {
				$parameter[] = $columns['name'] . (empty($columns['data']) ? '' : (' as ' . $columns['data']));
				foreach ($post['order'] as $order) {
					if ($order['column'] == $index) {
						$this->orderby($columns['name'], $order['dir']);
					}
				}
			}
		}
		if (isset($post['action']) && $post['action'] === 'filter') {
			if (!empty($post['refundno']))
			{
				$this->where('refund.refundno like ?',['%'.$post['refundno'].'%']);
			}
			if (!empty($post['orderno']))
			{
				$this->where('refund.orderno like ?',['%'.$post['orderno'].'%']);
			}
			if ($post['status'] != '')
			{
				$this->where('refund.status=?',[$post['status']]);
			}
			if (!empty($post['createtime_from']))
			{
				$this->where('refund.createtime >= ?',[strtotime($post['createtime_from'])]);
			}
			if (!empty($post['createtime_to']))
			{
				$this->where('refund.createtime <= ?',[strtotime($post['createtime_to'])]);
			}
		}
		return $this->select($parameter);
	}
	
	function count()
	{
		$result = $this->find('count(*)');
		foreach($result as $value)
		{
			return $value;
		}
		return NULL;
	}
}