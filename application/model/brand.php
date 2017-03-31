<?php
namespace application\model;

use system\core\model;

class brandModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function datatables($post)
	{
		$this->table('upload','left join','upload.id=brand.logo');
		$this->table('country','left join','country.id=brand.origin');
		
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
		if (isset($post['keywords']) && !empty($post['keywords']))
		{
			$this->where('name_cn like ? or name_en like ?',['%'.$post['keywords'].'%','%'.$post['keywords'].'%']);
		}
		return $this->select($parameter);
	}
	
	function count()
	{
		return $this->scalar('count(*)');
	}
}