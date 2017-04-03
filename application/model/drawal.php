<?php
namespace application\model;
use system\core\model;
class drawalModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function datatables($post)
	{
		$this->table('user','left join','user.id=drawal.uid');
		$this->table('bankcard','left join','bankcard.id=drawal.bankcard');
		$this->table('province','left join','province.id=bankcard.province');
		$this->table('city','left join','bankcard.city=city.id');
		
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
			if (!empty($post['uid']))
			{
				$this->where('drawal.uid=?',[$post['uid']]);
			}
			if (!empty($post['id']))
			{
				$this->where('drawal.id=?',[$post['id']]);
			}
			if(!empty($post['money_from']))
			{
				$this->where('drawal.money >= ?',[$post['money_from']]);
			}
			if (!empty($post['money_to']))
			{
				$this->where('drawal.money <= ?',[$post['money_to']]);
			}
			if (!empty($post['createtime_from']))
			{
				$this->where('drawal.createtime >= ?',[strtotime($post['createtime_from'])]);
			}
			if (!empty($post['createtime_to']))
			{
				$this->where('drawal.createtime <= ?',[strtotime($post['createtime_to'])]);
			}
			if ($post['pass'] != '')
			{
				$this->where('drawal.pass=?',[$post['pass']]);
			}
			if ($post['bank'] != '')
			{
				$this->where('bankcard.type=?',[$post['bank']]);
			}
		}
		return $this->select($parameter);
	}
	
	function count()
	{
		$result = $this->find('count(*)');
		return $result['count(*)'];
	}
}