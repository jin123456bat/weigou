<?php
namespace application\control\view;
use system\core\view;
use application\model\roleModel;
class admin extends view
{
	private $_power = [
		'nocheck' => [	//不检查权限的页面
			'login',
			'dashboard'
		],
		'forward' => [
			'orderdetail' => 'order',//  orderdetail页面按照order页面的权限
			'refund' => 'order',
			
			'couponno' => 'coupon',
			'advise' => 'page',
			'role' => 'system',
			'dictionary' => 'system',
			
			'recycle' => 'product',
			'create_product' => 'product',
			'edit_product' => 'product',
			'product_top' => 'product',
			
			'editPage' => 'page',
			'createPage'=> 'page',
			'question' => 'page',
			'create_question' => 'page',
			'edit_question' => 'page',
			
			'viporder' => 'user',
			
			'create_college' => 'college',
			'edit_college' => 'college',
			'file' => 'system',
		],
	];
	
	function __construct()
	{
		parent::__construct();
		
		$action = $this->get('a');
		if (!in_array($action, $this->_power['nocheck'],true))
		{
			if (isset($this->_power['forward'][$action]))
				$action = $this->_power['forward'][$action];
			$adminHelper = new \application\helper\admin();
			$role = $adminHelper->getGroupId();
			if(!$this->model('role')->checkPower($role,$action,roleModel::POWER_ALL))
			{
				$this->setViewname('nopower');
			}
		}
	}
	
	function create_question()
	{
		$category = $this->model('question_category')->where('isdelete=?',[0])->select();
		$this->assign('category', $category);
		return $this;
	}
	
	function question()
	{
		$category = $this->model('question_category')->where('isdelete=?',[0])->orderby('sort','asc')->orderby('id','desc')->select();
		foreach ($category as &$c)
		{
			$question = $this->model('question')->where('isdelete=? and cid=?',[0,$c['id']])->orderby('sort','asc')->orderby('id','desc')->select();
			$c['question'] = $question;
		}
		$this->assign('category', $category);
		return $this;
	}
	
	function edit_question()
	{
		$id = $this->get('id',0,'intval');
		$question = $this->model('question')->where('isdelete=? and id=?',[0,$id])->find();
		if (!empty($question))
		{
			$this->assign('question', $question);
			
			$category = $this->model('question_category')->where('isdelete=?',[0])->select();
			$this->assign('category', $category);
			
			return $this;
		}
	}
	
	function file()
	{
		$start = $this->get('start',0);
		$length = $this->get('length',12*5);
		$file = $this->model('upload')->limit($start,$length)->orderby('time','desc')->select();
		$this->assign('file', $file);
		
		$total = $this->model('upload')->find('count(*)');
		$this->assign('total', $total['count(*)']);
		
		return $this;
	}
	
	function orderdetail()
	{
		$orderno = $this->get('orderno');
		$order = $this->model('order')->where('orderno=?',[$orderno])->find();
		if (!empty($order))
		{
			$this->assign('order', $order);
			
			$user = $this->model('user')->where('id=?',[$order['uid']])->find();
			$this->assign('user', $user);
			
			if (!empty($user['oid']))
			{
				$o_user = $this->model('user')->where('id=?',[$user['oid']])->find();
				$this->assign('o_user', $o_user);
			}
			
			if (!empty($user['o_master']))
			{
				$o_master = $this->model('user')->where('id=?',[$user['o_master']])->find();
				$this->assign('o_master', $o_master);
			}
			
			$address = $this->model('address')
			->table('province','left join','province.id=address.province')
			->table('city','left join','city.id=address.city')
			->table('county','left join','county.id=address.county')
			->where('address.id=?',[$order['address']])
			->find([
				'address.id,
				address.name,
				address.telephone,
				address.zcode,
				address.identify,
				address.host,
				address.address,
				province.id as province_id,
				city.id as city_id,
				county.id as county_id,
				province.name as province,
				city.name as city,
				county.name as county'
			]);
			$this->assign('address', $address);
			
			$product = $this->model('order_package')
			->table('order_product','left join','order_product.package_id=order_package.id')
			->table('product','left join','product.id=order_product.pid')
			->table('suborder_store','left join','suborder_store.main_orderno=order_package.orderno')
			->where('order_package.orderno=?',[$orderno])
			->select([
				'product.*',
				'order_product.num',
				'(order_product.num*product.selled) as truenum',
				'order_product.content',
				'order_product.price as order_price',
				'order_product.id as order_product_id',
				'order_product.refund',
				'order_product.refundmoney',
				'if(suborder_store.erp=1,"已推送","未推送") as erp'
			]);
			
		
			
			$product_total_num = 0;
			
			foreach ($product as &$p)
			{
				if (!empty($p['content']))
				{
					$collection = $this->model('collection')->where('pid=? and content=? and isdelete=?',[$p['id'],$p['content'],0])->find();
					if (!empty($collection))
					{
						$p['price'] = $collection['price'];
						$p['v1price'] = $collection['v1price'];
						$p['v2price'] = $collection['v2price'];
					}
				}
				
				$product_total_num += $p['num'];
			}
			$this->assign('product_total_num', $product_total_num);
			$this->assign('product', $product);
			$this->assign('ship', $this->model('ship')->select());
			
			$log = $this->model('order_log')->where('orderno=?',[$orderno])->select();
			$this->assign('log', $log);
			return $this;
		}
	}
	
