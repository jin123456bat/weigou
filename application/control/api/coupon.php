<?php
namespace application\control\api;

use application\helper\user;
use application\message\json;

class coupon extends common
{
    private $_response;

    function __construct()
    {
        parent::__construct();
        $this->_response = $this->init();
    }

    /**
     * 根据优惠编码兑换优惠券
     */
    function couponno()
    {
        if (!empty($this->_response))
            return $this->_response;

        $userHelper = new user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $couponno = $this->data('couponno');
        if (empty($couponno))
            return new json(json::PARAMETER_ERROR, '优惠编码不能为空');

        $couponnoResult = $this->model('couponno')->where('couponno=? and isdelete=?', [$couponno, 0])->find();
        if (empty($couponnoResult)) {
            return new json(json::PARAMETER_ERROR, '优惠编码错误');
        }

        if ($couponnoResult['couponno_starttime'] > $_SERVER['REQUEST_TIME']) {
            return new json(json::PARAMETER_ERROR, '抱歉，这个优惠编码还没到领取时间');
        }

        if ($couponnoResult['couponno_endtime'] != 0 && $couponnoResult['couponno_endtime'] < $_SERVER['REQUEST_TIME']) {
            return new json(json::PARAMETER_ERROR, '抱歉，这个优惠码已经过期了');
        }

        $couponLogResult = $this->model('couponno_log')->where('couponno=? and user_id=?', [$couponno, $uid])->select();
        if (count($couponLogResult) >= $couponnoResult['limittimes'])
            return new json(json::PARAMETER_ERROR, '领取次数超过限制');

        if ($couponnoResult['total'] == 0 || ($couponnoResult['total'] != 0 && $couponnoResult['times'] > 0)) {
            if ($couponnoResult['coupon_time_type'] == 0) {
                $endtime = ($couponnoResult['coupon_time'] == 0) ? 0 : ($couponnoResult['coupon_time'] * 24 * 3600 + $_SERVER['REQUEST_TIME']);
            } else if ($couponnoResult['coupon_time_type'] == 1) {
                $endtime = $couponnoResult['coupon_endtime'];
            }

            $couponModel = [
                'value' => $couponnoResult['coupon_value'],
                'max' => $couponnoResult['coupon_max'],
                'uid' => $uid,
                'name' => $couponnoResult['coupon_name'],
                'createtime' => $_SERVER['REQUEST_TIME'],
                'endtime' => $endtime,
                'isdelete' => 0,
                'deletetime' => 0,
                'source' => 1,
                'couponno' => $couponno,
                'used' => 0,
                'usedtime' => 0,
                'product_id' => $couponnoResult['product_id'],
            ];
            $this->model('coupon')->transaction();
            if ($this->model('coupon')->insert($couponModel)) {
                $couponModel['id'] = $this->model('coupon')->lastInsertId();
                if ($couponnoResult['total'] != 0) {
                    if (!$this->model('couponno')->where('couponno=?', [$couponno])->increase('times', -1)) {
                        $this->model('coupon')->rollback();
                        return new json(json::PARAMETER_ERROR, '优惠券次数减少失败');
                    }
                }

                if (!$this->model('couponno_log')->insert([
                    'couponno' => $couponno,
                    'user_id' => $uid,
                    'coupon_id' => $couponModel['id'],
                    'time' => $_SERVER['REQUEST_TIME'],
                ])
                ) {
                    $this->model('coupon')->rollback();
                    return new json(json::PARAMETER_ERROR, '优惠券领取记录失败');
                }

                $couponReturnModel = [
                    'id' => $couponModel['id'],
                    'max' => $couponModel['max'],
                    'name' => $couponModel['name'],
                    'value' => $couponModel['value'],
                    'product_id' => $couponModel['product_id'],
                ];

                $this->model('coupon')->commit();
                return new json(json::OK, NULL, $couponReturnModel);
            }
            $this->model('coupon')->rollback();
            return new json(json::PARAMETER_ERROR);
        }
        return new json(json::PARAMETER_ERROR, '对不起，这个优惠券已经被领完了');
    }


