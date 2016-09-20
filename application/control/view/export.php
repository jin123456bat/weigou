<?php
namespace application\control\view;

use system\core\view;

class export extends view
{
    function __construct()
    {
        $this->_csrf_token_refresh = false;
        parent::__construct();

        $this->initlize();
    }

    private function initlize()
    {
        $adminHelper = new \application\helper\admin();
        $roleModel = $this->model('role');
        $role = $adminHelper->getGroupId();
        if (!$roleModel->checkPower($role, 'export', \application\model\roleModel::POWER_ALL)) {
            $this->setViewname('nopower');
            exit();
        }
    }

    function vip()
    {
        $vip_orderModel = $this->model('vip_order');
        $id = $this->post('id');
        if (is_array($id) && !empty($id) && $this->post('all') == 'select') {
            $vip_orderModel->where('id in (?)', $id);
        }
        if ($this->post('all') == 'noselect') {
            if (!empty($id)) {
                $vip_orderModel->where('id not in (?)', $id);
            }
        }
        $data = $vip_orderModel->select([
            'vip_order.id',
            'vip_order.orderno',
            '(select name from user where user.id=vip_order.uid) as username',
            'from_unixtime(vip_order.createtime)',
            'if(vip_from=0,if(vip_to=1,200,800),600) as money',
            'if(payprice=0,"未支付","已支付")',
            'concat(vip_from,"-",vip_to)',
            'ifnull((select money from swift where order_type=\'vip\' and swift.orderno=vip_order.orderno and source=5),0) as vip1',
            'ifnull((select money from swift where order_type=\'vip\' and swift.orderno=vip_order.orderno and source=6),0) as vip2',
            'ifnull((select money from swift where order_type=\'vip\' and swift.orderno=vip_order.orderno and source=7),0) as vip3',
        ]);
        $template = ROOT . '/extends/PHPExcel/vip.xlsx';
        $this->response($data, $template, 'VIP订单数据' . date('Y-m-d H:i:s'));
    }

