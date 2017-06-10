<?php
namespace application\control\view;

use system\core\view;
use system\core\image;
use application\helper\product;
use application\helper\user;

class index extends view
{
	function __construct()
	{
		$this->_csrf_token_refresh = false;
		parent::__construct();
	}
	
	function abc()
	{
		echo "abc";
	}

	function index()
	{
		$this->response->setCode(302);
		if ($this->http->isMobile())
		{
			$this->response->addHeader('Location', $this->http->url('', 'mobile', 'index'));
		}
		else
		{
			$this->response->addHeader('Location', 'http://willg.cn');
		}
	}
	
	function product()
	{
		$userHelper = new \application\helper\user();
		if($userHelper->isLogin())
		{
			$user = $this->model('user')->where('id=?',[$userHelper->isLogin()])->find();
			$this->assign('user', $user);
		}
		
		$id = $this->get('id',0,'intval');
		$product = $this->model('product')->where('id=?',[$id])->find();
		$productHelper = new \application\helper\product();
		$product['origin'] = $this->model('country')->get($product['origin']);
		$product['store'] = $this->model('store')->where('id=?',[$product['store']])->find();
		// 商品详情图
		$product['listImage'] = $productHelper->getListImage($id);
		$product['detailImage'] = $productHelper->getDetailImage($id);
		$product['tax'] = $productHelper->getTaxFields($id);
		$product['MeasurementUnit'] = $this->model('dictionary')->where('id=? and type=?',[$product['MeasurementUnit'],'MeasurementUnit'])->scalar('name');
		$product['package'] = $this->model('dictionary')->where('id=? and type=?',[$product['package'],'package'])->scalar('name');
		$this->assign('product', $product);
		
		$prototype = $this->model('prototype')->where('pid=? and type=? and isdelete=?',[$id,'text',0])->select();
		$this->assign('prototype', $prototype);
		
		$bind = $this->model('bind')->orderby('sort','asc')->where('pid=?',[$id])->select();
		$this->assign('bind', $bind);
		
		$this->assign('province', $this->model('province')->select());
		return $this;
	}
	
	/**
	 * 移动端地址二维码
	 */
	function mobileAddressEqcode()
	{
		$content = 'http://twillg.com/index.php?c=mobile&a=index';
		
		$image = new image();
		$size = empty($this->get->size) ? 4 : intval($this->get->size);
		$blank = empty($this->get->blank) ? 2 : intval($this->get->blank);
		
		//$logo = $this->model('system')->get('logo', 'system');
		//$logo = is_file($logo) ? $logo : NULL;
		$logo = ROOT.'/logo_small.jpg';
		
		$file = $image->QRCode($content, $logo, 'M', $size, $blank);
		$this->response->addHeader('Content-Type', 'image/png');
		if ($this->get->download == 'true') {
			$this->response->addHeader('Content-Disposition', 'attachment; filename="eqcode.png"');
		}
		return file_get_contents($file);
	}

	/**
	 * 图形验证码
	 */
	function code()
	{
		$image = new image();
		$image->code();
	}

	function clearCookieAndSession()
	{
		session_destroy();
		setcookie('source_time', NULL, $_SERVER['REQUEST_TIME'] - 3600, NULL, 'twillg.com');
		setcookie('source_time', NULL, $_SERVER['REQUEST_TIME'] - 3600, NULL, 'www.twillg.com');
		$this->response->setCode(302);
		$this->response->addHeader('Location', $this->http->referer());
	}

	function downloadApp()
	{
		$this->response->setCode(302);
		$this->response->addHeader('Location', 'http://a.app.qq.com/o/simple.jsp?pkgname=com.lianhai.MicroBuy');
	}

	function __404()
	{
		return "404";
	}
	
	/**
	 * 修复订单表中erp字段错误的脚本
	 */
	function erpError()
	{
		$orders = $this->model('order')->select();
		
		foreach ($orders as $order)
		{
			$sending_num = $this->model('suborder_store')->where('main_orderno=? and erp=?',[$order['orderno'],1])->count();
			$not_sending_num = $this->model('suborder_store')->where('main_orderno=? and erp=?',[$order['orderno'],0])->count();
			if ($sending_num>0 && $not_sending_num==0)
			{
				$this->model('order')->where('orderno=?',[$order['orderno']])->limit(1)->update([
					'erp'=>1
				]);
			}
			if ($sending_num==0 && $not_sending_num>0)
			{
				$this->model('order')->where('orderno=?',[$order['orderno']])->limit(1)->update([
					'erp'=>0
				]);
			}
			if ($sending_num>0 && $not_sending_num>0)
			{
				$this->model('order')->where('orderno=?',[$order['orderno']])->limit(1)->update([
					'erp'=>2
				]);
			}
		}
	}
	