    /**
     * 我的优惠券列表
     */
    function mylists()
    {
        $userHelper = new user();
        $uid = $userHelper->isLogin();

        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $filter = [
            'start' => $this->data('start'),
            'length' => $this->data('length'),
            'isdelete' => 0,
            'uid' => $uid,
            'used' => 0,
            'parameter' => [
                'coupon.id',
                'coupon.name',
                'coupon.createtime',
                'coupon.endtime',
                'coupon.max',
                'coupon.value',
                'coupon.product_id',

            ]
        ];

        $coupon = $this->model('coupon')->where("endtime>unix_timestamp(now())")->fetch($filter);

        foreach ($coupon as &$cou) {
            $cou['product_id'] = empty($cou['product_id']) ? 0 : 1;
            $cou['createtime'] = date('Y-m-d ', $cou['createtime']);
            $cou['endtime'] = date('Y-m-d ', $cou['endtime']);
        }


        $filter['parameter'] = 'count(*)';
        unset($filter['start']);
        unset($filter['length']);
        $total = $this->model('coupon')->fetch($filter);

        $couponReturnModel = [
            'current' => count($coupon),
            'total' => isset($total[0]['count(*)']) ? $total[0]['count(*)'] : 0,
            'start' => $this->get('start', 0),
            'length' => $this->get('length', 10),
            'data' => $coupon,
        ];

        return new json(json::OK, NULL, $couponReturnModel);
    }


    /**
     * 我的失效优惠券列表
     * copy
     */
    function mylistsfailure()
    {
        $userHelper = new user();
        $uid = $userHelper->isLogin();

        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $filter = [
            'start' => $this->data('start'),
            'length' => $this->data('length'),
            'uid' => $uid,
            'used' => 0,
            'parameter' => [
                'coupon.id',
                'coupon.name',
                'coupon.createtime',
                'coupon.endtime',
                'coupon.max',
                'coupon.value',
                'coupon.product_id',

            ]
        ];

        $coupon = $this->model('coupon')->where("(isdelete=1 or used=1 or endtime<unix_timestamp(now())) and uid=?", [$uid])->select([
            'coupon.id',
            'coupon.name',
            'coupon.createtime',
            'coupon.endtime',
            'coupon.max',
            'coupon.value',
            'coupon.product_id',
            'coupon.isdelete',
            'coupon.used',
        ]);

        foreach ($coupon as &$cou) {
            $cou['product_id'] = empty($cou['product_id']) ? 0 : 1;
            $cou['createtime'] = date('Y-m-d ', $cou['createtime']);
            $cou['endtime'] = date('Y-m-d', $cou['endtime']);
        }


        $filter['parameter'] = 'count(*)';
        unset($filter['start']);
        unset($filter['length']);
        $total = $this->model('coupon')->fetch($filter);

        $couponReturnModel = [
            'current' => count($coupon),
            'total' => isset($total[0]['count(*)']) ? $total[0]['count(*)'] : 0,
            'start' => $this->get('start', 0),
            'length' => $this->get('length', 10),
            'data' => $coupon,
        ];

        return new json(json::OK, NULL, $couponReturnModel);
    }


    /**
     * 新增
     * 动态加载我的优惠卷列表
     */
    function mylistsDynamic()
    {
        if (!empty($this->_response)) {
            return $this->_response;
        }

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            return new json(json::NOT_LOGIN);
        }

        //product的结构
        //[['id':1,'content':'','num':5]]
        $product = $this->data('product', '');
        $product = json_decode($product, true);
        if (empty($product)) {
            return new json(json::PARAMETER_ERROR, '请选择要购买的商品');
        }

        //获取我的所有的优惠卷
        $filter = [
            'isdelete' => 0,
            'uid' => $uid,
            'parameter' => [
                'coupon.id',
                'coupon.name',
                'coupon.endtime',
                'coupon.max',
                'coupon.value',
                'coupon.used',
                'coupon.product_id',
            ]
        ];
        $coupon = $this->model('coupon')->fetch($filter);

        //不可以使用的优惠卷
        $coupon_invalid = [];
        //可以使用的优惠卷
        $coupon_valid = [];

        $orderHelper = new \application\helper\order();
        foreach ($coupon as $c) {
            //检查优惠卷是否可以使用
            $orderHelper->createOrderData($uid, $product, $c['id'], '');
            if ($orderHelper->usedCoupon()) {
                $coupon_valid[] = $c;
            } else {
                $coupon_invalid[] = $c;
            }
        }

        return new json(json::OK, NULL, [
            'invalid' => $coupon_invalid,
            'valid' => $coupon_valid,
        ]);
    }
}