    function order()
    {
        $order_productModel = $this->model('order_product');
        $orderno = $this->post('orderno');
        if (is_array($orderno) && !empty($orderno) && $this->post('all') == 'select') {
            $order_productModel->where('order.orderno in (?)', $orderno);
        } else if ($this->post('all') == 'noselect') {
            if (!empty($orderno)) {
                $order_productModel->where('order.orderno not in (?)', $orderno);
            }
        } else {
            //导出全部的时候不包含未支付的订单
            $order_productModel->where('pay_status != 0');
        }
        $data = $order_productModel
            ->table('order_package', 'left join', 'order_package.id=order_product.package_id')
            ->table('`order`', 'left join', '`order`.orderno=order_package.orderno')
            ->table('product', 'left join', 'product.id=order_product.pid')
            ->table('suborder_store_product', 'left join', 'suborder_store_product.order_product_id=order_product.id')
            ->table('suborder_store', 'left join', 'suborder_store.id=suborder_store_product.suborder_id')
            ->select([
                'order.orderno',//订单号
                'concat(replace(suborder_store.date,"-",""),suborder_store.id) as suborder_orderno',//订单短号
                'order_package.id',//包裹号
                'from_unixtime(order.createtime)',//订单时间
                '(select name from user where order.uid=user.id) as username',//用户名
                'product.barcode',//条形码
                'product.sku',//商品SKU
                'product.name',//商品名称
                'product.inprice',//商品进价
                'if(order_product.num*order_product.bind=0,order_product.num,order_product.num*order_product.bind) as num',//数量
                'order.orderamount',//订单金额

                'order.pay_money',//支付金额
                'order_product.refund',//商品状态
                'replace(replace(if(order.pay_type="","未支付",order.pay_type),"alipay","支付宝"),"wechat","微信")',//支付方式
                'if(order.way_status=0,"未发货",from_unixtime(order.way_time))',//发货时间
                'ifnull((select ship.name from ship where ship.code=order_package.ship_type),"") as ship_name',//快递公司
                'order_package.ship_number',//快递单号
                //'(select concat_ws(":",province.name,city.name,county.name,address.address,address.name,address.telephone,address.identify) from address,province,city,county where order.address=address.id and province.id=address.province and city.id=address.city and county.id=address.county) as address',//配送信息

                '(select province.name from province,address where order.address=address.id and province.id=address.province) as province',//省份
                '(select city.name from city,address where order.address=address.id and city.id=address.city) as city',//省份
                '(select county.name from county,address where order.address=address.id and county.id=address.county) as county',//省份
                '(select address.address from address where address.id=order.address) as address',
                '(select address.name from address where address.id=order.address) as receive_name',
                '(select address.telephone from address where address.id=order.address) as receive_telephone',
                '(select address.identify from address where address.id=order.address) as receive_identify',


                'order.pay_number',//支付编号
                'if(product.outside=1 or product.outside=0,"无需报关",if(kouan=1,"已报关","未报关"))',//报关
                'if(order.status=1,"有效","无效")',//状态
                'replace(replace(order.need_kouan,1,"需要报关"),0,"无需报关")',//类别
                'ifnull((select publish.name from publish where product.publish=publish.id),"") as publish',//供应商
                'if((select task_user.orderno from task_user where task_user.orderno=order.orderno) is null,"否","是")',//是否团购
                'replace(replace(replace(ifnull((select task_user.status from task_user where task_user.orderno=order.orderno),""),0,"正在进行"),1,"成功"),2,"失败")',//团购成功
                'order_product.price',//商品单价
                'order.taxamount',
                'order.feeamount',
                'order.discount',
                'order.note',//商家备注
                //'if(order.pay_status=1,"已支付","未支付")',//支付状态

                'ifnull((select sum(money) from swift where swift.orderno=order.orderno and swift.order_type=\'order\' and swift.source=2),0) as product1',
                'ifnull((select sum(money) from swift where swift.orderno=order.orderno and swift.order_type=\'order\' and swift.source=3),0) as product2',
                'ifnull((select sum(money) from swift where swift.orderno=order.orderno and swift.order_type=\'order\' and swift.source=4),0) as product3',
                'order.invoice',//发票
                'if(suborder_store.erp=1,"已推送","未推送") as erp',//erp信息

            ]);


        //exit(json_encode($data));


        $phpexcel_root = ROOT . '/extends/PHPExcel';
        include $phpexcel_root . '/PHPExcel.php';

        $ship = $this->model('ship')->select('GROUP_CONCAT(ship.name) as name');

        //所有导出的订单
        $orderno_array = [];

        foreach ($data as &$value) {
            switch ($value['refund']) {
                case 1:
                    $value['refund'] = "已退款";
                    break;
                case 2:
                    $value['refund'] = "正在退款";
                    break;
                default:
                    $value['refund'] = "未退款";
                    break;
            }

            $orderno_array[] = $value['orderno'];

            $shipname = $value['ship_name'];
            //快递公司增加数据验证
            $value['ship_name'] = new \stdClass();
            $value['ship_name']->type = \PHPExcel_Cell_DataValidation::TYPE_LIST;
            $value['ship_name']->ErrorStyle = \PHPExcel_Cell_DataValidation::STYLE_INFORMATION;
            $value['ship_name']->AllowBlank = true;//是否允许空值
            $value['ship_name']->ShowInputMessage = true;//显示输入信息
            $value['ship_name']->ShowErrorMessage = true;//显示错误信息
            $value['ship_name']->ShowDropDown = true;//显示下拉菜单
            $value['ship_name']->ErrorTitle = '输入的值有误';
            $value['ship_name']->Error = '您输入的值不在下拉框列表内.';
            $value['ship_name']->PromptTitle = '快递公司';
            $value['ship_name']->Formula1 = '"' . $ship[0]['name'] . '"';
            $value['ship_name']->value = $shipname;
        }

        $orderno_array = array_unique($orderno_array);
        foreach ($orderno_array as $orderno) {
            $this->model('order')->where('orderno=?', [$orderno])->limit(1)->update([
                'erp' => 1,
                'erp_time' => $_SERVER['REQUEST_TIME']
            ]);
            $this->model('order_log')->add($orderno, '订单导出');
        }

        $template = ROOT . '/extends/PHPExcel/order.xlsx';
        $this->response($data, $template, '订单数据' . date('Y-m-d H:i:s'));
    }

