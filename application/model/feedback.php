<?php
namespace application\model;
use system\core\model;
class feedbackModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function datatables($post)
	{
		$this->table('user','left join','user.id=feedback.uid');
		
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
		return $this->where('isdelete=?',[0])->select($parameter);
	}
	
	function count()
	{
		$result = $this->find('count(*)');
		return isset($result['count(*)'])&&!empty($result['count(*)'])?$result['count(*)']:0;
	}
}