<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;
use application\helper\admin;
use application\helper\productSearchEngine;
use application\model\roleModel;

/**
 * @author jin12
 *
 */
class product extends ajax
{
	private $_aid = NULL;
	
	function find()
	{
		$id = $this->post('id');
		$product = $this->model('product')->where('id=?',[$id])->find();
		if (empty($product))
		{
			return new json(json::PARAMETER_ERROR);
		}
		$category3 = $this->model('bcategory_product')->where('product_id=?',[$id])->scalar('bc_id');
		$product['bcategory'] = array($category3);
		while (!empty($category3))
		{
			$category2 = $this->model('bcategory')->where('id=?',[$category3])->scalar('bc_id');
			if (empty($category2))
			{
				break;
			}
			else
			{
				array_unshift($product['bcategory'], $category2);
				$category3 = $category2;
			}
		}
		
		$product['province'] = [];
		$province = $this->model('product_province')->where('product_id=?',[$id])->select('province_id');
		foreach ($province as $p)
		{
			$product['province'][] = $p['province_id'];
		}
		
		$product['image'] = [];
		$image = $this->model('product_img')->orderby('position','desc')->orderby('sort','asc')->where('pid=? and isdelete=?',[$id,0])->select();
		foreach ($image as $img)
		{
			$path = $this->model('upload')->where('id=?',[$img['fid']])->scalar('path');
			$product['image'][] = array(
				'id' => $img['fid'],
				'path' => $path,
				'position' => $img['position'],
			);
		}
		
		$product['product_publish'] = $this->model('product_publish')->where('product_id=?',[$id])->select();
		foreach ($product['product_publish'] as &$product_publish)
		{
			$product_publish['product_publish_price'] = $this->model('product_publish_price')->where('product_id=? and publish_id=?',[$product_publish['product_id'],$product_publish['publish_id']])->select();
		}
		
		return new json(json::OK,NULL,$product);
	}
	