    /**
     * 提现导出
     */
    function drawal()
    {
        $drawalModel = $this->model('drawal');
        $id = $this->post('id');
        if (is_array($id) && !empty($id) && $this->post('all') == 'select') {
            $drawalModel->where('id in (?)', $id);
        }
        if ($this->post('all') == 'noselect') {
            if (!empty($id)) {
                $drawalModel->where('id not in (?)', $id);
            }
        }
        $data = $drawalModel->select([
            'drawal.id',
            'from_unixtime(drawal.createtime)',
            '(select name from user where user.id=drawal.uid) as username',//户名
            'replace(replace((select concat_ws(" ",bankcard.type,bankcard.account,bankcard.name) from bankcard where bankcard.id=drawal.bankcard),"alipay","支付宝"),"bank","银行卡") as bankcard',//提现账户
            'drawal.money',
            'replace(replace(drawal.pass,1,"通过"),0,"尚未通过")',
            'if(drawal.passtime!=0,from_unixtime(drawal.passtime),"")',
        ]);
        $template = ROOT . '/extends/PHPExcel/drawal.xlsx';
        $this->response($data, $template, '提现数据' . date('Y-m-d H:i:s'));
    }

    /**
     * 商品导出
     */
    /*function product()
    {
        $productModel = $this->model('product');
        $productModel->where('isdelete =?',[0]);
        $id = $this->post('id');
        if (is_array($id) && !empty($id) && $this->post('all') == 'select')
        {
            $productModel->where('id in (?)',$id);
        }
        if ($this->post('all') == 'noselect')
        {
            if (!empty($id))
            {
                $productModel->where('id not in (?)',$id);
            }
        }
        $data = $productModel->select([
            'product.id',
            'product.sku',
            'product.barcode',//条形码
            'product.inprice',//进价
            'product.selled',//售卖数
            'product.down_reason',//下架原因
            'product.name',
            'replace(replace(replace(replace(product.outside,0,"普通商品"),1,"进口商品"),2,"直供商品"),3,"直邮商品")',//类别

            'if(product.freetax=1,"是","否")',
            'product.sort',//排序
            'product.oldprice',
            'product.price',
            'product.v1price',
            'product.v2price',
            'product.stock',
            'replace(replace(if(product.auto_status=1,if(product.avaliabletime_from<now() and product.avaliabletime_to>now(),1,0),product.status),1,"上架"),0,"下架")',//商品状态

            'product.fee',//邮费
            '(select publish.name from publish where product.publish=publish.id) as publish',//供应商
            '(select name from country where product.origin=country.id)',//来源国
            '(select name from store where product.store=store.id)',//仓库
            '(select GROUP_CONCAT( province.name ) from province,product_province where province.id= product_province.province_id and product.id=product_province.product_id) as product_province',//包邮地区
            'concat_ws(",",if((select count(*) from task where task.pid=product.id and task.isdelete=0)>=1,"团购",""),if((select count(*) from product_top where product.id=product_top.pid)>=1,"首页","")) as prototype',//属性



            '(select name from category,category_product where category_product.cid=category.id and category_product.pid=product.id and category_product.isdelete=0 limit 0,1) as category1',//第一个分类

            'ifnull(if(product.outside=3,(select tax from posttaxno where product.postTaxNo=posttaxno.id),if(product.outside=2,(select 0.7*((xtax+ztax)/(1-xtax)) from tax as tax_table where product.ztax=tax_table.id),null)),0) as tax',//税率
            '(select name from category,category_product where category_product.cid=category.id and category_product.pid=product.id and category_product.isdelete=0 limit 1,1) as category2',//第二个分类

        ]);

        $template = ROOT.'/extends/PHPExcel/product.xlsx';
        $this->response($data,$template,'商品数据'.date('Y-m-d H:i:s'));
    }*/