	function publish()
	{
		$this->assign('publish', $this->model('publish')->where('isdelete=?',[0])->select());
		return $this;
	}
	
	function taskorder()
	{
		$province = $this->model('province')->select();
		$this->assign('province', $province);
		
		return $this;
	}
	
	function task()
	{
		$filter = [
			'isdelete' => 0,
			'sort' => ['task.sort','asc'],
			'parameter' => [
				'task.id',
				'product.name',
				'task.price',
				'task.teamnum',
				'task.score',
				'task.sort',
				'task.day',
			],
		];
		$task = $this->model('task')->fetchAll($filter);
		
		$this->assign('task', $task);
		return $this;
	}
	
	function order()
	{
		$province = $this->model('province')->select();
		$this->assign('province', $province);
		
		return $this;
	}
	
	function dictionary()
	{
		$country = $this->model('country')->table('upload','left join','upload.id=country.logo')->select('country.*,upload.path as logo');
		
		$this->assign('country', $country);
		return $this;
	}
	
	function drawal()
	{
		$this->assign('province', $this->model('province')->select());
		return $this;
	}
	
	function system()
	{
		$system = $this->model('system')->select();
		$this->assign('system', $system);
		return $this;
	}
	
	function admin()
	{
		$admin = $this->model('admin')
		->table('role','left join','role.id=admin.role')
		->select([
			'admin.*',
			'role.name as role',
			'role.id as role_id',
		]);
		$this->assign('admin', $admin);
		
		$this->assign('role', $this->model('role')->select());
		
		return $this;
	}
	
	function theme()
	{
		$theme = $this->model('theme')->table('upload','left join','upload.id=theme.logo')->where('isdelete=?',[0])->select([
			'theme.id',
			'theme.title',
			'upload.path as logo',
			'theme.logo as logo_id'
		]);
		$this->assign('theme', $theme);
		
		$subtheme = $this->model('subtheme')->table('theme','left join','theme.id=subtheme.theme_id')->orderby('subtheme.sort','asc')->select('subtheme.*');
		$this->assign('subtheme', $subtheme);
		
		$product = $this->model('subtheme')->table('subtheme_product','left join','subtheme.id=subtheme_product.subtheme_id')->table('product','left join','product.id=subtheme_product.product_id')->select('subtheme_product.subtheme_id,subtheme_product.product_id,product.name');
		$this->assign('product', $product);
		
		return $this;
	}
	
	
	function editPage()
	{
		$id = $this->get('id');
		$page = $this->model('page')->where('id=?',[$id])->find();
		$this->assign('page', $page);
		return $this;
	}
	
	function page()
	{
		$page = $this->model('page')->where('isdelete=?',[0])->select();
		$this->assign('page', $page);
		return $this;
	}
	
	function carousel()
	{
		$carousel = $this->model('carousel')->table('upload','left join','upload.id=carousel.logo')->orderby('carousel.sort','asc')->where('carousel.isdelete=?',[0])->select([
			'carousel.*',
			'upload.path as logo'
		]);
		$this->assign('carousel', $carousel);
		
		$position = $this->model('carousel')->groupby('position')->orderby('position')->select('position');
		$this->assign('position', $position);
		
		return $this;
	}
	
