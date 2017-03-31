<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;

class category extends ajax
{

	private $_aid = NULL;
	
	function setRecursive()
	{
		$category = $this->post('category',array());
		if (empty($category['name']))
		{
			return new json(json::PARAMETER_ERROR,'一级分类的名称不能为空');
		}
		
		$this->model('category')->transaction();
		if (empty($category['id'])) 
		{
			if($this->model('category')->insert([
				'name' => $category['name'],
				'logo' => empty($category['logo'])?NULL:$category['logo'],
				'cid'=>NULL,
				'sort' => $this->model('category')->count()+1,
				'description' => isset($category['description']) && !empty($category['description'])?$category['description']:'',
				'isdelete' => 0,
				'deletetime' => 0,
				'createtime' => $_SERVER['REQUEST_TIME'],
				'modifytime' => $_SERVER['REQUEST_TIME'],
			]))
			{
				$category1_id = $this->model('category')->lastInsertId();
			}
			else 
			{
				$this->model('category')->rollback();
				return new json(json::PARAMETER_ERROR,'系统繁忙');
			}
		}
		else
		{
			if(!$this->model('category')->where('id=?',[$category['id']])->limit(1)->update([
				'name'=>$category['name'],
				'logo'=>empty($category['logo'])?NULL:$category['logo'],
				'cid' => NULL,
				'modifytime' => $_SERVER['REQUEST_TIME'],
				'description' => isset($category['description']) && !empty($category['description'])?$category['description']:'',
			]))
			{
				$this->model('category')->rollback();
				return new json(json::PARAMETER_ERROR,'系统繁忙');
			}
			else
			{
				$category1_id = $category['id'];
			}
		}
		
		if (!empty($category['child']))
		{
			foreach ($category['child'] as $category2)
			{
				if (empty($category2['name']))
				{
					return new json(json::PARAMETER_ERROR,'二级分类名称不能为空');
				}
				if (empty(intval($category2['id'])))
				{
					if(!$this->model('category')->insert([
						'name' => $category2['name'],
						'logo' => NULL,
						'cid'=> $category1_id,
						'sort' => $this->model('category')->count()+1,
						'description' => isset($category2['description']) && !empty($category2['description'])?$category2['description']:'',
						'isdelete' => 0,
						'deletetime' => 0,
						'createtime' => $_SERVER['REQUEST_TIME'],
						'modifytime' => $_SERVER['REQUEST_TIME'],
					]))
					{
						$this->model('category')->rollback();
						return new json(json::PARAMETER_ERROR,'系统繁忙');
					}
					else
					{
						$category2_id = $this->model('category')->lastInsertId();
					}
				}
				else
				{
					if(!$this->model('category')->where('id=?',[$category2['id']])->limit(1)->update([
						'name'=>$category2['name'],
						'logo'=>NULL,
						'cid' => $category1_id,
						'modifytime' => $_SERVER['REQUEST_TIME'],
					]))
					{
						$this->model('category')->rollback();
						return new json(json::PARAMETER_ERROR,'系统繁忙');
					}
					else
					{
						$category2_id = $category2['id'];
					}
				}
				
				if(!empty($category2['child']))
				{
					foreach ($category2['child'] as $category3_id)
					{
						$this->model('category')->where('id=?',[$category3_id])->limit(1)->update([
							'isdelete'=>0,
							'cid'=>$category2_id
						]);
					}
				}
				
			}
		}
		
		$this->model('category')->commit();
		$category['id'] = $category1_id;
		$category['logo'] = $this->model('upload')->where('id=?',[$category['logo']])->scalar('path');
		return new json(json::OK,NULL,$category);
	}
	
	function find()
	{
		$id = $this->post('id',NULL,'intval');
		if (empty($id))
		{
			return new json(json::PARAMETER_ERROR);
		}
		$result = $this->model('category')->where('id=?',[$id])->find();
		if (!empty($result))
		{
			$cid = $result['cid'];
			$current = &$result;
			while (!empty($cid))
			{
				$parent = $this->model('category')->where('id=?',[$cid])->find();
				$cid = $parent['cid'];
				$current['parent'] = $parent;
				$current = &$current['parent'];
			}
			
			$result['logourl'] = $this->model('upload')->where('id=?',[$result['logo']])->scalar('path');	
			$bcategory = [];
			$temp = $this->model('category_bcategory')->where('category_id=?',[$id])->select('bcategory_id');
			foreach ($temp as $t)
			{
				$a = [];
				$b = $t['bcategory_id'];
				
				do{
					$bc = $this->model('bcategory')->where('id=?',[$b])->find('id,name,bc_id');
					if (!empty($bc))
					{
						array_unshift($a,array(
							'id' => $bc['id'],
							'name' => $bc['name'],
						));
					}
					$b = $bc['bc_id'];
				}while (!empty($b));
				$bcategory[] = $a;
			}
			$result['bcategory'] = $bcategory;
			return new json(json::OK,NULL,$result);
		}
		return new json(json::PARAMETER_ERROR);
	}