    function product()
    {
        $productModel = $this->model('product');
        $productModel->where('isdelete =?', [0]);
        $id = $this->post('id');
        if (is_array($id) && !empty($id) && $this->post('all') == 'select') {
            $productModel->where('id in (?)', $id);
        }
        if ($this->post('all') == 'noselect') {
            if (!empty($id)) {
                $productModel->where('id not in (?)', $id);
            }
        }
        $data = $productModel->select([
            'product.id',
            'product.sku',
            'product.barcode',//条形码
            // 'product.inprice',//进价
            //'product.selled',//售卖数
            'product.down_reason',//下架原因
            'product.name',
            'replace(replace(replace(replace(product.outside,0,"普通商品"),1,"进口商品"),2,"直供商品"),3,"直邮商品") as lei',//类别

            'if(product.freetax=1,"是","否") as freetax',
            'product.sort',//排序
            'product.oldprice',
            //'product.price',
            //'product.v1price',
            //'product.v2price',
            'product.stock',
            'replace(replace(if(product.auto_status=1,if(product.avaliabletime_from<now() and product.avaliabletime_to>now(),1,0),product.status),1,"上架"),0,"下架") as prostatus',//商品状态

            'product.fee',//邮费
            '(select publish.name from publish where product.publish=publish.id) as publish',//供应商
            '(select name from country where product.origin=country.id) as fromp',//来源国
            '(select name from store where product.store=store.id) as pstore',//仓库
            '(select GROUP_CONCAT( province.name ) from province,product_province where province.id= product_province.province_id and product.id=product_province.product_id) as product_province',//包邮地区
            'concat_ws(",",if((select count(*) from task where task.pid=product.id and task.isdelete=0)>=1,"团购",""),if((select count(*) from product_top where product.id=product_top.pid)>=1,"首页","")) as prototype',//属性


            '(select name from category,category_product where category_product.cid=category.id and category_product.pid=product.id and category_product.isdelete=0 limit 0,1) as category1',//第一个分类

            'ifnull(if(product.outside=3,(select tax from posttaxno where product.postTaxNo=posttaxno.id),if(product.outside=2,(select 0.7*((xtax+ztax)/(1-xtax)) from tax as tax_table where product.ztax=tax_table.id),null)),0) as tax',//税率
            '(select name from category,category_product where category_product.cid=category.id and category_product.pid=product.id and category_product.isdelete=0 limit 1,1) as category2',//第二个分类

        ]);
        $i = 0;
        $datan = array();
        foreach ($data as $d) {
            $datan[$i]['id'] = $d['id'];
            $datan[$i]['sku'] = $d['sku'];
            $datan[$i]['barcode'] = $d['barcode'];//条形码
            //3个进价  3个数量 bind表
            $bind = $this->model("bind")->where("pid=?", [$d['id']])->orderby("sort", "asc")->select();
            if ($bind) {
                for ($j = 0; $j < 3; $j++) {
                    if (isset($bind[$j])) {
                        $datan[$i]['inprice' . $j] = $bind[$j]['inprice'];
                        $datan[$i]['selled' . $j] = $bind[$j]['num'];
                    } else {
                        $datan[$i]['inprice' . $j] = '';
                        $datan[$i]['selled' . $j] = '';
                    }
                }


            } else {
                //获取product表价格
                $pp = $this->model("product")->where("id=?", [$d['id']])->find();
                $datan[$i]['inprice0'] = $pp['inprice'];
                $datan[$i]['selled0'] = $pp['selled'];
                $datan[$i]['inprice1'] = '';
                $datan[$i]['selled1'] = '';
                $datan[$i]['inprice2'] = '';
                $datan[$i]['selled2'] = '';

            }


            $datan[$i]['down_reason'] = $d['down_reason']; //下架原因
            $datan[$i]['name'] = $d['name'];//商品名
            $datan[$i]['lei'] = $d['lei'];//类别
            $datan[$i]['freetax'] = $d['freetax'];
            $datan[$i]['sort'] = $d['sort'];///排序
            $datan[$i]['oldprice'] = $d['oldprice'];//原价

            $unit='';
            if ($bind) {
                for ($j = 0; $j < 3; $j++) {
                    if (isset($bind[$j])) {
                        $unit= $bind[$j]['unit'];
                        $datan[$i]['price' . $j] = $bind[$j]['price'];
                        $datan[$i]['v1price' . $j] = $bind[$j]['v1price'];
                        $datan[$i]['v2price' . $j] = $bind[$j]['v2price'];
                    } else {
                        $datan[$i]['price' . $j] = '';
                        $datan[$i]['v1price' . $j] = '';
                        $datan[$i]['v2price' . $j] = '';
                    }
                }


            } else {
                //获取product表价格
                $pp = $this->model("product")->where("id=?", [$d['id']])->find();
                $datan[$i]['price0'] = $pp['price'];
                $datan[$i]['v1price0'] = $pp['v1price'];
                $datan[$i]['v2price0'] = $pp['v2price'];
                $datan[$i]['price1'] = "";
                $datan[$i]['v1price1'] = "";
                $datan[$i]['v2price1'] = "";
                $datan[$i]['price2'] = "";
                $datan[$i]['v1price2'] = "";
                $datan[$i]['v2price2'] = "";

            }


            $datan[$i]['stock'] = $d['stock'];//库存
            $datan[$i]['prostatus'] = $d['prostatus']; //商品状态

            $datan[$i]['fee'] = $d['fee'];//邮费
            $datan[$i]['publish'] = $d['publish'];//供应商
            $datan[$i]['unit'] = $unit;//供应商
            $datan[$i]['fromp'] = $d['fromp'];//来源国
            $datan[$i]['pstore'] = $d['pstore'];//仓库


            $datan[$i]['product_province'] = $d['product_province'];//包邮地区
            $datan[$i]['prototype'] = $d['prototype'];//属性
            $datan[$i]['category1'] = $d['category1'];//分类1
            $datan[$i]['tax'] = $d['tax'];//税费

            $datan[$i]['category2'] = $d['category2'];//分类2
            $i++;
        }
        //die(json_encode($datan));
        $template = ROOT . '/extends/PHPExcel/product.xlsx';
        $this->response($datan, $template, '商品数据' . date('Y-m-d H:i:s'));
    }