	/**
	 * 导师管理
	 * @return \application\control\view\admin
	 */
	function teacher()
	{
		$teacher = $this->model('teacher')->table('user','left join','user.id=teacher.uid')->orderby('teacher.sort','asc')->select('user.name,teacher.uid,teacher.sort,teacher.turn');
		$this->assign('teacher', $teacher);
		return $this;
	}
	
	
	/**
	 * 分类管理页面
	 * @return \application\control\view\admin
	 */
	function category()
	{
		$filter = [
			'isdelete' => 0,
			'sort'=>['sort','asc'],
			'parameter' => 'category.id,
							category.name,
							upload.path as logo,
							category.description,
							category.sort,
							c_category.name as c_name,
							c_category.id as cid',
		];
		$category = $this->model('category')->fetchAll($filter);
		$this->assign('category', $category);
		return $this;
	}
	
	/**
	 * 编辑课程
	 */
	function edit_college()
	{
		$id = $this->get('id');
		$college = $this->model('college')->table('user','left join','user.id=college.uid')->where('college.id=?',[$id])->find([
			'college.*',
			'user.name as uname'
		]);
		$this->assign('college', $college);
		return $this;
	}
	
	/**
	 * 创建商品页面
	 * @return \application\control\view\admin
	 */
	function create_product()
	{
		$filter = [
			'isdelete' => 0,
			'sort' => ['category.sort','asc'],
			'parameter' => 'category.id,category.name,category.cid'
		];
		$category = $this->model('category')->fetchAll($filter);
		$this->assign('category', $category);
		
		
		$filter = [
			'isdelete' => 0,
			'parameter' => 'store.id,store.name',
		];
		$store = $this->model('store')->fetch($filter);
		$this->assign('store', $store);
		
		$postTaxNo = $this->model('posttaxno')->select();
		$this->assign('postTaxNo', $postTaxNo);
		
		$package = $this->model('dictionary')->where('type=?',['package'])->select();
		$this->assign('package', $package);
		
		$MeasurementUnit = $this->model('dictionary')->where('type=?',['MeasurementUnit'])->select();
		$this->assign('MeasurementUnit', $MeasurementUnit);
		
		$origin = $this->model('country')->where('isdelete=?',[0])->select();
		$this->assign('origin', $origin);
		
		$currency = $this->model('dictionary')->where('type=?',['currency'])->select();
		$this->assign('currency', $currency);
		
		$publish = $this->model('publish')->where('isdelete=?',[0])->select();
		$this->assign('publish', $publish);
		
		$this->assign('province', $this->model('province')->select());
		
		$this->assign('ztax', $this->model('tax')->select());
		
		return $this;
	}
	
	/**
	 * 优惠券管理
	 * @return \application\control\view\admin
	 */
	function coupon()
	{
		
		return $this;
	}
	
	/**
	 * 仓库管理
	 * @return \application\control\view\admin
	 */
	function store()
	{
		$filter = [
			'isdelete' => 0,
		];
		$store = $this->model('store')->fetchAll($filter);
		$this->assign('store', $store);
		return $this;
	}
	
	/**
	 * 用户管理界面
	 * @return \application\control\view\admin
	 */
	function user()
	{
		$this->assign('source', $this->model('source')->select());
		return $this;
	}
	
	/**
	 * 商品回收站
	 */
	function recycle()
	{
		$filter = [
			'isdelete' => 0,
			'sort' => ['category.sort','asc'],
			'parameter' => 'category.id,category.name'
		];
		$category = $this->model('category')->fetchAll($filter);
		$this->assign('category', $category);
	
		$filter = [
			'isdelete' => 0,
			'parameter' => 'store.id,store.name',
		];
		$store = $this->model('store')->fetchAll($filter);
		$this->assign('store', $store);
		return $this;
	}
	
	/**
	 * 商品列表页面
	 */
	function product()
	{
		$filter = [
			'isdelete' => 0,
			'sort' => ['category.sort','asc'],
			'parameter' => 'category.id,category.name'
		];
		$category = $this->model('category')->fetchAll($filter);
		$this->assign('category', $category);
		
		$filter = [
			'isdelete' => 0,
			'parameter' => 'store.id,store.name',
		];
		$store = $this->model('store')->fetchAll($filter);
		$this->assign('store', $store);
		return $this;
	}
	