	/**
	 * 分类列表
	 */
	function lists()
	{
		$cid = $this->get('cid', NULL, 'intval');
		if (empty($cid))
		{
			$this->model('category')->where('cid is null');
		}
		else
		{
			$this->model('category')->where('cid=?', [
				$cid
			]);
		}
		$result = $this->model('category')
			->table('upload', 'left join', 'upload.id=category.logo')
			->where('isdelete=?', [
			0
		])
			->orderby('sort', 'asc')
			->select([
			'category.id',
			'upload.path as logo',
			'category.name',
			'upload.id as logoid',
		]);
		return new json(json::OK, NULL, $result);
	}

	/**
	 * 设置排序
	 */
	function sort()
	{
		$sort = $this->post('sort',array());
		if (is_array($sort) && !empty($sort))
		{
			foreach ($sort as $index=>$id)
			{
				$this->model('category')->where('id=?',[$id])->limit(1)->update('sort',$index+1);
			}
		}
		return new json(json::OK);
	}

	function save()
	{
		$id = $this->post('id',0,'intval');
		if (empty($id))
		{
			return new json(json::PARAMETER_ERROR);
		}
		$data = [];
		if ($this->post('name') !== NULL)
			$data['name'] = $this->post('name', '');
		if ($this->post('description') !== NULL)
			$data['description'] = $this->post('description', '');
		if ($this->post('sort') !== NULL)
			$data['sort'] = $this->post('sort', 1);
		if ($this->post('logo') !== NULL)
		{
			$data['logo'] = $this->post('logo',NULL);
			if (empty($data['logo']))
			{
				$data['logo'] = NULL;
			}
		}
		
		if (! empty($this->post('cid')))
		{
			$data['cid'] = $this->post('cid');
		}
		else
		{
			$data['cid'] = NULL;
		}
		
		$bcategory = $this->post('bcategory',array());
		
		$data['modifytime'] = $_SERVER['REQUEST_TIME'];
		$this->model('category')->transaction();
		if ($this->model('category')->where('id=?', [$id])->update($data))
		{
			$this->model('category_bcategory')->where('category_id=?',[$id])->delete();
			foreach ($bcategory as $bc)
			{
				if (!empty($bc))
				{
					$bcate = end(explode(',', $bc));
					if(!$this->model('category_bcategory')->insert([
						'category_id' => $id,
						'bcategory_id' => $bcate,
					]))
					{
						$this->model('category')->rollback();
						return new json(json::PARAMETER_ERROR,'不允许关联相同分类');
					}
				}
			}
			$this->model("admin_log")->insertlog($this->_aid, '保存分类信息成功', 1);
			$this->model('category')->commit();
			return new json(json::OK);
		}
		$this->model('category')->rollback();
		var_dump($data);
		return new json(json::PARAMETER_ERROR);
	}

	function remove()
	{
		$id = $this->post('id', 0, 'intval');
		if (empty($id))
		{
			return new json(json::PARAMETER_ERROR);
		}
		if ($this->model('category')
			->where('id=?', [
			$id
		])->update([
			'isdelete' => 1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
			$this->model("admin_log")->insertlog($this->_aid, '删除分类信息成功', 1);
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}

	function create()
	{
		$name = $this->post('name', '','trim');
		if (empty($name))
		{
			return new json(json::PARAMETER_ERROR,'分类名称不能为空');
		}
		$description = $this->post('description', '');
		$logo = $this->post('logo',NULL);
		$cid = $this->post('cid',NULL);
		if (empty($cid))
		{
			$cid = NULL;
		}
		if (empty($logo))
		{
			$logo = NULL;
		}
		
		$data = [
			'name' => $name,
			'logo' => $logo,
			'sort' => $this->model('category')->count()+1,
			'description' => $description,
			'isdelete' => $this->post('isdelete',0),
			'deletetime' => 0,
			'createtime' => $_SERVER['REQUEST_TIME'],
			'modifytime' => $_SERVER['REQUEST_TIME'],
			'cid' => $cid
		];
		
		$this->model('category')->transaction();
		if ($this->model('category')->insert($data))
		{
			$id = $this->model('category')->lastInsertId();
			$data['id'] = $id;
			$data['logourl'] = $this->model('upload')->where('id=?',[$logo])->scalar('path');
			$bcategory = $this->post('bcategory',array());
			foreach ($bcategory as $bc)
			{
				if (!empty($bc))
				{
					$bcate = end(explode(',', $bc));
					if(!$this->model('category_bcategory')->insert([
						'category_id' => $id,
						'bcategory_id' => $bcate,
					]))
					{
						$this->model('category')->rollback();
						return new json(json::PARAMETER_ERROR,'不允许关联相同分类');
					}
				}
			}
			$this->model("admin_log")->insertlog($this->_aid, '新增分类信息成功', 1);
			$this->model('category')->commit();
			return new json(json::OK, NULL, $data);
		}
		$this->model('category')->rollback();
		return new json(json::PARAMETER_ERROR);
	}

	function __access()
	{
		$adminHelper = new admin();
		$this->_aid = $adminHelper->getAdminId();
		return array(
			array(
				'deny',
				'actions' => [
					'create',
					'remove',
					'sort',
					'save',
					'setRecursive',
				],
				'message' => new json(json::NOT_LOGIN),
				'express' => empty($this->_aid),
				'redict' => './index.php?c=admin&a=login',
    		),
    	);
    }
}