    /**
     * 用户导出
     */
    function user()
    {
        $userModel = $this->model('user');
        $id = $this->post('id', array());
        //数据内容
        if (is_array($id) && !empty($id) && $this->post('all') == 'select') {
            $userModel->where('id in (?)', $id);
        }
        if ($this->post('all') == 'noselect') {
            if (!empty($id)) {
                $userModel->where('id not in (?)', $id);
            }
        }
        $y_profit_time_start = strtotime(date('Y-m-d', time() - 24 * 3600));
        $y_profit_time_end = $y_profit_time_start + 24 * 3600;

        $data = $this->model('user')->select([
            'user.id',
            'user.name',
            'user.telephone',
            'user.invit',
            'ifnull((select sum(money) from swift where source in (2,3,4,5,6,7) and uid=user.id and time > ' . $y_profit_time_start . ' and time < ' . $y_profit_time_end . '),0) as yesterday_profit',//昨日收益
            'user.money',
            'ifnull((select sum(money) from swift where source in (2,3,4,5,6,7) and type=0 and uid=user.id),0) as profit',//累计收益
            'ifnull((select sum(money) from swift where source in (2,3) and uid=user.id),0) as product',//产品推广
            'ifnull((select sum(money) from swift where source in (5,6) and uid=user.id),0) as pintai',//平台推广
            'ifnull((select sum(money) from swift where source in (4,7) and uid=user.id),0) as team',//团队管理
            'ifnull((select sum(money) from drawal where uid=user.id and pass=0),0) as drawaling',//提现中
            'ifnull((select sum(money) from drawal where uid=user.id and pass=1),0) as drawaled',//已提现
            'replace(replace(replace(user.vip,1,"白金会员"),0,"普通用户"),2,"钻石会员")',
            'replace(replace(user.master,1,"是"),0,"否")',
            '(select name from user as user2 where user2.id=user.o_master) as o_master',
            '(select name from user as user3 where user3.id=user.oid) as oid',
            'user.wechat_no'
        ]);
        $template = ROOT . '/extends/PHPExcel/user.xlsx';
        $this->response($data, $template, '用户数据' . date('Y-m-d H:i:s'));
    }

    private function response(array $data, $template, $filename, $start_line = 2)
    {
        $excel = new \application\helper\excel();
        $filepath = $excel->phpexcel($data, $template, $start_line);
        //excel文件下载
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=" . $filename . "." . pathinfo($template, PATHINFO_EXTENSION) . "");
        header("Pragma: no-cache");
        readfile($filepath);
        unlink($filepath);
    }
}