	/**
	 * 编辑商品页面
	 * @return \application\control\view\admin
	 */
	function edit_product()
	{
		$id = $this->get('id');
		
		$product = $this->model('product')->where('id=?',[$id])->find();
		if (!empty($product))
		{
			$this->assign('product', $product);
			
			$filter = [
				'pid' => $id,
				'isdelete' => 0,
				'parameter' => 'category.id',
			];
			$product_category = $this->model('category')->fetchAll($filter);
			$this->assign('product_category', $product_category);
			
			
			$filter = [
				'pid' => $id,
				'isdelete' => 0,
			];
			$product_prototype = $this->model('prototype')->fetch($filter);
			$this->assign('product_prototype', $product_prototype);
			
			
			$filter = [
				'pid' => $id,
				'isdelete' => 0,
				'parameter' => [
					'collection.content',
					'collection.price',
					'collection.v1price',
					'collection.v2price',
					'collection.stock',
					'collection.sku',
					'upload.path as logo',
					'collection.logo as logo_id',
					'collection.available',
				]
			];
			$product_collection = $this->model('collection')->fetchAll($filter);
			$this->assign('product_collection', $product_collection);
			
			$filter = [
				'isdelete' => 0,
				'pid' => $id,
				'sort' => ['sort','asc'],
				'parameter' => [
					'product_img.fid as id',
					'upload.path',
					'upload.name',
					'product_img.sort',
					'product_img.position',
				]
			];
			$product_image = $this->model('product_img')->fetchAll($filter);
			$this->assign('product_image', $product_image);
			
			$filter = [
				'isdelete' => 0,
				'sort' => ['category.sort','asc'],
				'parameter' => 'category.id,category.name,category.cid'
			];
			$category = $this->model('category')->fetchAll($filter);
			$this->assign('category', $category);
			
			
			$filter = [
				'isdelete' => 0,
				'parameter' => 'store.id,store.name',
			];
			$store = $this->model('store')->fetchAll($filter);
			$this->assign('store', $store);
			
			$postTaxNo = $this->model('posttaxno')->select();
			$this->assign('postTaxNo', $postTaxNo);
			
			$package = $this->model('dictionary')->where('type=?',['package'])->select();
			$this->assign('package', $package);
			
			$MeasurementUnit = $this->model('dictionary')->where('type=?',['MeasurementUnit'])->select();
			$this->assign('MeasurementUnit', $MeasurementUnit);
			
			$origin = $this->model('country')->where('isdelete=?',[0])->select();
			$this->assign('origin', $origin);
			
			$currency = $this->model('dictionary')->where('type=?',['currency'])->select();
			$this->assign('currency', $currency);
			
			$publish = $this->model('publish')->where('isdelete=?',[0])->select();
			$this->assign('publish', $publish);
			
			$this->assign('province', $this->model('province')->select());
			
			$product_province = $this->model('product_province')->where('product_id=?',[$id])->select();
			$province_temp = [];
			foreach ($product_province as $province)
			{
				$province_temp[] = $province['province_id'];
			}
			$this->assign('product_province', $province_temp);
			
			$this->assign('ztax', $this->model('tax')->select());
			
			return $this;
		}
	}
	
	function advise()
	{
		$advise = $this->model('advise')->orderby('sort','asc')->where('isdelete=?',[0])->select([
			'advise.*',
			'(select count(*) from advise_user where advise_user.aid=advise.id) as num'
		]);
		
		$this->assign('advise', $advise);
		return $this;
	}
	
	function product_top()
	{
		$filter = [
			'isdelete' => 0,
			'sort' => ['product_top.sort','asc'],
			'parameter' => [
				'product_top.pid',
				'product.name',
				'product_top.sort',
			],
		];
		$product = $this->model('product_top')->fetchAll($filter);
		$this->assign('product', $product);
		return $this;
	}
	
	function role()
	{
		$role = $this->model('role')->select();
		$this->assign('role', $role);
		return $this;
	}
	
	function logout()
	{
		session_destroy();
		$this->response->setCode(302);
		$this->response->addHeader('Location',$this->http->url('','admin','login'));
	}
	
	function source()
	{
		$source = $this->model('source')->where('isdelete=? and u_source is ? and type=0',[0,NULL])->select();
		
		$source2 = $this->model('source')->where('isdelete=? and u_source is not ? and type=0',[0,NULL])->select();
		
		$this->assign('source', $source);
		$this->assign('source2', $source2);
		return $this;
	}
	function source2()
	{
		$source = $this->model('source')->where('isdelete=? and type =1',[0])->select();
		$this->assign('source', $source);
		return $this;
	}
}