<?php
namespace application\helper;

use system\core\base;
use system\core\random;

class order extends base
{
    private $_uid;

    private $_product;

    private $_coupon;

    private $_address;

    private $_money;

    private $_msg;

    private $_invoice;

    private $_usedCoupon = false;

    private $_used_coupon_id = NULL;

    private $_need_kouan = 0;

    private $_outside = [];

    function __construct()
    {
        parent::__construct();
    }

    /**
     * 按照仓库拆分订单
     * @return 0订单不存在  1拆单成功 2已经拆单过了 3无需拆单 4系统繁忙 5订单状态不符合
     */
    public function departByStore($orderno)
    {
        //判断订单是否存在
        $order = $this->model('order')->where('orderno=?', [$orderno])->find();
        if (empty($order)) {
            return 0;
        }


        //订单状态不符合 无需推单
        if ($order['pay_status'] == 0 || $order['pay_status'] == 2 || $order['pay_status'] == 3 || $order['status'] == 0) {
            return 5;
        }

        //判断是否已经拆分过了
        if (!empty($this->model('suborder_store')->where('main_orderno=?', [$orderno])->find())) {
            return 2;
        }


        //计算订单下的仓库数量
        $store = [];
        $order_package = $this->model('order_package')->where('orderno=?', [$orderno])->select();
        foreach ($order_package as $package) {
            $store[] = $package['store_id'];
        }
        $store = array_unique($store);

        //假如发货仓库大于等于1个则拆单
        if (count($store) >= 1) {
            foreach ($store as $st) {
                $product = $this->model('order_package')
                    ->table('order_product', 'left join', 'order_product.package_id=order_package.id')
                    ->where('order_package.orderno=?', [$orderno])
                    ->where('order_package.store_id=?', [$st])
                    ->select(['order_product.*']);

                $goodsamount = 0;
                $feeamount = 0;
                $taxamount = 0;
                foreach ($product as $p) {
                    $goodsamount += $p['price'] * $p['num']*$p['bind'];//计算商品总价
                    $feeamount += $p['fee'];//计算运费总价
                    $taxamount += $p['tax'];//计算税款总价
                }

                if ($goodsamount == 0) {
                    $discount = 0;
                } else {
                    $discount = $order['discount'] * $goodsamount / $order['goodsamount'];
                }

                $suborder = [
                    'id' => NULL,
                    'date' => date('Y-m-d'),
                    'main_orderno' => $orderno,
                    'pay_money' => $order['pay_status'] == 1 ? ($goodsamount + $feeamount + $taxamount - $discount) : 0,//支付金额
                    'orderamount' => $goodsamount + $feeamount + $taxamount - $discount,
                    'goodsamount' => $goodsamount,//商品总价
                    'feeamount' => $feeamount,
                    'discount' => $discount,
                    'taxamount' => $taxamount,
                    'address' => $order['address'],
                    'uid' => $order['uid'],
                    'erp' => 0,
                    'erptime' => 0,
                    'sub_createtime' => $_SERVER['REQUEST_TIME'],
                    'store' => $st,
                ];


                $this->model('suborder_store')->transaction();
                if ($this->model('suborder_store')->insert($suborder)) {
                    $sub_id = $this->model('suborder_store')->lastInsertId();

                    foreach ($product as $p) {
                        if (!$this->model('suborder_store_product')->insert([
                            'suborder_id' => $sub_id,
                            'order_product_id' => $p['id'],
                        ])
                        ) {
                            $this->model('suborder_store')->rollback();
                            return 4;
                        }
                    }
                }
                $this->model('suborder_store')->commit();
            }
            return 1;
        } else {
            return 3;
        }
    }

    public function hasProductOutside($outside)
    {
        $this->_outside = array_unique($this->_outside);
        foreach ($this->_outside as $o) {
            if ($o == $outside)
                return true;
        }
        return false;
    }

