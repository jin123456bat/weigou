<?php
namespace application\model;
use system\core\model;
class order_packageModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function datatables($post)
	{
		$this->table('ship','left join','ship.code=order_package.ship_type');
		
		$parameter = [];
		foreach ($post['columns'] as $index => $columns)
		{
			if (!empty($columns['name']))
			{
				$parameter[] = $columns['name'].(empty($columns['data'])?'':(' as '.$columns['data']));
				foreach ($post['order'] as $order)
				{
					if ($order['column'] == $index)
					{
						$this->orderby($columns['name'],$order['dir']);
					}
				}
			}
		}
		if (isset($post['action']) && $post['action'] === 'filter')
		{
			
		}
		return $this->where('order_package.orderno=?',[$post['orderno']])->select($parameter);
	}
	
	
	function count()
	{
		$result = $this->find('count(*)');
		return $result['count(*)']?$result['count(*)']:NULL;
	}
}