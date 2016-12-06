<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;
use application\helper as helper;
use application\helper\admin;
use application\helper\productSearchEngine;
use application\model\roleModel;

class product extends ajax
{
	/**
	 * 添加商品
	 */
	function create()
	{
		$admin = $this->session->id;
		$this->model('product')->transaction();
		$productHelper = new helper\product();
		
		$product = $productHelper->createProductData($this->post());
		
		/* if (empty($product['store']))
		{
			$this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（请选择发货仓库）');
			return new json(json::PARAMETER_ERROR, '请选择发货仓库');
		} */
		if ($product['outside'] == 2)
		{
			if (empty($product['ztax']))
			{
				return new json(json::PARAMETER_ERROR, '请填写综合税种');
			}
		}
		if ($product['outside'] == 3)
		{
			if (empty($product['postTaxNo']))
			{
				return new json(json::PARAMETER_ERROR, '请选择行邮税号');
			}
		}
		/* if ($product['status'] == 1)
		{
			if (floatval($product['inprice']) == 0)
			{
				return new json(json::PARAMETER_ERROR, '进价不能为0');
			}
		} */
		
		if (empty($product['barcode']))
		{
			return new json(json::PARAMETER_ERROR, '条形码不能为空');
		}
		if (empty(intval($product['MeasurementUnit'])))
		{
			return new json(json::PARAMETER_ERROR,'请填写计量单位');
		}
		/* if (intval($product['selled']) <= 0)
		{
			$this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（售卖数不能为空）');
			return new json(json::PARAMETER_ERROR, '售卖数不能为空');
		} */
		
		if ($this->model('product')
			->where('barcode=? and isdelete=?', [
			$product['barcode'],
			0
		])
			->find())
		{
			return new json(json::PARAMETER_ERROR, '存在相同条形码的商品');
		}
		
		if ($this->model('product')->insert($product))
		{
			$product_id = $this->model('product')->lastInsertId();
			if (is_array($this->post('category')) && ! empty($this->post('category')))
			{
				foreach ($this->post('category') as $category)
				{
					if (! $this->model('category_product')->insert([
						'cid' => $category,
						'pid' => $product_id,
						'createtime' => $_SERVER['REQUEST_TIME'],
						'deletetime' => 0,
						'isdelete' => 0
					]))
					{
						$this->model('product')->rollback();
						return new json(json::PARAMETER_ERROR, '分配分类错误');
					}
				}
			}
			
			$hasListImage = false;
			if (is_array($this->post('image')) && ! empty($this->post('image')))
			{
				
				foreach ($this->post('image') as $image)
				{
					if ($image['position'] == 1)
					{
						$hasListImage = true;
					}
					if (! $this->model('product_img')->insert([
						'pid' => $product_id,
						'fid' => $image['id'],
						'sort' => $image['sort'],
						'position' => $image['position'],
						'createtime' => $_SERVER['REQUEST_TIME'],
						'deletetime' => 0,
						'isdelete' => 0
					]))
					{
						$this->model('product')->rollback();
						return new json(json::PARAMETER_ERROR, '图片添加失败');
					}
				}
			}
			
			if (! $hasListImage)
			{
				$this->model('product')->rollback();
				return new json(json::PARAMETER_ERROR, '必须设置列表图');
			}
			
			if (is_array($this->post('prototype')) && ! empty($this->post('prototype')))
			{
				foreach ($this->post('prototype') as $prototype)
				{
					if (! $this->model('prototype')->insert([
						'pid' => $product_id,
						'name' => $prototype['name'],
						'type' => $prototype['type'],
						'value' => $prototype['value'],
						'createtime' => $_SERVER['REQUEST_TIME'],
						'deletetime' => $_SERVER['REQUEST_TIME'],
						'isdelete' => 0
					]))
					{
						$this->model('product')->rollback();
						return new json(json::PARAMETER_ERROR, '属性添加失败');
					}
				}
			}
			
			if (is_array($this->post('collection')) && ! empty($this->post('collection')))
			{
				foreach ($this->post('collection') as $collection)
				{
					if (! $this->model('collection')->insert([
						'pid' => $product_id,
						'content' => $collection['content'],
						'price' => $collection['price'],
						'v1price' => $collection['v1price'],
						'v2price' => $collection['v2price'],
						'stock' => $collection['stock'],
						'sku' => $collection['sku'],
						'logo' => empty($collection['logo']) ? NULL : $collection['logo'],
						'deletetime' => 0,
						'createtime' => $_SERVER['REQUEST_TIME'],
						'isdelete' => 0,
						'available' => $collection['available']
					]))
					{
						$this->model('product')->rollback();
						return new json(json::PARAMETER_ERROR, '多属性添加失败');
					}
				}
			}
			
			if (is_array($this->post('fee_province')) && ! empty($this->post('fee_province')))
			{
				foreach ($this->post('fee_province') as $province)
				{
					if (! $this->model('product_province')->insert([
						'product_id' => $product_id,
						'province_id' => $province
					]))
					{
						$this->model('province')->rollback();
						return new json(json::PARAMETER_ERROR, '添加配送城市失败');
					}
				}
			}
			
			if (is_array($this->post('bind')) && ! empty($this->post('bind')))
			{
				foreach ($this->post('bind') as $bind)
				{
					if (empty($bind['num']) || empty($bind['unit']) || empty($bind['sort']) || empty($bind['price']) || empty($bind['inprice']) || empty($bind['v1price']) || empty($bind['v2price']))
					{
						continue;
					}
					if (! empty($this->model('bind')
						->where('pid=? and content=? and num=?', [
						$product_id,
						$bind['content'],
						$bind['num']
					])
						->find()))
					{
						$this->model('product')->rollback();
						return new json(json::PARAMETER_ERROR, '无法捆绑相同数量的商品');
					}
					if (! $this->model('bind')->insert([
						'pid' => $product_id,
						'content' => $bind['content'],
						'num' => $bind['num'],
						'inprice' => $bind['inprice'],
						'price' => $bind['price'],
						'v1price' => $bind['v1price'],
						'v2price' => $bind['v2price'],
						'unit' => $bind['unit'],
						'sort' => $bind['sort']
					]))
					{
						$this->model('product')->rollback();
						return new json(json::PARAMETER_ERROR, '商品捆绑添加失败');
					}
				}
			}
			
			
			if (is_array($this->post('product_publish')) && !empty($this->post('product_publish')))
			{
				foreach ($this->post('product_publish') as $publish)
				{
					$publish = json_decode(urldecode($publish),true);
					$publish['product_id'] = $product_id;
					if($this->model('product_publish')->insert($publish))
					{
						foreach ($publish['price'] as $price)
						{
							$price['product_id'] = $product_id;
							$price['publish_id'] = $publish['publish_id'];
							$price['num'] = $price['selled'];
							$this->model('product_publish_price')->insert($price);
						}
					}
				}
			}
			
			
			$this->model('product')->commit();
			$this->model("admin_log")->insertlog($admin, '商品管理，增加商品成功，商品id：' . $product_id, 1);
			$productHelper->cutPublish($product_id);
			$this->call('search', 'rebuild',['id'=>$product_id]);
			return new json(json::OK, NULL, $product_id);
		}
		else
		{
			$this->model('product')->rollback();
			return new json(json::PARAMETER_ERROR, '添加失败');
		}
	}