    /**
     * 计算相关价格
     */
    private function totalamount()
    {
        $user = $this->model('user')->where('id=?', [$this->_uid])->find();

        $totalamount = 0;
        $feeamount = 0;
        $taxamount = 0;

        //仓库下 行邮税 应征收数额
        $_current_store_cal_tax = [];
        //计算了行邮税的商品
        $this->_current_posttax_product = [];

        //收过运费的仓库（针对普通商品和进口商品）
        $_current_store_cal_fee = [];
        foreach ($this->_product as $p) {
            //当前商品的运费
            $_current_product_fee = 0;
            //当前商品的单价
            $_current_product_unit_price = 0;

            $product = $this->model('product')->where('id=?', [$p['id']])->find();

            //把当前的商品类型加入到outside
            $this->_outside[] = $product['outside'];

            //判断订单是否需要报关
            if ($product['outside'] == 2 || $product['outside'] == 3) {
                $this->_need_kouan = 1;
            }

            //计算运费
            if (!empty($this->_address)) {
                $address = $this->model('address')->where('id=?', [$this->_address])->find();
                if (!empty($address)) {
                    if (empty($this->model('product_province')->where('province_id=? and product_id=?', [$address['province'], $p['id']])->find())) {
                        //不再包邮地区
                        //普通商品和进口商品 按照包裹数量计算运费 包裹数量其实就是仓库数量
                        if (($product['outside'] == 0 || $product['outside'] == 1) && !in_array($product['store'], $_current_store_cal_fee)) {
                            $_current_product_fee = $product['fee'];
                            $feeamount += $_current_product_fee;

                            //标记一下当前商品对应的仓库已经收过运费了
                            $_current_store_cal_fee[] = $product['store'];

                            //标记当前商品收的运费金额
                            $this->_feeamount_detail[$product['id']] = $product['fee'];
                        }
                        if ($product['outside'] == 2 || $product['outside'] == 3) {
                            $_current_product_fee = $product['fee'];
                            $feeamount += $_current_product_fee;

                            //标记当前商品收的运费金额
                            $this->_feeamount_detail[$product['id']] = $product['fee'];
                        }
                    } else {
                        //在包邮地区
                        $_current_product_fee = 0;
                        $feeamount += 0;
                    }
                }
            }

            //根据规格 计算商品价格
            if (!empty($p['content'])) {
                $collection_price = $this->model('collection')->get($p['id'], $p['content']);
                if (!empty($collection_price)) {
                    $product['price'] = $collection_price['price'];

                    $product['v1price'] = $collection_price['v1price'];
                    $product['v2price'] = $collection_price['v2price'];
                    $product['image'] = $this->model('upload')->get($collection_price['logo'], 'path');
                    $product['sku'] = $collection_price['sku'];
                }
            }

            //计算捆绑价格
            $productHelper = new \application\helper\product();
            $priceInBind = $productHelper->getPriceByBind($p);

            if ($priceInBind) {

                $product['price'] = $priceInBind['price'];

                $product['v1price'] = $priceInBind['v1price'];
                $product['v2price'] = $priceInBind['v2price'];
                $product['selled'] = $priceInBind['num'];
            }


            //对于团购商品可能需要强制性的价格
            if (isset($p['price'])) {
                $product['v2price'] = $product['v1price'] = $product['price'] = $p['price'];
            }

            switch (intval($user['vip'])) {
                case 0:
                    $totalamount += $product['price'] * $p['num'] * $productHelper->getSelled($p);
                    $_current_product_unit_price = $product['price'];

                    break;
                case 1:
                    $totalamount += $product['v1price'] * $p['num'] * $productHelper->getSelled($p);
                    $_current_product_unit_price = $product['v1price'];
                    break;
                case 2:
                    $totalamount += $product['v2price'] * $p['num'] * $productHelper->getSelled($p);
                    $_current_product_unit_price = $product['v2price'];
                    break;
                default:
                    $totalamount += $product['price'] * $p['num'] * $productHelper->getSelled($p);
                    $_current_product_unit_price = $product['price'];
            }

            if ($product['outside'] == 3) {
                if ($product['freetax'] == 0) {
                    //计算行邮税应征数额
                    $tax_percent = $productHelper->getTaxFields($product['id']);
                    $tax = $tax_percent * ($_current_product_unit_price * $p['num'] * $productHelper->getSelled($p) + $_current_product_fee);

                    if (isset($_current_store_cal_tax[$product['store']])) {
                        $_current_store_cal_tax[$product['store']] += $tax;
                    } else {
                        $_current_store_cal_tax[$product['store']] = $tax;
                    }

                    $this->_current_posttax_product[] = $product['id'];
                }
            } else {
                //计算税费 记得要包含当前商品的运费
                $taxamount += $productHelper->calculationTax($product['id'], $_current_product_unit_price * $p['num'] * $productHelper->getSelled($p) + $_current_product_fee);
            }
        }

        //把行邮税计算进去
        foreach ($_current_store_cal_tax as $store => $tax) {
            if ($tax >= 50) {
                $taxamount += $tax;
            }
        }

        $taxamount = floatval(number_format($taxamount, 2, '.', ''));
        $orderamount = $totalamount + $taxamount + $feeamount;
        $discount = 0;

        //计算优惠卷
        if (!empty($this->_coupon)) {
            $coupon = $this->model('coupon')->where('(endtime>=? or endtime=0) and used=? and isdelete=? and id=? and uid=?', [$_SERVER['REQUEST_TIME'], 0, 0, $this->_coupon, $this->_uid])->find();
            if (!empty($coupon)) {
                //以商品价格为准
                if ($coupon['max'] <= $totalamount) {
                    if (empty($coupon['product_id']) || $this->inProduct($coupon['product_id'])) {
                        $discount = $coupon['value'];
                        $orderamount -= $coupon['value'];
                        $this->_used_coupon_id = $this->_coupon;
                        $this->_usedCoupon = true;
                    }
                }
            }
        }

        $this->_money = min([$this->_money, floatval($user['money']), $orderamount]);

        //确保订单金额必须大于等于0
        if (floatval($orderamount) < 0) {
            $orderamount = 0;
        }

        return [
            'money' => $this->_money,
            'totalamount' => number_format($totalamount, 2, '.', ''),//商品总价
            'feeamount' => number_format($feeamount, 2, '.', ''),//运费
            'taxamount' => number_format($taxamount, 2, '.', ''),//税款
            'discount' => number_format($discount, 2, '.', ''),//优惠金额
            'orderamount' => number_format($orderamount, 2, '.', ''),//订单金额
        ];
    }