	/**
	 * 升级脚本
	 */
	function upgrade()
	{
		$this->model('order')->transaction();
			$sql = [];
			
			$sql[] = '
DROP TABLE IF EXISTS `admin_fields`;
CREATE TABLE IF NOT EXISTS `admin_fields` (
  `aid` int(11) NOT NULL,
  `field` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
				';
			$sql[] = 'DROP TABLE IF EXISTS `admin_menu`;
CREATE TABLE IF NOT EXISTS `admin_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `link` varchar(32) DEFAULT NULL COMMENT \'英文名，1级菜单可以为空\',
  `name` varchar(32) NOT NULL COMMENT \'中文名\',
  `sort` int(11) NOT NULL COMMENT \'排序，从小到大\',
  `type` int(11) NOT NULL DEFAULT \'1\' COMMENT \'种类，0，平台，1商城\',
  `extra` text NOT NULL COMMENT \'逗号分开的其他link名，对2级有效\',
  `host` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'是否是默认的，2级菜单专用\',
  `u_link` varchar(32) DEFAULT NULL COMMENT \'上级菜单，2级菜单专用\',
  `display` tinyint(1) NOT NULL DEFAULT \'1\' COMMENT \'是否显示，1显示，0隐藏\',
  PRIMARY KEY (`id`),
  KEY `link` (`link`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=62 ;';
			$sql[] = "
INSERT INTO `admin_menu` (`id`, `link`, `name`, `sort`, `type`, `extra`, `host`, `u_link`, `display`) VALUES
(1, NULL, '商品管理', 1, 1, '', 0, NULL, 1),
(2, NULL, '促销', 2, 1, '', 0, NULL, 1),
(3, NULL, '订单管理', 3, 1, '', 0, NULL, 1),
(4, NULL, '库存/价格', 4, 1, '', 0, NULL, 1),
(5, NULL, '商品配置', 5, 1, '', 0, NULL, 1),
(6, 'product', '商品列表', 1, 1, '', 1, '1', 1),
(7, 'product_draft', '商品发布', 2, 1, '', 0, '1', 1),
(8, 'product_stock', '补货清单', 3, 1, '', 0, '1', 1),
(9, 'product_notice', '到货通知', 4, 1, '', 0, '1', 1),
(10, 'bcategory', '商品分类', 1, 1, '', 1, '5', 1),
(11, 'category', '前台类目', 2, 1, '', 1, '5', 1),
(12, 'brand', '商品品牌', 3, 1, '', 1, '5', 1),
(13, 'publish_store', '供货商/仓库', 4, 1, '', 1, '5', 1),
(14, NULL, '国家字典', 6, 1, '', 1, '5', 1),
(15, 'create_brand', '添加品牌', 0, 1, 'brand', 0, '12', 0),
(16, 'edit_brand', '编辑品牌', 1, 1, 'brand', 0, '12', 0),
(17, 'product_create', '商品编辑', 1, 1, 'product_draft', 0, '7', 0),
(18, 'create_product_notice_template', '添加短信模板', 1, 1, 'product_notice', 0, '9', 0),
(19, 'edit_product_notice_template', '编辑 / 查看短信模板', 1, 1, 'product_notice', 0, '9', 0),
(20, 'product_top', '首页商品', 1, 1, '', 1, '2', 1),
(21, 'task', '团购活动', 3, 1, '', 0, '2', 1),
(22, 'coupon', '优惠卷', 4, 1, '', 0, '2', 1),
(23, 'order', '订单列表', 1, 1, '', 1, '3', 1),
(24, 'order_send', '发货管理', 2, 1, '', 0, '3', 1),
(25, 'orderdetail', '订单详情', 1, 1, 'order', 0, '23', 0),
(26, 'stock_manager', '库存盘点', 1, 1, '', 1, '4', 1),
(28, 'price_manager', '价格盘点', 2, 1, '', 0, '4', 1),
(29, 'product_stock_manager', '商品库存盘点', 1, 1, 'stock_manager', 0, '26', 0),
(30, 'product_price_manager', '商品价格盘点', 1, 1, 'price_manager', 0, '28', 0),
(31, NULL, '用户管理', 1, 0, '', 1, NULL, 1),
(32, 'user', '会员管理', 1, 0, '', 1, '31', 1),
(33, 'stock_price_setting', '库存/价格/税率配置', 5, 1, '', 0, '5', 1),
(34, 'create_task', '添加团购活动', 1, 1, 'task', 1, '21', 0),
(35, NULL, '临保专区', 2, 1, '', 0, '2', 1),
(36, 'edit_task', '编辑团购活动', 1, 1, 'task', 1, '21', 0),
(37, 'product_edit', '商品编辑', 1, 1, 'product_draft', 0, '7', 0),
(38, NULL, '内容管理', 2, 0, '', 0, NULL, 1),
(39, NULL, '页面管理', 3, 0, '', 0, NULL, 1),
(40, NULL, '权限管理', 4, 0, '', 0, NULL, 1),
(41, NULL, '小功能', 5, 0, '', 0, NULL, 1),
(42, NULL, '系统设置', 6, 0, '', 0, NULL, 1),
(43, 'student', '学生管理', 2, 0, '', 0, '31', 1),
(44, 'user_order', '会员订单', 4, 0, '', 0, '31', 1),
(45, 'vip', '会员团队', 5, 0, '', 0, '31', 1),
(46, 'teacher', '导师设置', 6, 0, '', 0, '31', 1),
(47, 'drawal', '提现申请', 7, 0, '', 0, '31', 1),
(48, 'carousel', '轮播图设置', 1, 0, '', 1, '38', 1),
(49, 'admin', '账号列表', 1, 0, '', 1, '40', 1),
(50, 'role', '角色管理', 2, 0, '', 0, '40', 1),
(51, 'admin_create', '添加账号', 1, 0, 'admin', 0, '49', 0),
(52, 'article', '文章管理', 2, 0, '', 0, '38', 1),
(53, 'notice', '公告', 3, 0, '', 0, '38', 1),
(54, 'ucenter', '个人中心', 4, 0, '', 0, '38', 1),
(55, 'question', '常见问题', 5, 0, '', 0, '38', 1),
(56, 'message', '短信群发', 1, 0, '', 1, '41', 1),
(57, 'role_create', '添加角色', 0, 0, 'role', 0, '50', 0),
(58, 'userinfo', '用户详情', 1, 0, 'user', 0, '32', 0),
(59, 'role_edit', '编辑角色', 0, 0, 'role', 0, '50', 0),
(60, 'admin_edit', '编辑账号', 1, 0, 'admin', 0, '40', 0),
(61, 'nopower', '权限不足', 0, 1, '', 1, NULL, 0);";
			$sql[] = "
DROP TABLE IF EXISTS `admin_privileges`;
CREATE TABLE IF NOT EXISTS `admin_privileges` (
  `aid` int(11) NOT NULL COMMENT '管理员id',
  `pid` int(11) NOT NULL COMMENT '权限id',
  `type` enum('column','page','button') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='这个是管理员额外的权限表';
			";
			$sql[] = "DROP TABLE IF EXISTS `admin_role`;
CREATE TABLE IF NOT EXISTS `admin_role` (
  `aid` int(11) NOT NULL,
  `rid` int(11) NOT NULL,
  UNIQUE KEY `aid` (`aid`,`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			$sql[] = 'ALTER TABLE `admin` DROP FOREIGN KEY `admin_ibfk_1`;';
			$sql[] = 'ALTER TABLE `admin` DROP `role`;';
			$sql[] = 'ALTER TABLE `admin` ADD `realname` VARCHAR(12) NOT NULL COMMENT \'姓名\' , ADD `telephone` CHAR(11) NOT NULL COMMENT \'手机号\' , ADD `create_aid` INT NOT NULL COMMENT \'创建人id\' , ADD `create_time` INT NOT NULL COMMENT \'创建时间\' , ADD `status` BOOLEAN NOT NULL DEFAULT \'1\' COMMENT \'状态，1有效，0无效\' ;';
			
			$sql[] = "DROP TABLE IF EXISTS `bcategory`;
CREATE TABLE IF NOT EXISTS `bcategory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `bc_id` int(11) DEFAULT NULL COMMENT '上级分类id',
  `sort` int(11) NOT NULL DEFAULT '0',
  `stock_limit` int(11) NOT NULL DEFAULT '0',
  `price_v2` int(11) NOT NULL,
  `price_v1` int(11) NOT NULL,
  `price_v0` int(11) NOT NULL,
  `price_old` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bc_id` (`bc_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='后台分类表' AUTO_INCREMENT=34 ;";
			$sql[] = "DROP TABLE IF EXISTS `bcategory_product`;
CREATE TABLE IF NOT EXISTS `bcategory_product` (
  `bc_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  UNIQUE KEY `bc_id` (`bc_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			";
			$sql[] = "DROP TABLE IF EXISTS `brand`;
CREATE TABLE IF NOT EXISTS `brand` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `logo` int(11) NOT NULL COMMENT '图片',
  `name_cn` varchar(64) NOT NULL COMMENT '中文名',
  `name_en` varchar(64) NOT NULL COMMENT '英文名',
  `origin` int(11) NOT NULL COMMENT '国家id',
  `description` varchar(256) NOT NULL COMMENT '描述',
  `modifytime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '上一次修改的时间',
  PRIMARY KEY (`id`),
  KEY `logo` (`logo`),
  KEY `origin` (`origin`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16 ;";
			$sql[] = "DROP TABLE IF EXISTS `category_bcategory`;
CREATE TABLE IF NOT EXISTS `category_bcategory` (
  `category_id` int(11) NOT NULL,
  `bcategory_id` int(11) NOT NULL,
  UNIQUE KEY `category_id` (`category_id`,`bcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='前台分类和后台分类的对应关系表';";
			$sql[] = "DROP TABLE IF EXISTS `jstree_state`;
CREATE TABLE IF NOT EXISTS `jstree_state` (
  `id` int(11) NOT NULL,
  `node_id` varchar(32) NOT NULL,
  `type` varchar(32) NOT NULL COMMENT '目前只有2个地方有权限树，一个是角色，一个是管理员，这里只有2种值，role或者admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='这个表用来保存权限树的state';";
			$sql[] = "
DROP TABLE IF EXISTS `privileges`;
CREATE TABLE IF NOT EXISTS `privileges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '权限名称',
  `mid` int(11) DEFAULT NULL COMMENT 'admin_menu的id',
  `keyword` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `keyword` (`keyword`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=25 ;";
			$sql[] = "
INSERT INTO `privileges` (`id`, `name`, `mid`, `keyword`) VALUES
(1, '导出会员', 32, 'export_user'),
(2, '加入黑名单', 32, 'create_blacklist'),
(3, '黑名单页面', 32, 'blacklist'),
(4, '查看会员信息', 32, 'user_look'),
(5, '添加商品', 6, 'create_product_from_list'),
(6, '批量修改商品信息', 6, 'multi_modify_product'),
(7, '导出商品', 6, 'export_product'),
(8, '编辑商品', 6, 'edit_product'),
(9, '商品回收', 6, 'recycle_product'),
(10, '商品删除', 6, 'delete_product'),
(11, '商品上架', 6, 'up_product'),
(12, '商品下架', 6, 'down_product'),
(15, '商品创建', 7, 'create_product_from_draft'),
(16, '基础信息审核', 7, 'examine_base_product'),
(17, '库存信息审核', 7, 'examine_stock_product'),
(18, '价格信息审核', 7, 'examine_price_product'),
(19, '上架审核', 7, 'examine_up_product'),
(20, '短信模板', 9, 'sms_template'),
(21, '导出订单', 23, 'export_order'),
(22, '查看订单', 23, 'look_order'),
(23, '订单退款', 23, 'refund_order'),
(24, '订单发货', 23, 'send_order');
			";
			$sql[] = "CREATE TABLE IF NOT EXISTS `product_notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '商品id',
  `uid` int(11) NOT NULL COMMENT '用户id',
  `createtime` int(10) unsigned NOT NULL COMMENT '申请时间',
  `send` tinyint(1) NOT NULL COMMENT '是否已发送',
  `sendtime` int(10) unsigned NOT NULL COMMENT '发送时间',
  `content` text NOT NULL COMMENT '发送短信内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='商品到货通知' AUTO_INCREMENT=3 ;";
			$sql[] = "
CREATE TABLE IF NOT EXISTS `product_notice_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL COMMENT '管理员id',
  `title` varchar(64) NOT NULL COMMENT '标题',
  `content` varchar(256) NOT NULL COMMENT '内容',
  `createtime` int(10) unsigned NOT NULL COMMENT '创建时间',
  `modifytime` int(10) unsigned NOT NULL COMMENT '上次修改时间',
  `host` tinyint(1) NOT NULL COMMENT '是否是默认',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;
			";
			$sql[] = "
CREATE TABLE IF NOT EXISTS `product_publish` (
  `product_id` int(11) NOT NULL,
  `publish_id` int(11) NOT NULL,
  `stock` int(11) NOT NULL COMMENT '库存',
  `sku` varchar(32) NOT NULL COMMENT '供货商商品唯一编码',
  `store` int(11) NOT NULL COMMENT '发货仓库',
  UNIQUE KEY `product_id` (`product_id`,`publish_id`),
  KEY `store` (`store`),
  KEY `publish_id` (`publish_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			$sql[] = "
CREATE TABLE IF NOT EXISTS `product_publish_price` (
  `product_id` int(11) NOT NULL,
  `publish_id` int(11) NOT NULL,
  `num` int(11) NOT NULL COMMENT '销售数量',
  `oldprice` double(10,2) NOT NULL,
  `inprice` double(10,2) NOT NULL,
  `price` double(10,2) NOT NULL,
  `v1price` double(10,2) NOT NULL,
  `v2price` double(10,2) NOT NULL,
  UNIQUE KEY `product_id` (`product_id`,`publish_id`,`num`),
  KEY `publish_id` (`publish_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			";
			$sql[] = "
CREATE TABLE IF NOT EXISTS `role_privileges` (
  `rid` int(11) NOT NULL COMMENT '角色id',
  `pid` int(11) NOT NULL COMMENT '权限id',
  `type` enum('column','page','button') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			
			$sql[] = '
				ALTER TABLE `order`
				DROP `personal`,
				DROP `personal_time`,
				DROP `ordered`,
				DROP `ordered_time`,
				DROP `payed`,
				DROP `payed_time`,
				DROP `kouan`,
				DROP `kouan_time`,
				DROP `kouan_result`;
			';
			$sql[] = 'ALTER TABLE `order` ADD `erp_note` VARCHAR(256) NOT NULL COMMENT \'erp备注信息\' AFTER `erp_time`;';
			$sql[] = 'ALTER TABLE `order` ADD `refund_reason` VARCHAR(256) NOT NULL COMMENT \'退款原因\' AFTER `refundtime`, ADD `refund_note` VARCHAR(256) NOT NULL COMMENT \'退款备注\' AFTER `refund_reason`;';
			$sql[] = 'ALTER TABLE `order` ADD `msg_to_user` VARCHAR(256) NOT NULL COMMENT \'给用户的留言\' ;';
				
			
			$sql[] = 'ALTER TABLE `product`
			  DROP `categoryCode`,
			  DROP `grossWeight`,
			  DROP `goodsItemNo`,
			  DROP `goodsModel`,
			  DROP `currencyType`,
			  DROP `purpose`,
			  DROP `firstUnit`,
			  DROP `productRecordNo`,
			  DROP `package`;';
			$sql[] = "ALTER TABLE `product`  ADD `source` INT(11) NOT NULL DEFAULT '0',  ADD `brand` INT(11) NULL,  ADD `examine` TINYINT(4) NOT NULL,  ADD `examine_result` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,  ADD `examine_description` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,  ADD `examine_time` INT NOT NULL,  ADD `examine_stock` INT NOT NULL,  ADD `examine_stock_result` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,  ADD `examine_stock_description` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,  ADD `examine_stock_time` INT NOT NULL,  ADD `examine_price` INT NOT NULL,  ADD `examine_price_result` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,  ADD `examine_price_description` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,  ADD `examine_price_time` INT NOT NULL,  ADD `examine_final` INT NOT NULL,  ADD `examine_final_result` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,  ADD `examine_final_description` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,  ADD `examine_final_time` INT NOT NULL,  ADD `draft` TINYINT NOT NULL,  ADD `downStatus` TINYINT NOT NULL,  ADD `isnew` BOOLEAN NOT NULL,  ADD `weight` DOUBLE(10,2) NOT NULL;";
				
			$sql[] = "DROP TABLE IF EXISTS `role`;
CREATE TABLE IF NOT EXISTS `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '角色名称',
  `description` varchar(256) NOT NULL COMMENT '角色描述',
  `create_aid` int(11) NOT NULL COMMENT '创建者id',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否有效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;";
			$sql[] = "DROP TABLE IF EXISTS `role_privileges`;
CREATE TABLE IF NOT EXISTS `role_privileges` (
  `rid` int(11) NOT NULL COMMENT '角色id',
  `pid` int(11) NOT NULL COMMENT '权限id',
  `type` enum('column','page','button') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			
			$sql[] = 'ALTER TABLE `order_package` ADD `ship_note` VARCHAR(256) NOT NULL COMMENT \'发货备注\' ;';
			
			$sql[] = 'ALTER TABLE `order_log` ADD `aid` INT(11) NULL DEFAULT NULL COMMENT \'管理员id\' , ADD `note` VARCHAR(256) NOT NULL COMMENT \'相关备注抄送\' , ADD `status` VARCHAR(24) NOT NULL COMMENT \'操作前状态\' ;';
			
			$sql[] = 'ALTER TABLE `category` drop `alias`';
			
			//task的活动开始时间和活动结束时间
			$sql[] = 'ALTER TABLE `task` ADD `starttime` DATE NULL DEFAULT NULL COMMENT \'活动开始时间\' , ADD `endtime` DATE NULL DEFAULT NULL COMMENT \'活动结束时间\' , ADD `modifytime` int(11) NULL DEFAULT NULL COMMENT \'活动修改时间\';';
			
			$sql[] = "INSERT INTO `role` (`id`, `name`, `description`, `create_aid`, `create_time`, `status`) VALUES
(3, '超级管理员', '', 0, 0, 1);";
			$sql[] = "INSERT INTO `role_privileges` (`rid`, `pid`, `type`) VALUES
(3, 5, 'button'),
(3, 6, 'button'),
(3, 7, 'button'),
(3, 8, 'button'),
(3, 9, 'button'),
(3, 10, 'button'),
(3, 11, 'button'),
(3, 12, 'button'),
(3, 15, 'button'),
(3, 16, 'button'),
(3, 17, 'button'),
(3, 18, 'button'),
(3, 19, 'button'),
(3, 20, 'button'),
(3, 21, 'button'),
(3, 22, 'button'),
(3, 23, 'button'),
(3, 24, 'button'),
(3, 1, 'button'),
(3, 2, 'button'),
(3, 3, 'button'),
(3, 4, 'button'),
(3, 1, 'column'),
(3, 0, 'column'),
(3, 1, 'page'),
(3, 6, 'page'),
(3, 7, 'page'),
(3, 8, 'page'),
(3, 9, 'page'),
(3, 17, 'page'),
(3, 37, 'page'),
(3, 18, 'page'),
(3, 19, 'page'),
(3, 2, 'page'),
(3, 20, 'page'),
(3, 21, 'page'),
(3, 22, 'page'),
(3, 35, 'page'),
(3, 34, 'page'),
(3, 36, 'page'),
(3, 3, 'page'),
(3, 23, 'page'),
(3, 24, 'page'),
(3, 25, 'page'),
(3, 4, 'page'),
(3, 26, 'page'),
(3, 28, 'page'),
(3, 29, 'page'),
(3, 30, 'page'),
(3, 5, 'page'),
(3, 10, 'page'),
(3, 11, 'page'),
(3, 12, 'page'),
(3, 13, 'page'),
(3, 14, 'page'),
(3, 33, 'page'),
(3, 15, 'page'),
(3, 16, 'page'),
(3, 31, 'page'),
(3, 32, 'page'),
(3, 43, 'page'),
(3, 44, 'page'),
(3, 45, 'page'),
(3, 46, 'page'),
(3, 47, 'page'),
(3, 58, 'page'),
(3, 38, 'page'),
(3, 48, 'page'),
(3, 52, 'page'),
(3, 53, 'page'),
(3, 54, 'page'),
(3, 55, 'page'),
(3, 39, 'page'),
(3, 40, 'page'),
(3, 49, 'page'),
(3, 50, 'page'),
(3, 60, 'page'),
(3, 51, 'page'),
(3, 57, 'page'),
(3, 59, 'page'),
(3, 41, 'page'),
(3, 56, 'page'),
(3, 42, 'page'),
(3, 61, 'page'),
(3, 0, 'column'),
(3, 1, 'column');";
			$sql[] = "INSERT INTO `admin_role` (`aid`, `rid`) VALUES
(1, 3);";
			$sql[] = "update product set examine_final = 1,examine=1,examine_stock=1,examine_price=1 where isdelete=0";
			
			$sql[] = 'ALTER TABLE `store` ADD `publish` INT NULL DEFAULT NULL COMMENT \'供应商ID\' ;';
			foreach ($sql as $s)
			{
				$this->model('order')->exec($s);
			}
		
		$this->model('order')->commit();
	}
	
	function orderoff()
	{
		
		$orders = $this->model("order_package")
			->table('`order`', 'left join', 'order_package.orderno=`order`.orderno')
			->where("`order`.status=1 and `order`.pay_status=0 and  unix_timestamp(now())-`order`.createtime>=3600 and (select task_user.orderno from task_user where task_user.orderno=`order`.orderno) is null")
			->
		// ->where("`order`.status=1 and `order`.pay_status=0 and unix_timestamp(now())-`order`.createtime>=3600 ")
		// ->where("`order`.orderno=?", ['1606132140142742120'])
		select([
			'order_package.id',
			'order_package.orderno'
		]);
		if ($orders)
		{
			foreach ($orders as $o)
			{
				// 取商品数据 判断是否有content 不存在直接加库存 存在加另一个库存
				$order_pro1 = $this->model("order_product")
					->where("package_id=?", [
					$o['id']
				])
					->select();
				
				foreach ($order_pro1 as $order_pro)
				{
					$num = $order_pro['num'] * $order_pro['bind'];
					
					if ($order_pro['content'] != '')
					{
						$stock = $this->model("collection")
							->where("pid=? and content=?", [
							$order_pro['pid'],
							$order_pro['content']
						])
							->find('stock');
						$stock = $stock['stock'] + $num;
						$this->model("collection")
							->where("pid=? and content=?", [
							$order_pro['pid'],
							$order_pro['content']
						])
							->update([
							'stock' => $stock
						]);
					}
					else
					{
						$stock = $this->model("product")
							->where("id=?", [
							$order_pro['pid']
						])
							->find([
							'stock'
						]);
						$stock = $stock['stock'] + $num;
						$this->model("product")
							->where("id=?", [
							$order_pro['pid']
						])
							->update([
							'stock' => $stock
						]);
					}
				}
				
				if ($this->model("order")
					->where("orderno=?", [
					$o['orderno']
				])
					->update([
					"status" => 0,
					'quittime' => time()
				]))
				{
					echo $o['orderno'] . "关闭成功<br />";
				}
				else
				{
					echo $o['orderno'] . "关闭失败<br />";
					foreach ($order_pro1 as $order_pro)
					{
						$num = $order_pro['num'] * $order_pro['bind'];
						
						if ($order_pro['content'] != '')
						{
							$stock = $this->model("collection")
								->where("pid=? and content=?", [
								$order_pro['pid'],
								$order_pro['content']
							])
								->find('stock');
							$stock = $stock['stock'] - $num;
							$this->model("collection")
								->where("pid=? and content=?", [
								$order_pro['pid'],
								$order_pro['content']
							])
								->update([
								'stock' => $stock
							]);
						}
						else
						{
							$stock = $this->model("product")
								->where("id=?", [
								$order_pro['pid']
							])
								->find([
								'stock'
							]);
							$stock = $stock['stock'] - $num;
							$this->model("product")
								->where("id=?", [
								$order_pro['pid']
							])
								->update([
								'stock' => $stock
							]);
						}
					}
				}
			}
		}
		else
		{
			echo '无记录';
		}
	}

	function uporder()
	{
		
		$order = $this->model("order_product")->select();
		foreach ($order as $o)
		{
			// 获取商品的进价 商品名，店铺名，供应商名
			$product = $this->model("product")
				->table("store", "left join", "store.id=product.store")
				->table("publish", "left join", "publish.id=product.publish")
				->where('product.id=?', [
				$o['pid']
			])
				->find([
				'product.name',
				'store.name as storename',
				'publish.name as publish',
				"product.inprice"
			]);
			if ($this->model("order_product")
				->where("id=?", [
				$o['id']
			])
				->update([
				'name' => $product['name'],
				'store_name' => $product['storename'],
				'publish' => $product['publish'],
				'inprice' => $product['inprice']
			]
			))
			{
                echo $o['pid']."成功<br />";
            }else{
                echo $o['pid'] . "失败<br />";
            }

        }

    }
}