	/**
	 * 添加商品
	 */
	function create()
	{
		
		$product = new \application\entity\product();
		$data = $this->post();
		
		if ($data['outside']==2)
		{
			$data['postTaxNo'] = NULL;
		}
		else if ($data['outside']==3)
		{
			$data['ztax'] = NULL;
		}
		else
		{
			$data['postTaxNo'] = NULL;
			$data['ztax'] = NULL;
		}
		
		$product->setData($data);
		if ($product->validate())
		{
			if($product->save())
			{
				$this->model("admin_log")->insertlog($this->_aid, '商品管理，增加商品成功，商品id：' . $product->getPrimaryKey(), 1);
				//切换商品的供应商等信息
				$productHelper = new \application\helper\product();
				$productHelper->cutPublish($product->getPrimaryKey());
				//创建商品的索引
				$this->call('search', 'rebuild',['id'=>$product->getPrimaryKey()]);
				return new json(json::OK, NULL, $product->getPrimaryKey());
			}
			else
			{
				return new json(json::PARAMETER_ERROR, '添加失败');
			}
		}
		else
		{
			return new json(json::PARAMETER_ERROR, $product->getErrors());
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
			$this->model("admin_log")->insertlog($this->_aid, '商品管理，首页商品排序', 1);
			return new json(json::OK);
		}
		if ($flag == count($line) - 1 && $forward == 'down')
		{
			$this->model("admin_log")->insertlog($this->_aid, '商品管理，首页商品排序', 1);
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
		$this->model("admin_log")->insertlog($this->_aid, '商品管理，首页商品排序', 1);
		return new json(json::OK);
	}

	/**
	 * 将商品从首页上下架
	 */
	function untop()
	{
		$id = $this->post('id');
		if (! empty($id))
		{
			if ($this->model('product_top')
				->where('pid=?', [
				$id
			])
				->delete())
			{
				$this->model("admin_log")->insertlog($this->_aid, '商品管理，将商品从首页下架,商品id：' . $id, 1);
				return new json(json::OK);
			}
			return new json(json::PARAMETER_ERROR);
		}
		return new json(json::PARAMETER_ERROR);
	}

	/**
	 * 将商品推送到首页
	 * 
	 * @return \application\message\json
	 */
	function top()
	{
		$ids = $this->post('id',array());
		if (! empty($ids))
		{
			$num = 0;
			foreach ($ids as $id)
			{
				if($this->model('product_top')->insert([
					'pid'=>$id,
					'sort' =>1,
					'time' =>time(),
				]))
				{
					$num++;
				}
			}
			return new json(json::OK,NULL,$num);
		}
		return new json(json::PARAMETER_ERROR,'请选择商品');
	}
	
	function top_sort()
	{
		$sort = $this->post('sort',array());
		foreach ($sort as $index=>$pid)
		{
			$this->model('product_top')->where('pid=?',[$pid])->limit(1)->update('sort',$index);
		}
		return new json(json::OK);
	}

	/**
	 * 保存商品信息
	 */
	function save()
	{
		$product = new \application\entity\product();
		$data = $this->post();
		
		if ($data['outside']==2)
		{
			$data['postTaxNo'] = NULL;
		}
		else if ($data['outside']==3)
		{
			$data['ztax'] = NULL;
		}
		else
		{
			$data['postTaxNo'] = NULL;
			$data['ztax'] = NULL;
		}
		
		$product->setData($data);
		if ($product->validate())
		{
			if($product->save())
			{
				$this->model("admin_log")->insertlog($this->_aid, '商品管理，保存商品成功，商品id：' . $product->getPrimaryKey(), 1);
				//切换商品的供应商等信息
				$productHelper = new \application\helper\product();
				$productHelper->cutPublish($product->getPrimaryKey());
				//创建商品的索引
				$this->call('search', 'rebuild',['id'=>$product->getPrimaryKey()]);
				return new json(json::OK, NULL, $product->getPrimaryKey());
			}
			else
			{
				return new json(json::PARAMETER_ERROR, '添加失败');
			}
		}
		else
		{
			return new json(json::PARAMETER_ERROR, $product->getErrors());
		}
	}

	/**
	 * 删除商品
	 * 
	 * @return \application\message\json
	 */
	function remove()
	{
		$id = $this->post('id');
		if (!empty($id))
		{
			if ($this->model('product')->where('id=?', [$id])->update([
				'isdelete' => 1,
				'deletetime' => $_SERVER['REQUEST_TIME']
			]))
			{
				$this->model("admin_log")->insertlog($this->_aid, '商品管理，删除商品成功，商品id：' . $id, 1);
				return new json(json::OK);
			}
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	/**
	 * 审核通过
	 */
	function examine_pass()
	{
		$id = $this->post('id');
		if (empty($id))
		{
			return new json(json::PARAMETER_ERROR);
		}
		
		if($this->model('product')->where('id=?',[$id])->limit(1)->update([
			'examine'=>1,
			'status'=>1,
			'examine_time' => $_SERVER['REQUEST_TIME'],
		]))
		{
			$this->model("admin_log")->insertlog($this->_aid, '商品审核通过，商品id：' . $id, 1);
			return new json(json::OK);
		}
		else
		{
			return new json(json::PARAMETER_ERROR);
		}
	}
	
	/**
	 * 审核拒绝
	 */
	function examine_refuse()
	{
		$id = $this->post('id');
		$result = $this->post('result','','htmlspecialchars');
		$description = $this->post('description','','','htmlspecialchars');
		if (empty($id))
		{
			return new json(json::PARAMETER_ERROR);
		}
		if($this->model('product')->where('id=?',[$id])->limit(1)->update([
			'examine' => -1,
			'examine_result' => $result,
			'examine_description' => $description,
			'examine_time' => $_SERVER['REQUEST_TIME']
		]))
		{
			$this->model("admin_log")->insertlog($this->_aid, '商品审核拒绝，商品id：' . $id, 1);
			return new json(json::OK);
		}
		else
		{
			return new json(json::PARAMETER_ERROR);
		}
	}

	/**
	 * 恢复商品从删除状态到未删除状态
	 * @return \application\message\json
	 */
	function restore()
	{
		$id = $this->post('id');
		if ($this->model('product')->where('id=?', [$id])->limit(1)->update('isdelete', 0))
		{
			$this->model("admin_log")->insertlog($this->_aid, '商品管理，回收站商品恢复，商品id：' . $id, 1);
			return new json(json::OK);
		}
		else
		{
			return new json(json::PARAMETER_ERROR);
		}
	}
	
	/**
	 * 读取导入文件的信息
	 */
	private function importer($file_name,$start,$to)
	{
		$Ein_t = array(
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
		$Ein = [];
		$start_flag = false;
		foreach ($Ein_t as $v)
		{
			if ($v == $start)
			{
				$start_flag = true;
			}
			
			if ($start_flag)
			{
				$Ein[] = $v;
			}
			
			if ($v == $to)
			{
				break;
			}
		}
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
		if (isset($_FILES[$file_name]))
		{
			$file = $this->file->receive($_FILES[$file_name], $config);
			if (is_file($file))
			{
				$phpexcel_root = ROOT . '/extends/PHPExcel';
				include_once $phpexcel_root . '/PHPExcel/IOFactory.php';
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
					return $products;
				}
			}
		}
		return false;
	}
	
	/**
	 * 商品导入基本信息
	 */
	function import_base()
	{
		$adminHelper = new admin();
		$aid = $adminHelper->getAdminId();
		if (empty($aid))
		{
			return new json(json::NOT_LOGIN);
		}
		$roleModel = $this->model('role');
		if ($roleModel->checkPower($adminHelper->getGroupId(),'product',roleModel::POWER_UPDATE))
		{
			$data = $this->importer('file_base','A','J');
			if (!empty($data))
			{
				$success_base = [];
				$failed_base = [];
				foreach ($data as $d)
				{
					if(!$this->emptyArray($d))
					{
						list($a,$id,$category,$outside_ch,$name,$brand,$fee,$freeFee,$short_description) = $d;
						
						$id = $id*1;
						if ($freeFee)
						{
							$fee = 0;
						}
						switch ($outside_ch)
						{
							case '普通商品':$outside = 0;break;
							case '进口商品':$outside = 1;break;
							case '直供商品':$outside = 2;break;
							case '直邮商品':$outside = 3;break;
							default:$outside = 0;
						}
						
						
						$productHelper = new \application\helper\product();
						$product = $productHelper->createProductData(array(
							//'id' => $id,
							'name' => $name,
							'outside' => $outside,
							'fee' => $fee,
							'short_description' => $short_description,
						));
						
						if (empty($id))
						{
							if ($this->model('product')->insert($product))
							{
								$success_base[] = [
									'a'=>$a,
									'id' => $id,
									'category'=>$category,
									'outside' => $outside_ch,
									'name' => $name,
									'brand' => $brand,
									'fee'=>$fee,
									'freeFee'=>$freeFee,
									'short_description' => $short_description,
								];
							}
							else
							{
								$failed_base[] = [
									'a'=>$a,
									'id' => $id,
									'category'=>$category,
									'outside' => $outside_ch,
									'name' => $name,
									'brand' => $brand,
									'fee'=>$fee,
									'freeFee'=>$freeFee,
									'short_description' => $short_description,
								];
							}
						}
						else
						{
							if($this->model('product')->where('id=?',[$id])->limit(1)->update(array(
								'name' => $name,
								'outside' => $outside,
								'fee' => $fee,
								'short_description' => $short_description,
								'modifytime' => $_SERVER['REQUEST_TIME']
							)))
							{
								$success_base[] = [
									'a'=>$a,
									'id' => $id,
									'category'=>$category,
									'outside' => $outside_ch,
									'name' => $name,
									'brand' => $brand,
									'fee'=>$fee,
									'freeFee'=>$freeFee,
									'short_description' => $short_description,
								];
							}
							else
							{
								$failed_base[] = [
									'a'=>$a,
									'id' => $id,
									'category'=>$category,
									'outside' => $outside_ch,
									'name' => $name,
									'brand' => $brand,
									'fee'=>$fee,
									'freeFee'=>$freeFee,
									'short_description' => $short_description,
								];
							}
						}
					}
				}
			}
			
			$need_conflict_product_id = [];
			
			$data = $this->importer('file_stock', 'A', 'I');
			if (!empty($data))
			{
				$checked_product_id = [];
				$success_stock = [];
				$failed_stock = [];
				foreach ($data as $d)
				{
					if (!$this->emptyArray($d))
					{
						list($id,$sku,$name,$publish,$store,$barcode,$MeasurementUnit,$current_stock,$next_stock) = $d;
						
						$product_id = $id*1;
						$publish_id = $this->model('publish')->where('name=?',array($publish))->scalar('id');
						$store_id = $this->model('store')->where('name=?',array($store))->scalar('id');
						$stock = intval($next_stock);
						
						$MeasurementUnit_id = $this->model('dictionary')->where('name=? and type=?',[$MeasurementUnit,'MeasurementUnit'])->scalar('id');
						
						//只执行一次的代码
						if (!in_array($id, $checked_product_id))
						{
							if (!in_array($product_id, $need_conflict_product_id))
							{
								$need_conflict_product_id[] = $product_id;
							}
							$checked_product_id[] = $id;
							//删除原来的信息
							$this->model('product_publish')->where('product_id=? and publish_id=?',[$product_id,$publish_id])->delete();
							
							$this->model('product')->where('id=?',[$product_id])->limit(1)->update(array(
								'modifytime' => $_SERVER['REQUEST_TIME'],
								'barcode' => $barcode,
								'MeasurementUnit' => $MeasurementUnit_id,
							));
						}
						
						//执行多次的代码
						if($this->model('product_publish')
						->insert(array(
							'product_id' => $product_id,
							'publish_id' => $publish_id,
							'stock' => $stock,
							'sku' => $sku,
							'store' => $store_id,
						)))
						{
							$success_stock[] = array(
								'id'=>$id,
								'sku'=>$sku,
								'name'=>$name,
								'publish'=>$publish,
								'store' => $store,
								'barcode' => $barcode,
								'MeasurementUnit' => $MeasurementUnit,
								'current_stock' => $current_stock,
								'next_stock' => $next_stock,
							);
						}
						else
						{
							$failed_stock[] = array(
								'id'=>$id,
								'sku'=>$sku,
								'name'=>$name,
								'publish'=>$publish,
								'store' => $store,
								'barcode' => $barcode,
								'MeasurementUnit' => $MeasurementUnit,
								'current_stock' => $current_stock,
								'next_stock' => $next_stock,
							);
						}
					}
				}
			}
			
			$data = $this->importer('file_price', 'A', 'L');
			if (!empty($data))
			{
				$success_price = [];
				$failed_price = [];
				$checked_product_publish = [];
				foreach ($data as $d)
				{
					if (!$this->emptyArray($d))
					{
						list($id,$sku,$name,$publish,$store,$barcode,$selled,$inprice,$oldprice,$price,$v1price,$v2price) = $d;
						$inprice *= 1;
						$oldprice *= 1;
						$price *= 1;
						$v1price *= 1;
						$v2price *= 1;
						
						$product_id = $id*1;
						$publish_id = $this->model('publish')->where('name=?',[$publish])->scalar('id');
						$store_id = $store;
						
						
						if (!in_array([
							'product_id' => $product_id,
							'publish_id' => $publish_id,
						], $checked_product_publish))
						{
							$checked_product_publish[] = [
								'product_id' => $product_id,
								'publish_id' => $publish_id,
							];
							
							if (!in_array($product_id, $need_conflict_product_id))
							{
								$need_conflict_product_id[] = $product_id;
							}
							
							$this->model('product_publish_price')->where('product_id=? and publish_id=?',[$product_id,$publish_id])->delete();
						}
						
						if($this->model('product_publish_price')->insert(array(
							'product_id' => $product_id,
							'publish_id' => $publish_id,
							'num' => $selled,
							'inprice' => $inprice,
							'price' => $price,
							'v1price' => $v1price,
							'v2price' => $v2price,
							'oldprice' => $oldprice,
						)))
						{
							$success_price[] = [
								'id' => $id,
								'sku' => $sku,
								'name' => $name,
								'publish'=>$publish,
								'store' => $store,
								'barcode' => $barcode,
								'selled' => $selled,
								'inprice' => $inprice,
								'oldprice' => $oldprice,
								'price' => $price,
								'v1price' => $v1price,
								'v2price' => $v2price,
							];
						}
						else
						{
							$failed_price[] = [
								'id' => $id,
								'sku' => $sku,
								'name' => $name,
								'publish'=>$publish,
								'store' => $store,
								'barcode' => $barcode,
								'selled' => $selled,
								'inprice' => $inprice,
								'oldprice' => $oldprice,
								'price' => $price,
								'v1price' => $v1price,
								'v2price' => $v2price,
							];
						}
					}
				}
			}
			
			//调整商品价格
			$productHelper = new \application\helper\product();
			foreach ($need_conflict_product_id as $product_id)
			{
				$productHelper->cutPublish($product_id);
			}
			
			return new json(json::OK,NULL,[
				'success_base' => $success_base,
				'failed_base' => $failed_base,
				'success_stock' => $success_stock,
				'failed_stock' => $failed_stock,
				'success_price' => $success_price,
				'failed_price' => $failed_price,
			]);
		}
		else
		{
			return new json(json::NO_POWER);
		}
	}
    
    private function emptyArray($array)
    {
    	foreach ($array as $a)
    	{
    		if (!empty(trim($a))) {
    			return false;
    		}
    	}
    	return true;
    }
    
    
    function __access()
    {
    	$adminHelper = new \application\helper\admin();
    	$this->_aid = $adminHelper->getAdminId();
    	return array(
    		array(
    			'allow',
    			'actions' => ['create','topmove','untop','top','top_sort','save','remove','examine_pass','examine_refuse','restore'],
    			'express' => empty($this->_aid),
    			'message' => new json(json::NOT_LOGIN,'请重新登录'),
    			'httpCode' => 200,
    		),
    		array(
    			'allow',
    			'actions' => '*',
    		)
    	);
    }
}