    /**
     * 当前购买的商品是否包含指定商品
     * @param unknown $product_id
     * @return boolean
     */
    function inProduct($product_id)
    {
        if (!empty($product_id)) {
            foreach ($this->_product as $product) {
                if ($product['id'] == $product_id) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 当前订单是否使用了有效的优惠券
     * @return boolean
     */
    function usedCoupon()
    {
        return $this->_usedCoupon;
    }

    /**
     * 获取使用的优惠券id
     */
    function getCouponId()
    {
        return $this->_used_coupon_id;
    }

    /**
     * 创建订单数据
     */
    function createOrderData($uid, $product, $coupon, $address, $money = 0, $msg = '', $invoice = '')
    {
        $this->_uid = $uid;
        $this->_product = $product;
        $this->_coupon = $coupon;
        $this->_address = $address;
        $this->_money = $money;
        $this->_msg = $msg;
        $this->_invoice = $invoice;

        $amount = $this->totalamount();

        return [
            'uid' => $uid,
            'orderno' => $this->getOrderno($uid),
            'pay_status' => $amount['money'] >= $amount['orderamount'] ? 1 : 0,
            'pay_type' => $amount['money'] >= $amount['orderamount'] ? 'money' : '',
            'pay_number' => '',
            'pay_money' => 0,
            'pay_time' => 0,
            'taxamount' => $amount['taxamount'],
            'feeamount' => $amount['feeamount'],
            'goodsamount' => $amount['totalamount'],
            'discount' => $amount['discount'],
            'orderamount' => $amount['orderamount'],
            'money' => $amount['money'],
            'address' => $address,
            'way_status' => 0,
            'way_type' => 0,
            'way_time' => 0,//订单发货时间
            'createtime' => $_SERVER['REQUEST_TIME'],
            'status' => 1,
            'note' => '',
            'msg' => $msg,
            'invoice' => $invoice,
            'personal' => 0,
            'personal_time' => 0,
            'ordered' => 0,
            'ordered_time' => 0,
            'payed' => 0,
            'payed_time' => 0,
            'kouan' => 0,
            'kouan_time' => 0,
            'kouan_result' => '',
            'coupon' => $this->_usedCoupon ? $this->_used_coupon_id : NULL,
            'quittime' => 0,
            'erp' => 0,
            'erp_time' => 0,
            'receive' => 0,//订单是否收货
            'receive_time' => 0,//订单收货时间
            'refundtime' => 0,
            'device' => '',
            'need_kouan' => $this->_need_kouan,
            'isdelete' => 0,
            'deletetime' => 0,
        ];
    }

    /**
     * 判断data中是否已经存在该仓库
     * @param unknown $store
     * @param array $data
     * @return boolean|unknown
     */
    private function storeExistInData($store, array $data)
    {
        if (empty($data))
            return false;
        foreach ($data as $index => $d) {
            if ($d['store_id'] == $store)
                return $index;
        }
        return false;
    }

    /**
     * 订单包裹数据
     */
    function createPackageData()
    {
        $data = [];

        $user = $this->model('user')->where('id=?', [$this->_uid])->find();

        $productHelper = new \application\helper\product();

        foreach ($this->_product as $p) {
            $product = $this->model('product')->where('id=?', [$p['id']])->find();

            //计算商品价格
            switch ($user['vip']) {
                case '0':
                    $price = $product['price'];
                    break;
                case '1':
                    $price = $product['v1price'];
                    break;
                case '2':
                    $price = $product['v2price'];
                    break;
                default:
                    $price = $product['price'];
            }

            if (!empty($p['content'])) {
                $price_collection = $this->model('collection')->where('pid=? and content=?', [$p['id'], $p['content']])->find();
                if (!empty($price_collection)) {
                    switch ($user['vip']) {
                        case '0':
                            $price = $price_collection['price'];
                            break;
                        case '1':
                            $price = $price_collection['v1price'];
                            break;
                        case '2':
                            $price = $price_collection['v2price'];
                            break;
                        default:
                            $price = $price_collection['price'];
                    }
                }
            }

            //计算捆绑价格
            $priceInBind = $productHelper->getPriceByBind($p);
            if ($priceInBind) {
                switch ($user['vip']) {
                    case '0':
                        $price = $priceInBind['price'];
                        break;
                    case '1':
                        $price = $priceInBind['v1price'];
                        break;
                    case '2':
                        $price = $priceInBind['v2price'];
                        break;
                    default:
                        $price = $priceInBind['price'];
                }
            }

            //假如存在指定价格，按照指定价格计算
            if (isset($p['price'])) {
                $price = $p['price'];
            }

            //直供和直邮 按照sku分配包裹
            if ($product['outside'] == 2 || $product['outside'] == 3) {
                $index = false;
            } else {
                $index = $this->storeExistInData($product['store'], $data);
            }

            //计算单商品的税费
            if ($product['outside'] == 3) {
                if ($product['freetax'] == 0 && in_array($p['id'], $this->_current_posttax_product)) {
                    $tax_percent = $productHelper->getTaxFields($p['id']);
                    $tax = $tax_percent * $p['num'] * $price * $productHelper->getSelled($p);
                } else {
                    $tax = 0;
                }
            } else {
                $tax = $productHelper->calculationTax($p['id'], $price * $p['num'] * $productHelper->getSelled($p));
            }

            if ($index !== false) {
                $data[$index]['product'][] = [
                    'pid' => $p['id'],
                    'content' => $p['content'],
                    'num' => $p['num'],
                    'bind' => $productHelper->getSelled($p),
                    'price' => $price,
                    'refund' => 0,
                    'refundmoney' => 0,
                    'refundtime' => 0,
                    'tax' => $tax,
                    'fee' => isset($this->_feeamount_detail[$p['id']]) ? $this->_feeamount_detail[$p['id']] : 0,
                ];
                $data[$index]['ship_money'] += isset($this->_feeamount_detail[$p['id']]) ? $this->_feeamount_detail[$p['id']] : 0;
            } else {
                $data[] = [
                    'orderno' => NULL,
                    'ship_status' => 0,
                    'ship_type' => '',
                    'ship_time' => 0,
                    'ship_number' => '',
                    'ship_money' => isset($this->_feeamount_detail[$p['id']]) ? $this->_feeamount_detail[$p['id']] : 0,
                    'store_id' => $product['store'],
                    'product' => [[
                        'pid' => $p['id'],
                        'num' => $p['num'],
                        'bind' => $productHelper->getSelled($p),
                        'price' => $price,
                        'content' => $p['content'],
                        'refund' => 0,
                        'refundmoney' => 0,
                        'refundtime' => 0,
                        'tax' => $tax,
                        'fee' => isset($this->_feeamount_detail[$p['id']]) ? $this->_feeamount_detail[$p['id']] : 0,
                    ]]
                ];
            }
        }

        return $data;
    }

    /**
     * 生成订单号
     */
    private function getOrderno($uid)
    {
        do {
            $orderno = date('ymdHis') . $uid . random::number(4);
        } while (!empty($this->model('order')->where('orderno=?', [$orderno])->find()));
        return $orderno;
    }

    /**
     * 获得订单中的所有商品
     */
    function getProduct($orderno)
    {
        $temp = [];
        $product = $this->model('order')->table('order_package', 'left join', 'order.orderno=order_package.orderno')->table('order_product', 'left join', 'order_package.id=order_product.package_id')->where('order.orderno=?', [$orderno])->select('order_product.pid,order_product.num,order_product.content');
        foreach ($product as $p) {
            if (!empty($p['content'])) {
                $collection = $this->model('collection')->where('pid=? and content=?', [$p['pid'], $p['content']])->find();
                if (!empty($collection)) {
                    $temp_product = $this->model('product')->where('id=?', [$p['pid']])->find();
                    $temp_product['price'] = $collection['price'];
                    $temp_product['v1price'] = $collection['v1price'];
                    $temp_product['v2price'] = $collection['v2price'];
                    $temp_product['sku'] = $collection['sku'];
                    $temp_product['stock'] = $collection['stock'];
                } else {
                    $temp_product = $this->model('product')->where('id=?', [$p['pid']])->find();
                }
            } else {
                $temp_product = $this->model('product')->where('id=?', [$p['pid']])->find();
            }
            $temp_product['num'] = $p['num'];
            $temp[] = $temp_product;
        }
        return $temp;
    }

    /**
     * 订单完成支付
     */
    function payedOrder($orderno, $pay_type, $pay_number, $pay_money, array $options = [])
    {
        $order = $this->model('order')->where('orderno=?', [$orderno])->find();
        if (!empty($order) && $order['pay_status'] == '0' && $order['orderamount'] == $pay_money) {
            $data = [
                'pay_status' => 1,
                'pay_type' => $pay_type,
                'pay_number' => $pay_number,
                'pay_money' => $pay_money,
                'pay_time' => $_SERVER['REQUEST_TIME']
            ];
            if (!empty($options)) {
                $data = array_merge($data, $options);
            }

            $this->model('order')->transaction();

            if (!$this->model('order')->where('orderno=?', [$orderno])->limit(1)->update($data)) {
                $this->model('order')->rollback();
                return false;
            }

            $class_name = '\application\callback\order';
            if (class_exists($class_name, true) && method_exists($class_name, 'payedOrder') && is_callable([$class_name, 'payedOrder'])) {
                if (!call_user_func([new $class_name(), 'payedOrder'], $orderno)) {
                    $this->model('order')->rollback();
                    return false;
                }
            }

            if ($this->model('order_log')->add($orderno, '订单支付成功，等待处理')) {
                $this->model('order')->commit();
                //判断仓库是否是自动推送
                $is_auto = $this->model("store")
                    ->table("order_package", "left join", "order_package.store_id=store.id")
                    ->where("order_package.orderno=?", [$orderno])
                    ->select(['is_auto']);
                $bl = true;
                foreach ($is_auto as $auto) {
                    if ($auto['is_auto'] == 0) {
                        $bl=false;
                        break;
                    }
                }
                if($bl) {
                    $erpSender = new erpSender();
                    $erpSender->doSendOrder($orderno);
                }

                return true;
            } else {
                $this->model('order')->rollback();
                return false;
            }

        }
        return false;
    }

    /**
     * 取消订单
     */
    function quitOrder($orderno, $transaction = true)
    {
        $order = $this->model('order')->where('orderno=?', [$orderno])->find();
        if (!empty($order)) {
            if ($order['status'] != 1) {
                return false;
            }

            if ($order['pay_status'] == 1) {
                return false;
            }

            if ($transaction) {
                $this->model('order')->transaction();
            }

            if ($this->model('order')->where('orderno=?', [$orderno])->limit(1)->update([
                'status' => 0,
                'quittime' => $_SERVER['REQUEST_TIME']
            ])
            ) {
                //回退余额
                if ($order['money'] > 0) {
                    if (!$this->model('user')->where('id=?', [$order['uid']])->limit(1)->increase('money', $order['money'])) {
                        if ($transaction) {
                            $this->model('order')->rollback();
                        }
                        return false;
                    }
                }

                //回退优惠券
                if (!empty($order['coupon'])) {

                    if (!$this->model('coupon')->where('id=?', [$order['coupon']])->limit(1)->update([
                        'used' => 0,
                        'usedtime' => 0
                    ])
                    ) {
                        if ($transaction) {
                            $this->model('order')->rollback();
                        }

                        return false;
                    }
                }

                //退回商品
                $product = $this->model('order')
                    ->table('order_package', 'left join', 'order_package.orderno=order.orderno')
                    ->table('order_product', 'left join', 'order_package.id=order_product.package_id')
                    ->where('order.orderno=?', [$orderno])
                    ->select([
                        'order_product.pid',
                        'order_product.num',
                        'order_product.content'
                    ]);
                $productHelper = new \application\helper\product();
                foreach ($product as $p) {
                    if (!$productHelper->increaseStock($p['pid'], $p['content'], $p['num'])) {
                        if ($transaction) {
                            $this->model('order')->rollback();
                        }

                        return false;
                    }
                }

                $this->model('order_log')->add($orderno, '订单取消成功');

                if ($transaction) {
                    $this->model('order')->commit();
                }
                return true;
            }
        }
        if ($transaction) {
            $this->model('order')->rollback();
        }
        return false;
    }
    
    /**
     * 获取退款失败时候的错误原因
     */
    function getRefundError()
    {
    	return isset($this->_refund_msg)?$this->_refund_msg:'';
    }

    /**
     * 订单退款
     * @param string $orderno 订单号
     * @param string $order_product_id 订单中的商品id 新增参数，假如存在这个参数为退款部分商品
     * @return boolean
     */
    function refund($orderno, $order_product_id = NULL)
    {

        $order = $this->model('order')->where('orderno=?', [$orderno])->find();
        if (!empty($order)) {
            if ($order['pay_status'] != 1 && $order['pay_status'] != 4) {
                return false;
            }

            //计算要退款的金额
            if (empty($order_product_id)) {

                //查找订单尚未退款的金额
                $extra_money = $this->model('order_package')
                    ->table('order_product', 'left join', 'order_product.package_id=order_package.id')
                    ->where('order_package.orderno=?', [$orderno])
                    ->where('order_product.refund!=?', [0])
                    ->find('sum(order_product.refundmoney) as extra_money');
                $extra_money = isset($extra_money['extra_money']) ? $extra_money['extra_money'] : 0;
                //累计支付金额 - 已经退款金额 = 预计要退款的金额
                $money = $order['pay_money'] - $extra_money;
                if ($money <= 0) {
                    return false;
                }
            } else {
                $order_product = $this->model('order_package')
                    ->table('order_product', 'left join', 'order_product.package_id=order_package.id')
                    ->where('order_package.orderno=?', [$orderno])
                    ->where('order_product.id=?', [$order_product_id])
                    ->find('order_product.*');
                if ($order_product['refund'] != 0) {
                    return false;
                }

                //计算应该退款金额 包含税费
                if (empty($order['coupon'])) {
                    $money = $order_product['price'] * $order_product['num'] * $order_product['bind'] + $order_product['tax'];
                } else {
                    $coupon = $this->model('coupon')->where('id=?', [$order['coupon']])->find();
                    if (empty($coupon['product_id'])) {
                        $money = $order_product['price'] * $order_product['num'] * $order_product['bind'] + $order_product['tax'] - ($order_product['price'] * $order_product['num'] + $order_product['tax']) / ($order['goodsamount'] + $order['taxamount']) * $coupon['value'];
                    } else {
                        if ($coupon['product_id'] == $order_product['pid']) {
                            $money = $order_product['price'] * $order_product['num']* $order_product['bind'] + $order_product['tax'] - $coupon['value'];
                        } else {
                            $money = $order_product['price'] * $order_product['num'] * $order_product['bind'] + $order_product['tax'];
                        }
                    }
                }

            }

            //创建退款订单数据
            do {
                $refundno = date('YmdHis') . random::number(6);
            } while (!empty($this->model('refund')->where('refundno=?', [$refundno])->find()));

            //记录退款数据
            if (!$this->model('refund')->insert([
                'refundno' => $refundno,
                'orderno' => $orderno,
                'order_product_id' => $order_product_id,
                'status' => 0,
                'createtime' => $_SERVER['REQUEST_TIME'],
                'completetime' => 0,
                'money' => $money,
                'reason' => ''
            ])
            ) {
                return false;
            }

            $partner = $this->model('system')->get('partner', $order['pay_type']);
            $key = $this->model('system')->get('key', $order['pay_type']);
            $appid = $this->model('system')->get('appid', 'wechat');
            //app的订单的appid和商户号和加密的密钥不一样
            $client_cert = $this->model('system')->get('client_cert', 'wechat');
            $client_key = $this->model('system')->get('client_key', 'wechat');

            if ($order['pay_type'] == 'wechat') {
                if ($order['device'] == 'ios' || $order['device'] == 'android') {
                    $partner = $this->model('system')->get('app_partner', 'wechat');
                    $key = $this->model('system')->get('app_key', 'wechat');
                    $appid = $this->model('system')->get($order['device'] . '_appid', 'wechat');
                    $client_cert = $this->model('system')->get('app_client_cert', 'wechat');
                    $client_key = $this->model('system')->get('app_client_key', 'wechat');
                }
            }
            $pay = new \application\helper\pay();

            $pay->createParameter([
                'client_cert' => $client_cert,
                'client_key' => $client_key,
                'appid' => $appid,
                'pay_money' => $order['pay_money'],//支付的总金额
            ]);

            $pay->setSigntype('MD5');
            $pay->setPayType($order['pay_type']);
            $pay->setPaynumber($order['pay_number']);
            $pay->setPartner($partner);
            $pay->setCharset('UTF-8');
            $pay->setKey($key);
            $pay->setMoney($money);//设置退款金额
            $pay->setId($refundno);
            $response = $pay->createRefundParameter();
			//记录退款的报文
            $this->model('refund')->where('refundno=?',[$refundno])->update('reason',json_encode($response,JSON_UNESCAPED_UNICODE));
			
            if ($order['pay_type'] == 'wechat') {
                if ($response['return_code'] == 'SUCCESS') {
                    if ($response['result_code'] == 'SUCCESS') {
                        //开启事务
                        $this->model('order')->transaction();

                        if (empty($order_product_id)) {
                            //标记订单状态为退款成功
                            if ($this->model('order')->where('orderno=?', [$orderno])->limit(1)->update([
                                'pay_status' => 2,
                                'refundtime' => $_SERVER['REQUEST_TIME'],
                            ])
                            ) {
                                //订单退款成功 调用相应的回调
                                $class_name = '\application\callback\order';
                                if (class_exists($class_name, true) && method_exists($class_name, 'refundOrder') && is_callable([$class_name, 'refundOrder'])) {
                                    if (!call_user_func([new $class_name(), 'refundOrder'], $refundno)) {
                                        $this->model('order')->rollback();
                                        return false;
                                    }
                                }
                                $this->model('order')->commit();
                                return true;
                            } else {
                                $this->model('order')->rollback();
                                return false;
                            }
                        } else {
                            //商品退款的话 标记商品已经退款
                            if ($this->model('order_package')
                                ->table('order_product', 'left join', 'order_product.package_id=order_package.id')
                                ->where('order_package.orderno=?', [$orderno])
                                ->where('order_product.id=?', [$order_product_id])
                                ->update([
                                    'refund' => 1,
                                    'refundmoney' => $money,
                                    'refundtime' => $_SERVER['REQUEST_TIME']
                                ])
                            ) {
                                //商品退款完毕后标记订单为部分退款
                                $this->model('order')->where('orderno=?', [$orderno])->limit(1)->update([
                                    'pay_status' => 4,
                                ]);

                                //商品退款成功 调用相应的回调
                                $class_name = '\application\callback\refund';
                                if (class_exists($class_name, true) && method_exists($class_name, 'product') && is_callable([$class_name, 'product'])) {
                                    if (!call_user_func([new $class_name(), 'product'], $refundno)) {
                                        $this->model('order')->rollback();
                                        return false;
                                    }
                                }

                                $this->model('order')->commit();

                                return true;
                            } else {
                                $this->model('order')->rollback();
                                return false;
                            }
                        }
                    } else {
                    	//记录错误原因
                    	$this->_refund_msg = $response['err_code_des'];
                        return false;
                    }
                } else {
                    return false;
                }
            } else if ($order['pay_type'] == 'alipay') {

                if (isset($response['is_success']) && strtoupper($response['is_success']) === 'T') {

                    //开启事务
                    $this->model('order')->transaction();

                    if (empty($order_product_id)) {
                        //订单退款 标记订单为正在退款
                        if ($this->model('order')->where('orderno=?', [$orderno])->update([
                            'pay_status' => 3,
                        ])
                        ) {
                            if ($this->model('order_log')->add($orderno, '订单申请退款，等待处理')) {
                                $this->model('order')->commit();
                                return true;
                            } else {
                                $this->model('order')->rollback();
                                return false;
                            }
                        } else {
                            $this->model('order')->rollback();
                            return false;
                        }
                    } else {
                        if ($this->model('order_package')
                            ->table('order_product', 'left join', 'order_package.id=order_product.package_id')
                            ->where('order_package.orderno=?', [$orderno])
                            ->where('order_product.id=?', [$order_product_id])
                            ->update([
                                'order_product.refund' => 2,
                                'order_product.refundmoney' => $money,
                                'order_product.refundtime' => $_SERVER['REQUEST_TIME'],
                            ])
                        ) {

                            $this->model('order')->commit();
                            return true;
                        } else {

                            $this->model('order')->rollback();
                            return false;
                        }
                    }
                } else {
                    return false;
                }
            }
        }
    }
}