	function search()
	{
		$parameter = [
			'product.id',
			'product.name',
			'product.oldprice as oldprice',
			'product.price as price',
			'product.v1price as v1price',
			'product.v2price as v2price',
			'product.short_description',
			'store.name as store',
			'product.origin',
			'product.selled',
			'product.stock',
			'sum(product.percent) as percent'
		];
		
		$keywords = htmlspecialchars($this->get('keywords', '', 'trim'));
		$keywords = substr($keywords, 0, 32);
		
		$product = $this->model('product')
			->where('id=?', [
			$keywords
		])
			->find();
		if (! empty($product))
		{
			$product = [
				$product
			];
		}
		else
		{
			$searchHelper = new productSearchEngine();		
			$keyword = $searchHelper->depart($keywords);
			if (empty($keyword))
			{
				// 分词失败，使用原来的关键词进行搜索
				$keyword = [$keywords];
			}
			else
			{
				$temp_key = [];
				foreach ($keyword as $key)
				{
					$temp_key[] = $key['word'];
				}
				$keyword = $temp_key;
			}
			$start = $this->get('start', 0);
			$length = $this->get('length', 10);
			
			$fkey = array_shift($keyword);
			$name_param = ['%'.$fkey.'%'];
			$keyword_param = [$fkey];
			$name_where = 'name like ?';
			$keyword_where = 'searchIndex.keyword = ?';
			foreach ($keyword as $word)
			{
				$name_param[] = '%'.$word.'%';
				$keyword_param[] = $word;
				$name_where = $name_where.' and name like ?';
				$keyword_where = $keyword_where.' or searchIndex.keyword=?';
			}
			$name_where = '('.$name_where.')';
			$keyword_where = '('.$keyword_where.')';
			$name_param[] = 0;
			$name_param[] = $_SERVER['REQUEST_TIME'];
			$name_param[] = $_SERVER['REQUEST_TIME'];
			$keyword_param[] = 0;
			$keyword_param[] = $_SERVER['REQUEST_TIME'];
			$keyword_param[] = $_SERVER['REQUEST_TIME'];
			
			// 对于商品标题，默认使用1000的关键度
			$sql = 'select ' . implode(',', $parameter) . ' 
        			from (
        			(
        				select product.*,1000 as percent
        				from product 
        				where 
        					'.$name_where.' and 
        					isdelete=? and 
        					(
        						(product.auto_status = 0 and product.status = 1) or
        						(auto_status = 1 and avaliabletime_from <= ? and avaliabletime_to >= ?)
        					)
        				order by product.sort asc,product.id desc
        			)
					union
					(
        				select product.*,searchIndex.percent
        				from product 
        				left join searchIndex 
        				on searchIndex.pid=product.id 
        				where 
        				'.$keyword_where.'
        				and product.isdelete=? and 
        				(
        					(product.auto_status = 0 and product.status = 1) or
        					(product.auto_status = 1 and product.avaliabletime_from <= ? and product.avaliabletime_to >= ?)
        				) 
        				order by percent desc
        			)
        		) as product
        			left join store
        			on store.id=product.store
					group by product.id
        			order by percent desc,product.sort asc,product.id desc
        			limit ' . $start . ',' . $length;
			
			$param = array_merge($name_param,$keyword_param);
			$product = $this->model('product')->query($sql, $param);
		}
		
		$productHelper = new \application\helper\product();
		foreach ($product as &$p)
		{
			$p['origin'] = $this->model('country')->get($p['origin']);
			$p['image'] = $productHelper->getListImage($p['id']);
			
			$bind = $this->model('bind')->where('pid=?',[$p['id']])
			->orderby('num','desc')
			->find([
				'price',
				'v1price',
				'v2price',
			]);
			if (!empty($bind))
			{
				$p['price'] = $bind['price'];
				$p['v1price'] = $bind['v1price'];
				$p['v2price'] = $bind['v2price'];
			}
			
			// 商品价格
			$filter = [
				'pid' => $p['id'],
				'isdelete' => 0,
				'available' => 1,
				'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)'
			];
			$price_collection = $this->model('collection')->fetch($filter);
			if (! empty($price_collection))
			{
				if ($price_collection[0]['sum(stock)'] !== NULL)
				{
					$p['stock'] = $price_collection[0]['sum(stock)'];
				}
				if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)'] !== NULL)
				{
					$p['price'] = $price_collection[0]['min(price)']; // '~'.$price_collection[0]['max(price)'];
				}
				if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL)
				{
					$p['v1price'] = $price_collection[0]['min(v1price)']; // '~'.$price_collection[0]['max(v1price)'];
				}
				if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL)
				{
					$p['v2price'] = $price_collection[0]['min(v2price)']; // '~'.$price_collection[0]['max(v2price)'];
				}
			}
		}
		
		$product_filter = [
			'isdelete' => 0,
			'status' => 1,
			'parameter' => 'count(*)'
		];
		$total = $this->model('product')->fetchAll($product_filter);
		
		$productReturnModel = [
			'current' => count($product),
			'total' => isset($total[0]['count(*)']) ? $total[0]['count(*)'] : 0,
			'start' => $this->get('start', 0),
			'length' => $this->get('length', 10),
			'data' => $product
		];
		
		if ($this->get('draw', NULL) !== NULL)
		{
			$productReturnModel['draw'] = $this->get('draw');
		}
		
		if (! empty($keywords))
		{
			$userHelper = new user();
			$this->model('search_log')->insert([
				'ip' => ip(),
				'keywords' => $keywords,
				'time' => $_SERVER['REQUEST_TIME'],
				'uid' => $userHelper->isLogin(),
				'total' => isset($total[0]['count(*)']) ? $total[0]['count(*)'] : 0,
				'userAgent' => $_SERVER['HTTP_USER_AGENT']
			]);
		}
		
		return new json(json::OK, NULL, $productReturnModel);
	}

	/**
	 * 移动首页商品排序
	 */
	function topmove()
	{
		$admin = $this->session->id;
		$id = $this->post('id');
		$forward = $this->post('forward', 'up');
		$line = $this->model('product_top')
			->orderby('sort', 'asc')
			->select();
		
		foreach ($line as $index => $product)
		{
			if ($product['pid'] == $id)
			{
				$flag = $index;
			}
		}
		
		if ($flag == 0 && $forward == 'up')
		{
			$this->model("admin_log")->insertlog($admin, '商品管理，首页商品排序', 1);
			return new json(json::OK);
		}
		if ($flag == count($line) - 1 && $forward == 'down')
		{
			$this->model("admin_log")->insertlog($admin, '商品管理，首页商品排序', 1);
			return new json(json::OK);
		}
		if ($forward == 'up')
		{
			$temp = $line[$flag];
			$line[$flag] = $line[$flag - 1];
			$line[$flag - 1] = $temp;
		}
		else if ($forward == 'down')
		{
			$tmep = $line[$flag];
			$line[$flag] = $line[$flag + 1];
			$line[$flag + 1] = $tmep;
		}
		
		foreach ($line as $index => $product)
		{
			$this->model('product_top')
				->where('pid=?', [
				$product['pid']
			])
				->limit(1)
				->update('sort', $index);
		}
		$this->model("admin_log")->insertlog($admin, '商品管理，首页商品排序', 1);
		return new json(json::OK);
	}

	/**
	 * 将商品从首页上下架
	 */
	function untop()
	{
		$admin = $this->session->id;
		$id = $this->post('id');
		if (! empty($id))
		{
			if ($this->model('product_top')
				->where('pid=?', [
				$id
			])
				->delete())
			{
				$this->model("admin_log")->insertlog($admin, '商品管理，将商品从首页下架,商品id：' . $id, 1);
				return new json(json::OK);
			}
			$this->model("admin_log")->insertlog($admin, '商品管理，将商品从首页下架（参数错误）');
			return new json(json::PARAMETER_ERROR);
		}
		$this->model("admin_log")->insertlog($admin, '商品管理，将商品从首页下架（参数错误）');
		return new json(json::PARAMETER_ERROR);
	}

	/**
	 * 将商品推送到首页
	 * 
	 * @return \application\message\json
	 */
	function top()
	{
		$admin = $this->session->id;
		$id = $this->post('id');
		if (! empty($id))
		{
			if (empty($this->model('product_top')
				->where('pid=?', [
				$id
			])
				->find()))
			{
				$total = $this->model('product_top')->select('count(*)');
				
				$data = [
					'pid' => $id,
					'sort' => $total[0]['count(*)'],
					'time' => $_SERVER['REQUEST_TIME']
				];
				if ($this->model('product_top')->insert($data))
				{
					$data = $this->model('product_top')
						->table('product', 'left join', 'product.id=product_top.pid')
						->where('product.id=?', [
						$id
					])
						->find('product.name,product.id,product_top.sort');
					$this->model("admin_log")->insertlog($admin, '商品管理，将商品推送到首页，商品id:' . $id, 1);
					return new json(json::OK, NULL, $data);
				}
				$this->model("admin_log")->insertlog($admin, '商品管理，将商品推送到首页(请求参数错误)');
				return new json(json::PARAMETER_ERROR);
			}
			$this->model("admin_log")->insertlog($admin, '商品管理，将商品推送到首页(商品已经存在)');
			return new json(json::PARAMETER_ERROR, '已经存在了');
		}
	}

	/**
	 * 保存商品信息
	 */
	function save()
	{
		$admin = $this->session->id;
		$this->model('product')->transaction();
		$productHelper = new helper\product();
		
		$product = $productHelper->createProductData($this->post());
		if (! isset($product['id']))
		{
			return new json(json::PARAMETER_ERROR, '商品id错误');
		}
		
		$product_id = $product['id'];
		
		// 保存的时候商品创建时间不做任何修改
		unset($product['createtime']);
		
		// 商品修改时间
		$product['modifytime'] = $_SERVER['REQUEST_TIME'];
		
		/* if (empty($product['store']))
		{
			return new json(json::PARAMETER_ERROR, '请选择发货仓库');
		} */
		if ($product['outside'] == 2)
		{
			if (empty($product['ztax']))
			{
				$this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(请填写综合税种)');
				return new json(json::PARAMETER_ERROR, '请填写综合税种');
			}
		}
		if ($product['outside'] == 3)
		{
			if (empty($product['postTaxNo']))
			{
				return new json(json::PARAMETER_ERROR, '请选择行邮税号');
			}
		}
		if (empty(intval($product['MeasurementUnit'])))
		{
			return new json(json::PARAMETER_ERROR,'请填写计量单位');
		}
		/* if ($product['status'] == 1)
		{
			if (floatval($product['inprice']) == 0)
			{
				return new json(json::PARAMETER_ERROR, '进价不能为0');
			}
		} */
		if (empty($product['barcode']))
		{
			return new json(json::PARAMETER_ERROR, '条形码不能为空');
		}
		/* if (intval($product['selled']) <= 0)
		{
			return new json(json::PARAMETER_ERROR, '售卖数不能为空');
		} */
		
		/*
		 * if ($this->model('product')->where('id!=? and barcode=? and isdelete=?', [$product_id, $product['barcode'], 0])->find()) {
		 * $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(存在相同条形码的商品)');
		 * return new json(json::PARAMETER_ERROR, '存在相同条形码的商品');
		 * }
		 */
		
		if ($this->model('product')
			->where('id=?', [
			$product_id
		])
			->update($product, '', true))
		{
			$this->model('category_product')
				->where('pid=?', [
				$product_id
			])
				->delete();
			if (is_array($this->post('category')) && ! empty($this->post('category')))
			{
				foreach ($this->post('category') as $category)
				{
					if (! $this->model('category_product')->insert([
						'cid' => $category,
						'pid' => $product_id,
						'createtime' => $_SERVER['REQUEST_TIME'],
						'deletetime' => 0,
						'isdelete' => 0
					]))
					{
						$this->model('product')->rollback();
						return new json(json::PARAMETER_ERROR, '分配分类错误');
					}
				}
			}
			
			$hasListImage = false;
			$this->model('product_img')
				->where('pid=?', [
				$product_id
			])
				->delete();
			if (is_array($this->post('image')) && ! empty($this->post('image')))
			{
				foreach ($this->post('image') as $image)
				{
					if ($image['position'] == 1)
					{
						$hasListImage = true;
					}
					if (! $this->model('product_img')->insert([
						'pid' => $product_id,
						'fid' => $image['id'],
						'sort' => $image['sort'],
						'position' => $image['position'],
						'createtime' => $_SERVER['REQUEST_TIME'],
						'deletetime' => 0,
						'isdelete' => 0
					]))
					{
						$this->model('product')->rollback();
						return new json(json::PARAMETER_ERROR, '图片添加失败');
					}
				}
			}
			if (! $hasListImage)
			{
				$this->model('product')->rollback();
				return new json(json::PARAMETER_ERROR, '必须设置列表图');
			}
			
			$this->model('prototype')
				->where('pid=?', [
				$product_id
			])
				->delete();
			if (is_array($this->post('prototype')) && ! empty($this->post('prototype')))
			{
				foreach ($this->post('prototype') as $prototype)
				{
					if (! $this->model('prototype')->insert([
						'pid' => $product_id,
						'name' => $prototype['name'],
						'type' => $prototype['type'],
						'value' => $prototype['value'],
						'createtime' => $_SERVER['REQUEST_TIME'],
						'deletetime' => $_SERVER['REQUEST_TIME'],
						'isdelete' => 0
					]))
					{
						$this->model('product')->rollback();
						return new json(json::PARAMETER_ERROR, '属性添加失败');
					}
				}
			}
			
			$result = $this->model('collection')
				->where('pid=?', [
				$product_id
			])
				->find();
			
			if (! empty($result))
			{
				if (! $this->model('collection')
					->where('pid=?', [
					$product_id
				])
					->delete())
				{
					return new json(json::PARAMETER_ERROR, 'collection更新中断');
				}
			}
			
			if (is_array($this->post('collection')) && ! empty($this->post('collection')))
			{
				foreach ($this->post('collection') as $collection)
				{
					if (! $this->model('collection')->insert([
						'pid' => $product_id,
						'content' => $collection['content'],
						'price' => $collection['price'],
						'v1price' => $collection['v1price'],
						'v2price' => $collection['v2price'],
						'stock' => $collection['stock'],
						'sku' => $collection['sku'],
						'logo' => empty($collection['logo']) ? NULL : $collection['logo'],
						'deletetime' => 0,
						'createtime' => $_SERVER['REQUEST_TIME'],
						'isdelete' => 0,
						'available' => $collection['available']
					]))
					{
						$this->model('product')->rollback();
						return new json(json::PARAMETER_ERROR, '多属性添加失败');
					}
				}
			}
			
			$this->model('product_province')
				->where('product_id=?', [
				$product_id
			])
				->delete();
			if (is_array($this->post('fee_province')) && ! empty($this->post('fee_province')))
			{
				foreach ($this->post('fee_province') as $province)
				{
					if (! $this->model('product_province')->insert([
						'product_id' => $product_id,
						'province_id' => $province
					]))
					{
						$this->model('province')->rollback();
						return new json(json::PARAMETER_ERROR, '添加配送城市失败');
					}
				}
			}
			
			$this->model('bind')
				->where('pid=?', [
				$product_id
			])
				->delete();
			if (is_array($this->post('bind')) && ! empty($this->post('bind')))
			{
				foreach ($this->post('bind') as $bind)
				{
					if (empty($bind['sort']) || empty($bind['unit']) || empty($bind['num']) || empty($bind['price']) || empty($bind['inprice']) || empty($bind['v1price']) || empty($bind['v2price']))
					{
						continue;
					}
					
					if (! empty($this->model('bind')
						->where('pid=? and content=? and num=?', [
						$product_id,
						$bind['content'],
						$bind['num']
					])
						->find()))
					{
						$this->model('product')->rollback();
						return new json(json::PARAMETER_ERROR, '无法捆绑相同数量的商品');
					}
					
					if (! $this->model('bind')->insert([
						'pid' => $product_id,
						'content' => $bind['content'],
						'num' => $bind['num'],
						'inprice' => $bind['inprice'],
						'price' => $bind['price'],
						'v1price' => $bind['v1price'],
						'v2price' => $bind['v2price'],
						'unit' => $bind['unit'],
						'sort' => $bind['sort']
					]))
					{
						$this->model('product')->rollback();
						return new json(json::PARAMETER_ERROR, '商品捆绑添加失败');
					}
				}
			}
			
			
			$this->model('product_publish')->where('product_id=?',[$product_id])->delete();
			$this->model('product_publish_price')->where('product_id=?',[$product_id])->delete();
			if (is_array($this->post('product_publish')) && !empty($this->post('product_publish')))
			{
				foreach ($this->post('product_publish') as $publish)
				{
					$publish = json_decode(urldecode($publish),true);
					$publish['product_id'] = $product_id;
					if($this->model('product_publish')->insert($publish))
					{
						foreach ($publish['price'] as $price)
						{
							$price['product_id'] = $product_id;
							$price['publish_id'] = $publish['publish_id'];
							$price['num'] = $price['selled'];
							$this->model('product_publish_price')->insert($price);
						}
					}
				}
			}
			
			// 顺便清空购物车中的相关商品
			// $this->model('cart')->where('pid=?',[$product_id])->delete();
			
			$this->model('product')->commit();
			$this->model("admin_log")->insertlog($admin, '商品保存成功:'.$product_id);
			$productHelper->cutPublish($product_id);
			$this->call('search', 'rebuild',['id'=>$product_id]);
			return new json(json::OK, NULL, $product_id);
		}
		else
		{
			$this->model('product')->rollback();
			return new json(json::PARAMETER_ERROR, '更新失败');
		}
	}

	/**
	 * 删除商品
	 * 
	 * @return \application\message\json
	 */
	function remove()
	{
		$admin = $this->session->id;
		$id = $this->post('id');
		if ($this->model('product')
			->where('id=?', [
			$id
		])
			->update([
			'isdelete' => 1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
			$this->model("admin_log")->insertlog($admin, '商品管理，删除商品成功，商品id：' . $id, 1);
			return new json(json::OK);
		}
		$this->model("admin_log")->insertlog($admin, '商品管理，删除商品失败（参数错误）');
		return new json(json::PARAMETER_ERROR);
	}

	function restore()
	{
		$admin = $this->session->id;
		$id = $this->post('id');
		if ($this->model('product')
			->where('id=?', [
			$id
		])
			->update('isdelete', 0))
		{
			$this->model("admin_log")->insertlog($admin, '商品管理，回收站商品恢复，商品id：' . $id, 1);
			return new json(json::OK);
		}
		$this->model("admin_log")->insertlog($admin, '商品管理，回收站商品恢复失败（参数错误）');
		return new json(json::PARAMETER_ERROR);
	}
	
	function import1()
	{
		$isPublishLine = function($product_line){
			$i = 0;
			while (empty($product_line[$i]))
			{
				$i++;
			}
			if ($i == 9)
			{
				return true;
			}
			return false;
		};
		
		$isPriceLine = function($product_line){
			$i = 0;
			while(empty($product_line[$i]))
			{
				$i++;
			}
			if ($i == 14)
			{
				return true;
			}
			return false;
		};
		
		$adminHelper = new admin();
		$aid = $adminHelper->getAdminId();
		if (empty($aid))
		{
			return new json(json::NOT_LOGIN);
		}
		
		$roleModel = $this->model('role');
		if ($roleModel->checkPower($adminHelper->getGroupId(),'product',roleModel::POWER_UPDATE))
		{
			$config = config('file');
			$config->type = [
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'application/vnd.ms-excel',
				'application/vnd.ms-office',
				'application/zip'
			];
			$config->size = 1024 * 1024 * 10;
			
			$Ein = array(
				"A",
				"B",
				"C",
				"D",
				"E",
				"F",
				"G",
				"H",
				"I",
				"J",
				"K",
				"L",
				"M",
				"N",
				"O",
				"P",
				"Q",
				"R",
				"S",
				"T",
			);
			
			// 接受文件
			if (isset($_FILES['file']))
			{
				$file = $this->file->receive($_FILES['file'], $config);
				if (is_file($file))
				{
					$phpexcel_root = ROOT . '/extends/PHPExcel';
					include $phpexcel_root . '/PHPExcel/IOFactory.php';
					$objReader = \PHPExcel_IOFactory::createReader('Excel2007');
					if ($objReader->canRead($file))
					{
						try
						{
							$objPHPExcel = $objReader->load($file);
							$sheet = $objPHPExcel->getSheet(0);
							$rowNum = $sheet->getHighestRow();//文件行
						}
						catch (\Exception $e)
						{
							return new json(json::PARAMETER_ERROR, '文件解析失败');
						}
						for ($row = 2; $row <= $rowNum; $row ++)
						{
							// 行数是以第2行开始
							$dataset = [];
							for ($column = 0; $column < count($Ein); $column ++)
							{
								$dataset[] = $sheet->getCell($Ein[$column] . $row)->getCalculatedValue();
							}
							$products[] = $dataset;
						}
						
						$j = 0;
						$z = 0;
						for($i = 0;$i<count($products);$i = $i+$j+$z)
						{
							$id = $products[$i][0];
							$spu = $products[$i][1];
							$name = $products[$i][2];
							$brand = $products[$i][3];
							$oldprice = $products[$i][4];
							$fee = $products[$i][5];
							$tax = $products[$i][6];
							$freetax = $products[$i][7];
							$MeasurementUnit = $products[$i][8];
							$status = $products[$i][9];
							
							$publish = [];
							$j = 0;
							while($isPublishLine($products[$i+$j]))
							{
								$publish[] = [
									'publish_id' => $products[$i+$j][10],
									'sku' => $products[$i+$j][11],
									'store'=>$products[$i+$j][12],
									'stock' => $products[$i+$j][14],
								];
								$j++;
								
								$price = [];
								
								$z = 0;
								while($isPriceLine($products[$i+$j+$z]))
								{
									$price[] = [
										'num' => $products[$i+$j+$z][15],
										'inprice' => $products[$i+$j+$z][16],
										'price' => $products[$i+$j+$z][17],
										'v1price' => $products[$i+$j+$z][18],
										'v2price' => $products[$i+$j+$z][19],
									];
									$z++;
								}
							}
							
							$product = [
								'name' => $name,
							];
							
						}
					}
					return new json(json::PARAMETER_ERROR,'文件不是一个可读excel文档');
				}
				return new json(json::PARAMETER_ERROR,'文件上传失败');
			}
			return new json(json::PARAMETER_ERROR,'请选择文件');
		}
		return new json(json::NO_POWER);
	}

	function import()
	{
		$emptyProduct = function($product)
		{
			foreach ($product as $value)
			{
				if (!empty(trim($value)))
				{
					return false;
				}
			}
			return true;
		};
		
		$adminHelper = new admin();
		$aid = $adminHelper->getAdminId();
		
		$Ein = array(
			"A",
			"B",
			"C",
			"D",
			"E",
			"F",
			"G",
			"H",
			"I",
			"J",
			"K",
			"L",
			"M",
			"N",
			"O",
			"P",
			"Q",
			"R",
			"S",
			"T",
			"U",
			"V",
			"W",
			"X",
			"Y",
			"Z",
			"AA",
			"AB",
			"AC"
		);
		
		$products = [];
		
		$config = config('file');
		$config->type = [
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.ms-excel',
			'application/vnd.ms-office',
			'application/zip'
		];
		$config->size = 1024 * 1024 * 10;
		// 接受文件
		if (isset($_FILES['file']))
		{
			$file = $this->file->receive($_FILES['file'], $config);
			if (is_file($file))
			{
				$phpexcel_root = ROOT . '/extends/PHPExcel';
				include $phpexcel_root . '/PHPExcel/IOFactory.php';
				$objReader = \PHPExcel_IOFactory::createReader('Excel2007');
				if ($objReader->canRead($file))
				{
					try
					{
						$objPHPExcel = $objReader->load($file);
						$sheet = $objPHPExcel->getSheet(0);
						$rowNum = $sheet->getHighestRow();//文件行
					}
					catch (\Exception $e)
					{
						return new json(json::PARAMETER_ERROR, '文件解析失败');
					}
					for ($row = 2; $row <= $rowNum; $row ++)
					{
						// 行数是以第2行开始
						$dataset = [];
						for ($column = 0; $column < count($Ein); $column ++)
						{
							$dataset[] = $sheet->getCell($Ein[$column] . $row)->getCalculatedValue();
						}
						$products[] = $dataset;
					}
					
					$response_body = [];
					
					foreach ($products as $index => $product)
					{
						if ($emptyProduct($product))
						{
							continue;
						}
						list(
							$id,
							$sku,
							$barcode,
							$inprice,
							$selled,
							$bind0_inprice,
							$bind0_num,
							$bind1_inprice,
							$bind1_num,
							$down_reason,
							$name,
							$outside,
							$freetax,
							$sort,
							$oldprice,
							$price,
							$v1price,
							$v2price,
							$bind0_price,
							$bind0_v1price,
							$bind0_v2price,
							$bind1_price,
							$bind1_v1price,
							$bind1_v2price,
							$stock,
							$status,
							$fee,
							$publish,
							$unit
						) = $product;
						
						$bind = ['num'=>$selled,'inprice'=>$inprice,'price'=>$price,'v1price'=>$v1price,'v2price'=>$v2price];
						$bind1 = ['num'=>$bind0_num,'inprice'=>$bind0_inprice,'price'=>$bind0_price,'v1price'=>$bind0_v1price,'v2price'=>$bind0_v2price];
						$bind2 = ['num'=>$bind1_num,'inprice'=>$bind1_inprice,'price'=>$bind1_price,'v1price'=>$bind1_v1price,'v2price'=>$bind1_v2price];
					
						switch (trim($outside))
						{
							case '普通商品':
								$outside = 0;
							break;
							case '进口商品':
								$outside = 1;
								break;
							case '直供商品':
								$outside = 2;
								break;
							case '直邮商品':
								$outside = 3;
								break;
							default:
								$outside = 0;
						}
						
						if (trim($freetax) == '是')
						{
							$freetax = 1;
						}
						else
						{
							$freetax = 0;
						}
						
						
						if (trim($status) == '上架')
						{
							$status = 1;
						}
						else
						{
							$status = 0;
						}
						
						$publish = trim($publish);
						$publish = $this->model('publish')
						->where('name=?', [
							$publish
						])
						->find();
						if (! empty($publish))
						{
							$publish = $publish['id'];
						}
						else
						{
							$publish = NULL;
						}
						
						if (!empty($bind))
						{
							$bind['unit'] = $unit;
						}
						if (!empty($bind1))
						{
							$bind1['unit'] = $unit;
						}
						if (!empty($bind2))
						{
							$bind2['unit'] = $unit;
						}
						
						$data = [
							'sku' => $sku,
							'name'=> $name,
							'down_reason' => $down_reason,
							'barcode' => $barcode,
							'inprice' => $inprice,
							'selled' => $selled,
							'outside' => $outside,
							'freetax'=>$freetax,
							'sort' => $sort,
							'oldprice' => $oldprice,
							'price' => $price,
							'v1price' => $v1price,
							'v2price' => $v2price,
							'stock' => $stock,
							'status' => $status,
							'fee' => $fee,
							'publish' => $publish,
							'auto_status' => 0,
						];
						
						
						if (empty($id))
						{
							$productHelper = new \application\helper\product();
							$data = $productHelper->createProductData($data);
						
							if($this->model('product')->insert($data))
							{
								$id = $this->model("product")->lastInsertId();
								if (!empty($bind))
								{
									if (!empty($bind['num']) && !empty($bind['price']) && !empty($bind['v1price']) && !empty($bind['v2price']) && !empty($bind['inprice']))
									{
										$bind['pid'] = $id;
										$bind['content'] = '';
										$this->model('bind')->insert($bind);
									}
								}
								if (!empty($bind1))
								{
									if (!empty($bind1['num']) && !empty($bind1['price']) && !empty($bind1['v1price']) && !empty($bind1['v2price']) && !empty($bind1['inprice']))
									{
										$bind1['pid'] = $id;
										$bind1['content'] = '';
										$this->model('bind')->insert($bind1);
									}
								}
								if (!empty($bind2))
								{
									if (!empty($bind2['num']) && !empty($bind2['price']) && !empty($bind2['v1price']) && !empty($bind2['v2price']) && !empty($bind2['inprice']))
									{
										$bind2['pid'] = $id;
										$bind2['content'] = '';
										$this->model('bind')->insert($bind2);
									}
								}
								$this->model("admin_log")->insertlog($aid, '导入商品信息成功,data:'.json_encode($data), 1);
								$response_body[] = [
									'id' => $id,
									'sku'=> $sku,
									'name' => $name,
									'success'=>true,
								];
								//return new json(json::OK);
							}
							else
							{
								$response_body[] = [
									'id' => $id,
									'sku'=> $sku,
									'name' => $name,
									'success'=>false,
								];
								return new json(json::PARAMETER_ERROR,'第'.$index.'个商品添加失败');
							}
						}
						else
						{
							$data['modifytime'] = $_SERVER['REQUEST_TIME'];
							if($this->model('product')
							->where('id=?', [
								$id
							])
							->update($data))
							{
								$this->model('bind')->where('pid=?',[$id])->delete();
								if (!empty($bind))
								{
									if (!empty($bind['num']) && !empty($bind['price']) && !empty($bind['v1price']) && !empty($bind['v2price']) && !empty($bind['inprice']))
									{
										$bind['pid'] = $id;
										$bind['content'] = '';
										$this->model('bind')->insert($bind);
									}
								}
								if (!empty($bind1))
								{
									if (!empty($bind1['num']) && !empty($bind1['price']) && !empty($bind1['v1price']) && !empty($bind1['v2price']) && !empty($bind1['inprice']))
									{
										$bind1['pid'] = $id;
										$bind1['content'] = '';
										$this->model('bind')->insert($bind1);
									}
								}
								if (!empty($bind2))
								{
									if (!empty($bind2['num']) && !empty($bind2['price']) && !empty($bind2['v1price']) && !empty($bind2['v2price']) && !empty($bind2['inprice']))
									{
										$bind2['pid'] = $id;
										$bind2['content'] = '';
										$this->model('bind')->insert($bind2);
									}
								}
								
								$data['id'] = $id;
								$this->model("admin_log")->insertlog($aid, '导入商品信息成功,data:'.json_encode($data), 1);
								$response_body[] = [
									'id' => $id,
									'sku'=> $sku,
									'name' => $name,
									'success'=>true,
								];
							}
							else
							{
								$response_body[] = [
									'id' => $id,
									'sku'=> $sku,
									'name' => $name,
									'success'=>false,
								];
							}
						}
					}
					return new json(json::OK,'导入成功',$response_body);
				}
				else
				{
					return new json(json::PARAMETER_ERROR,'文件无法读取');
				}
			}
			else
			{
				return new json(json::PARAMETER_ERROR,'文件上传失败');
			}
		}
		else
		{
			return new json(json::PARAMETER_ERROR, '文件下标错误');
		}
    }
}
