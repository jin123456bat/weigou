<?php
namespace application\control\api;

use application\message\json;
use application\helper\user;
use application\helper\product;

class order extends common
{
    private $_response;

    function __construct()
    {
        parent::__construct();
        $this->_response = $this->init();
    }

    /**
     * 订单删除功能
     */
    function delete()
    {
        $orderno = $this->data('orderno');
        $userHelper = new user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            return new json(json::NOT_LOGIN);
        }

        if (empty($orderno)) {
            return new json(json::PARAMETER_ERROR, '订单编号不能为空');
        }

        if ($this->model('order')->where('orderno=?', [$orderno])->limit(1)->update([
            'isdelete' => 1,
            'deletetime' => $_SERVER['REQUEST_TIME']
        ])
        ) {
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR, '不要重复删除订单嘛');
    }

    /**
     * 创建订单
     */
    function create()
    {
        $userHelper = new user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        //商品信息
        $product = $this->data('product', '');
        $product = json_decode($product, true);
        if (empty($product)) {
            return new json(json::PARAMETER_ERROR, '请选择要购买的商品');
        }
        if (!is_array($product)) {
            return new json(json::PARAMETER_ERROR, 'product参数解析失败');
        }

        $prepay = $this->data('prepay', 0, 'intval');

        //优惠券
        $coupon = $this->data('coupon', '');

        //收货地址
        $address = $this->data('address', '', 'intval');
        if (empty($address) && !$prepay)
            return new json(json::PARAMETER_ERROR, '请选择收货地址');

        //用户留言
        $msg = $this->data('msg', '');

        //发票抬头
        $invoice = $this->data('invoice', '');

        //使用余额
        $money = $this->data('money', 0, 'floatval');
        if ($money < 0)
            $money = 0;

        $orderHelper = new \application\helper\order();

        $productHelper = new product();
        foreach ($product as $p) {
            if (!$productHelper->canBuy($p['id'], $p['content']))
                return new json(json::PARAMETER_ERROR, '存在不可购买的商品,请删除重新下单');
        }

        $order = $orderHelper->createOrderData($uid, $product, $coupon, $address, $money, $msg, $invoice);
        $package = $orderHelper->createPackageData();

        if ($prepay) {
            //预订单到这里结束了
            return new json(json::OK, NULL, $order);
        }

        if (empty(floatval($order['orderamount']))) {
            return new json(json::PARAMETER_ERROR, '创建订单失败');
        }

        $this->model('order')->transaction();

        if ($this->model('order')->insert($order)) {
            foreach ($package as $p) {
                $p['orderno'] = $order['orderno'];
                if (!$this->model('order_package')->insert($p)) {
                    $this->model('order')->rollback();
                    return new json(json::PARAMETER_ERROR, '订单包裹错误');
                }

                $package_id = $this->model('order_package')->lastInsertId();
                foreach ($p['product'] as $temp_product) {
                    $temp_product['package_id'] = $package_id;
                    if (!$this->model('order_product')->insert($temp_product)) {
                        $this->model('order')->rollback();
                        return new json(json::PARAMETER_ERROR, '订单商品错误');
                    }
                }
            }

            //减少库存
            foreach ($product as $p) {
            	$selled = $productHelper->getSelled($p);
                if (!$productHelper->increaseStock($p['id'], $p['content'], -$p['num'] * $selled)) {
                    $this->model('order')->rollback();
                    return new json(json::PARAMETER_ERROR, '库存不足');
                }
            }

            //扣除余额
            if ($order['money'] > 0) {
                if (!$this->model('user')->where('id=?', [$uid])->limit(1)->increase('money', -$order['money'])) {
                    $this->model('order')->rollback();
                    return new json(json::PARAMETER_ERROR, '扣除余额失败');
                }
            }

            //扣除优惠卷
            if ($orderHelper->usedCoupon()) {
                if (!$this->model('coupon')->where('id=?', [$orderHelper->getCouponId()])->limit(1)->update([
                    'used' => 1,
                    'usedtime' => $_SERVER['REQUEST_TIME']
                ])
                ) {
                    $this->model('order')->rollback();
                    return new json(json::PARAMETER_ERROR, '优惠卷使用错误');
                }
            }

            //是否清空购物车
            $clear = $this->data('clear', 0, 'intval');
            if ($clear) {
                foreach ($product as $p) {
                	
                	$selled = $productHelper->getSelled($p);
                	
                    if (!$this->model('cart')->where('uid=? and pid=? and content=? and bind=?', [$uid, $p['id'], $p['content'],$selled])->increase('num', -$p['num'])) {
                        $this->model('order')->rollback();
                        return new json(json::PARAMETER_ERROR, '清空购物车失败1');
                    }

                    $num = $this->model('cart')->where('uid=? and pid=? and content=? and bind=?', [$uid, $p['id'], $p['content'],$selled])->find();
                    if ($num['num'] <= 0) {
                        if (!$this->model('cart')->where('uid=? and pid=? and content=? and bind=?', [$uid, $p['id'], $p['content'],$selled])->delete()) {
                            $this->model('order')->rollback();
                            return new json(json::PARAMETER_ERROR, '清空购物车失败2');
                        }
                    }
                }
            }

            $this->model('order_log')->add($order['orderno'], '订单创建成功，等待支付');

            $this->model('order')->commit();
            return new json(json::OK, NULL, $order);
        }
        $this->model('order')->rollback();
        return new json(json::PARAMETER_ERROR, '订单创建失败');
    }


    /**
     * 订单取消
     * @return \application\message\json
     */
    function quit()
    {
        if (!empty($this->_response)) {
            return $this->_response;
        }

        $orderno = $this->data('orderno');
        if (empty($orderno)) {
            return new json(json::PARAMETER_ERROR);
        }

        $orderHelper = new \application\helper\order();

        if (!empty($this->model('task_user')->where('orderno=?', [$orderno])->find())) {
            return new json(json::PARAMETER_ERROR, '团购订单无法手动取消');
        }

        $order = $this->model('order')->where('orderno=?', [$orderno])->find();

        $this->model('order')->transaction();
        if ($orderHelper->quitOrder($orderno, false)) {
            $this->model('order')->commit();
            return new json(json::OK);
        } else {
            $this->model('order')->rollback();
            return new json(json::PARAMETER_ERROR, '取消失败');
        }
    }

    /**
     * 订单收货
     */
    function receive()
    {
        if (!empty($this->_response))
            return $this->_response;

        $orderno = $this->data('orderno');
        if (empty($orderno))
            return new json(json::PARAMETER_ERROR);

        $order = $this->model('order')->where('orderno=?', [$orderno])->find();
        if (!empty($order)) {
            if ($order['receive'] != 0)
                return new json(json::PARAMETER_ERROR, '订单已经收货了');

            if ($this->model('order')->where('orderno=?', [$orderno])->update([
                'receive' => 1,
                'receive_time' => $_SERVER['REQUEST_TIME']
            ])
            ) {

                $this->model('order_log')->add($orderno, '订单确认签收了');

                return new json(json::OK);
            }
        }
        return new json(json::PARAMETER_ERROR);
    }


    function detail()
    {
        if (!empty($this->_response))
            return $this->_response;

        $orderno = $this->data('orderno');
        if (empty($orderno))
            return new json(json::PARAMETER_ERROR);

        $order = $this->model('order')
            ->table('address', 'left join', 'address.id=order.address')
            ->table('province', 'left join', 'province.id=address.province')
            ->table('city', 'left join', 'city.id=address.city')
            ->table('county', 'left join', 'county.id=address.county')
            ->where('orderno=?', [$orderno])->find([
                'order.orderno',
                'province.name as province',
                'city.name as city',
                'county.name as county',
                'address.address',
                'address.name',
                'address.telephone',
                'order.goodsamount',
                'order.feeamount',
                'order.taxamount',
                'order.discount',
                'order.orderamount',
                'order.pay_status',
                'order.pay_money',
                'order.pay_time',
                'order.createtime',
                'order.status',
                'order.way_status',
                'order.receive',
                'order.receive_time',
                'address.identify'
            ]);
        if (!empty($order)) {
            //筛选所有仓库
            $store = $this->model('order_package')
                ->table('order_product', 'left join', 'order_product.package_id=order_package.id')
                ->table('product', 'left join', 'order_product.pid=product.id')
                ->table('store', 'left join', 'product.store=store.id')
                ->groupby('store.id')
                ->where('order_package.orderno=?', [$orderno])
                ->select('store.id,store.name');
            foreach ($store as &$st) {
                $product = $this->model('order_package')
                    ->table('order_product', 'left join', 'order_product.package_id=order_package.id')
                    ->table('product', 'left join', 'order_product.pid=product.id')
                    ->table('store', 'left join', 'product.store=store.id')
                    ->where('store.id=?', [$st['id']])
                    ->where('order_package.orderno=?', [$orderno])->select([
                        'order_product.num',
                        'order_product.content',
                        'order_product.price',
                        'product.id',
                        'product.oldprice',
                        'product.name',
                        'store.name as store',
                        'product.outside',
                        'order_product.refund',
                    ]);

                $productHelper = new \application\helper\product();
                foreach ($product as &$p) {
                    $p['image'] = $productHelper->getListImage($p['id']);
                    $p['tax'] = $productHelper->getTaxFields($p['id']);
                }

                $st['product'] = $product;
            }
            //物流参数
            $wuliu = array();
            //判断是否付款
            if ($order['pay_status'] == 1) {
                //判断是否发货
                if ($order['way_status'] == 1) {
                    //是否确认收获

                    if ($order['receive']==1) {
                        $wuliu['wuliu_notice'] = '订单提交成功';
                        $wuliu['wuliu_time'] = $order['pay_time'];
                    }
                    //判断是否是否有ship_number

                } else {
                    $wuliu['wuliu_notice'] = '订单提交成功';
                    $wuliu['wuliu_time'] = $order['pay_time'];
                }
            } else {
                $wuliu['wuliu_notice'] = '';
                $wuliu['wuliu_time'] = '';
            }


            $order['store'] = $store;

            $order['is_task'] = !empty($this->model('task_user')->where('orderno=?', [$orderno])->find());

            return new json(json::OK, NULL, $order);
        }
        return new json(json::PARAMETER_ERROR);
    }

    /**
     * 订单列表
     */
    function mylists()
    {
        if (!empty($this->_response))
            return $this->_response;

        $userHelper = new user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);


        $filter = [
            'isdelete' => 0,
            'uid' => $uid,
            'start' => $this->data('start', 0, 'intval'),
            'length' => $this->data('length', 10, 'intval'),
            'sort' => ['createtime', 'desc'],
            'parameter' => [
                '`order`.orderno',
                '`order`.pay_status',
                '`order`.pay_type',
                '`order`.pay_number',
                '`order`.pay_money',
                '`order`.pay_time',
                '`order`.taxamount',
                '`order`.feeamount',
                '`order`.goodsamount',
                '`order`.discount',
                '`order`.orderamount',
                '`order`.money',
                '`order`.address',
                '`order`.way_status',
                '`order`.createtime',
                '`order`.status',
                '`order`.note',
                '`order`.msg',
                '`order`.invoice',
                '`order`.receive',
                '`order`.receive_time',
                '`order`.way_time',
            ],
        ];

        $pay_status = $this->data('pay_status', NULL, 'intval');
        if ($pay_status !== NULL) {
            $filter['pay_status'] = $pay_status;
        }

        $way_status = $this->data('way_status', NULL, 'intval');
        if ($way_status !== NULL) {
            $filter['way_status'] = $way_status;
        }

        $receive = $this->data('receive', NULL, 'intval');
        if ($receive !== NULL) {
            $filter['receive'] = $receive;
        }

        $status = $this->data('status', NULL, 'intval');
        if ($status !== NULL) {
            $filter['status'] = $status;
        }
        $order = $this->model('order')->fetch($filter);

        $productHelper = new product();
        foreach ($order as &$t_order) {
            $total_product_num = 0;//订单中的商品总数
            $t_order['product'] = $this->model('order_product')
                ->table('order_package', 'left join', 'order_package.id=order_product.package_id')
                ->table('product', 'left join', 'product.id=order_product.pid')
                ->where('order_package.orderno=?', [$t_order['orderno']])
                ->select([
                    'order_product.num',
                    'order_product.pid',
                    'product.id',
                    'product.name',
                    'order_product.content',
                    'order_product.price',
                    'product.oldprice',
                ]);
            foreach ($t_order['product'] as &$product) {
                $total_product_num += $product['num'];
                $product['image'] = $productHelper->getListImage($product['id']);
                $product['tax'] = $productHelper->getTaxFields($product['id']);
            }
            $t_order['product_num'] = $total_product_num;

            $t_order['is_task'] = !empty($this->model('task_user')->where('orderno=?', [$t_order['orderno']])->find());
        }

        unset($filter['start']);
        unset($filter['length']);
        $filter['parameter'] = 'count(*)';

        $total = $this->model('order')->fetch($filter);
        $orderReturnModel = [
            'current' => count($order),
            'total' => isset($total[0]['count(*)']) ? $total[0]['count(*)'] : 0,
            'start' => $this->data('start', 0),
            'length' => $this->data('length', 10),
            'data' => $order,
        ];
        return new json(json::OK, NULL, $orderReturnModel);
    }

    function detail_way()
    {
        if (!empty($this->_response))
            return $this->_response;

        $orderno = $this->data('orderno');
        if (!empty($orderno)) {
            $order = $this->model('order')
                ->table('address', 'left join', 'address.id=order.address')
                ->table('province', 'left join', 'province.id=address.province')
                ->table('city', 'left join', 'city.id=address.city')
                ->table('county', 'left join', 'county.id=address.county')
                ->where('orderno=?', [$orderno])->find([
                    'order.orderno',
                    'province.name as province',
                    'city.name as city',
                    'county.name as county',
                    'address.address',
                    'address.name',
                    'address.telephone',
                    'order.goodsamount',
                    'order.feeamount',
                    'order.taxamount',
                    'order.discount',
                    'order.orderamount',
                    'order.pay_status',
                    'order.pay_money',
                    'order.createtime',
                    'order.status',
                    'order.way_status',
                    'order.receive',
                    'order.receive_time',
                    'address.identify'
                ]);
            if (!empty($order)) {
                if ($order['way_status'] == 1) {
                    $productHelper = new \application\helper\product();
                    //找到所有包裹
                    $package = $this->model('order_package')->where('order_package.orderno=?', [$orderno])->select();
                    foreach ($package as &$p) {
                        //包裹下的商品
                        $product_array = $this->model('order_product')
                            ->table('product', 'left join', 'product.id=order_product.pid')
                            ->where('order_product.package_id=?', [$p['id']])
                            ->select([
                                'product.id',
                                'product.name',
                                'order_product.price',
                                'order_product.content',
                                'order_product.num',
                            ]);
                        foreach ($product_array as &$product) {
                            $product['image'] = $productHelper->getListImage($product['id']);
                        }

                        $p['ship_type'] = $this->model('ship')->get($p['ship_type']);
                        $p['product'] = $product_array;
                    }
                    $order['mypackage'] = $package;
                    $order['is_task'] = !empty($this->model('task_user')->where('orderno=?', [$orderno])->find());
                    return new json(json::OK, $order);
                }
                return new json(json::PARAMETER_ERROR, '订单尚未发货');
            }
        }
        return new json(json::PARAMETER_ERROR);
    }

    function wuliu()
    {
        if (!empty($this->_response))
            return $this->_response;

        $id = $this->data('id');
        $package = $this->model('order_package')->where('id=?', [$id])->find();
        if (!empty($package)) {

            $response = \application\helper\express::queryJuhe($package['ship_type'], $package['ship_number']);
            $response = json_decode($response, true);
            if ($response['resultcode'] == 200) {
                $package['ship_type'] = $this->model('ship')->get($package['ship_type']);
                return new json(json::OK, NULL, [
                    'mypackage' => $package,
                    'response' => $response['result'],
                ]);
            }
            return new json(json::PARAMETER_ERROR, '物流信息查询失败');
        }
        return new json(json::PARAMETER_ERROR);
    }
}