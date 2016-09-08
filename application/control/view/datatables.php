<?php
namespace application\control\view;

use system\core\view;
use application\message\json;

class datatables extends view
{
    function __construct()
    {
        $this->_csrf_token_refresh = false;
        parent::__construct();
    }

    function taskorder()
    {
        $resultObj = new \stdClass();
        if ($this->post('customActionType') == 'group_action') {
            switch ($this->post('customActionName')) {
                default:
            }
        }
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('task_user')->datatables($this->post());
        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        $resultObj->recordsTotal = $this->model('task_user')->count();
        return new json($resultObj);
    }

    function product()
    {

        if ($this->post('customActionType') == 'group_action') {
            $post_id = $this->post('id');
            if (is_array($post_id) && !empty($post_id)) {
                foreach ($post_id as $id) {
                    switch ($this->post('customActionName')) {
                        case 'remove':
                            $this->model('product')->where('id=?', [$id])->limit(1)->update([
                                'isdelete' => 1,
                                'deletetime' => $_SERVER['REQUEST_TIME']
                            ]);
                        default:
                    }
                }
            }
        }

        $resultObj = new \stdClass();
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('product')->where('product.isdelete=?', [0])->datatables($this->post());
        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }

        foreach ($resultObj->data as &$product) {
            //商品分类
            $filter = [
                'pid' => $product['id'],
                'parameter' => 'category.name as category',
                'isdelete' => 0,
            ];
            $product['category'] = [];
            foreach ($this->model('category')->fetchAll($filter) as $category) {
                $product['category'][] = $category['category'];
            }

            //商品价格
            $filter = [
                'pid' => $product['id'],
                'isdelete' => 0,
                'available' => 1,
                'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)',
            ];
            $price_collection = $this->model('collection')->fetch($filter);
            if (!empty($price_collection)) {
                if ($price_collection[0]['sum(stock)'] !== NULL) {
                    $product['stock'] = $price_collection[0]['sum(stock)'];
                }
                if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)'] !== NULL) {
                    $product['price'] = $price_collection[0]['min(price)'] . '~' . $price_collection[0]['max(price)'];
                }
                if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL) {
                    $product['v1price'] = $price_collection[0]['min(v1price)'] . '~' . $price_collection[0]['max(v1price)'];
                }
                if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL) {
                    $product['v2price'] = $price_collection[0]['min(v2price)'] . '~' . $price_collection[0]['max(v2price)'];
                }
            }
        }
        $resultObj->recordsTotal = $this->model('product')->where('product.isdelete=?', [0])->count();
        die(json_encode($resultObj));
        return new json($resultObj);
    }

    function recycle()
    {
        if ($this->post('customActionType') == 'group_action') {
            $post_id = $this->post('id');
            if (is_array($post_id) && !empty($post_id)) {
                foreach ($post_id as $id) {
                    switch ($this->post('customActionName')) {
                        case 'remove':
                            $this->model('product')->where('id=?', [$id])->delete();
                        case 'restore':
                            $this->model('product')->where('id=?', [$id])->limit(1)->update('isdelete', 0);
                        default:
                    }
                }
            }
        }


        $resultObj = new \stdClass();
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('product')->where('product.isdelete=?', [1])->datatables($this->post());
        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        foreach ($resultObj->data as &$product) {
            //商品分类
            $filter = [
                'pid' => $product['id'],
                'parameter' => 'category.name as category',
                'isdelete' => 0,
            ];
            $product['category'] = [];
            foreach ($this->model('category')->fetchAll($filter) as $category) {
                $product['category'][] = $category['category'];
            }

            //商品价格
            $filter = [
                'pid' => $product['id'],
                'isdelete' => 0,
                'available' => 1,
                'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)',
            ];
            $price_collection = $this->model('collection')->fetch($filter);
            if (!empty($price_collection)) {
                if ($price_collection[0]['sum(stock)'] !== NULL) {
                    $product['stock'] = $price_collection[0]['sum(stock)'];
                }
                if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)'] !== NULL) {
                    $product['price'] = $price_collection[0]['min(price)'] . '~' . $price_collection[0]['max(price)'];
                }
                if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL) {
                    $product['v1price'] = $price_collection[0]['min(v1price)'] . '~' . $price_collection[0]['max(v1price)'];
                }
                if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL) {
                    $product['v2price'] = $price_collection[0]['min(v2price)'] . '~' . $price_collection[0]['max(v2price)'];
                }
            }
        }
        $resultObj->recordsTotal = $this->model('product')->where('product.isdelete=?', [1])->count();
        return new json($resultObj);
    }


    function user()
    {
        $resultObj = new \stdClass();
        if ($this->post('customActionType') == 'group_action') {
            switch ($this->post('customActionName')) {
            }
        }
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('user')->datatables($this->post());
        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        $resultObj->recordsTotal = $this->model('user')->count();
        return new json($resultObj);
    }

    /**
     * 渠道
     */
    function source_user()
    {
        $resultObj = new \stdClass();
        if ($this->post('customActionType') == 'group_action') {
            switch ($this->post('customActionName')) {
            }
        }
        $resultObj->draw = $this->post('draw');

        $resultObj->data = $this->model('user')->source_datatables($this->post(), $this->session->id);


        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        $resultObj->recordsTotal = $this->model('user')->source_count($this->session->id);
        return new json($resultObj);
    }

    /**
     * 普通渠道
     */
    function source_user2()
    {
        $resultObj = new \stdClass();
        if ($this->post('customActionType') == 'group_action') {
            switch ($this->post('customActionName')) {
            }
        }
        $resultObj->draw = $this->post('draw');

        $resultObj->data = $this->model('user')->source_datatables2($this->post(), $this->session->id);


        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        $resultObj->recordsTotal = $this->model('user')->source_count2($this->session->id);
        return new json($resultObj);
    }

    /**
     * 渠道金额统计
     */
    function source_under()
    {
        $resultObj = new \stdClass();
        if ($this->post('customActionType') == 'group_action') {
            switch ($this->post('customActionName')) {
            }
        }
        $resultObj->draw = $this->post('draw');

        $resultObj->data = $this->model('user')->sourceunder_datatables($this->post(), $this->session->id);


        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        $resultObj->recordsTotal = $this->model('user')->sourceunder_count($this->session->id);
        return new json($resultObj);
    }


    /**
     * 优惠券
     */
    function coupon()
    {
        $resultObj = new \stdClass();
        if ($this->post('customActionType') == 'group_action') {
            switch ($this->post('customActionName')) {
                case 'remove':
                    foreach ($this->post('id') as $id) {
                        $this->model('coupon')->where('id=?', [$id])->update([
                            'isdelete' => 1,
                            'deletetime' => $_SERVER['REQUEST_TIME']
                        ]);
                    }
                default:
            }
        }
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('coupon')->datatables($this->post());
        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        $resultObj->recordsTotal = $this->model('coupon')->count();
        return new json($resultObj);
    }

    /**
     * 优惠券
     */
    function couponno()
    {
        $resultObj = new \stdClass();
        if ($this->post('customActionType') == 'group_action') {
            switch ($this->post('customActionName')) {
                case 'remove':
                    foreach ($this->post('id') as $id) {
                        $this->model('couponno')->where('id=?', [$id])->limit(1)->update([
                            'isdelete' => 1,
                            'deletetime' => $_SERVER['REQUEST_TIME']
                        ]);
                    }
                default:
            }
        }
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('couponno')->datatables($this->post());
        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        $resultObj->recordsTotal = $this->model('couponno')->count();
        return new json($resultObj);
    }

    function order()
    {
        $resultObj = new \stdClass();
        if ($this->post('customActionType') == 'group_action') {
            switch ($this->post('customActionName')) {
                default:
            }
        }
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('order')->datatables($this->post());

        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));

            //判断每个订单的是否需要推送
            foreach ($resultObj->data as &$erp) {
                $erp['is_erp'] = 1;
                //判断一下是否有效
                if($erp['status']==0){
                    continue;
                }
                //是否已经付款
                if ($erp['pay_status'] == 0 || $erp['pay_status'] == 3) {
                    continue;
                }
                //是否发货
                if ($erp['way_status'] != 0) {
                    continue;
                }
                //判断当前订单仓库是否是自动的
                $is_auto = $this->model("store")
                    ->table("order_package", "left join", "order_package.store_id=store.id")
                    ->where("order_package.orderno=?", [$erp['orderno']])
                    ->select(['is_auto']);
                $bl = true;
                foreach ($is_auto as $auto) {
                    if ($auto['is_auto'] == 0) {
                        $bl = false;
                        break;
                    }
                }
                //departByStore判断是否为
                if (!$bl) {
                    $orderHelper = new \application\helper\order();
                    $depart = $orderHelper->departByStore($erp['orderno']);

                    if ($depart == 1 || $depart == 2) {
                        $erp['is_erp'] = 0;
                    }
                }
            }
        }
        $resultObj->recordsTotal = $this->model('order')->count();
        return new json($resultObj);
    }

    /*
     * 渠道订单表
     */
    function source_order()
    {
        $resultObj = new \stdClass();
        if ($this->post('customActionType') == 'group_action') {
            switch ($this->post('customActionName')) {
                default:
            }
        }
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('order')->source_datatables($this->post(), $this->session->uid, $this->session->id);
        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        $resultObj->recordsTotal = $this->model('order')->source_count($this->session->id, $this->session->uid);
        return new json($resultObj);
    }

    /*
     * 普通渠道订单表
     */
    function source_order2()
    {
        $resultObj = new \stdClass();
        if ($this->post('customActionType') == 'group_action') {
            switch ($this->post('customActionName')) {
                default:
            }
        }
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('order')->source_datatables2($this->post(), $this->session->uid, $this->session->id);
        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        $resultObj->recordsTotal = $this->model('order')->source_count2($this->session->id, $this->session->uid);
        return new json($resultObj);
    }

    function drawal()
    {
        $resultObj = new \stdClass();
        if ($this->post('customActionType') == 'group_action') {
            switch ($this->post('customActionName')) {
                default:
            }
        }
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('drawal')->datatables($this->post());
        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        $resultObj->recordsTotal = $this->model('drawal')->count();
        return new json($resultObj);
    }

    function feedback()
    {
        $resultObj = new \stdClass();
        if ($this->post('customActionType') == 'group_action') {
            switch ($this->post('customActionName')) {
                case 'remove':
                    $post_id = $this->post('id');
                    if (is_array($post_id) && !empty($post_id)) {
                        foreach ($post_id as $id) {
                            $this->model('feedback')->where('id=?', [$id])->limit(1)->update([
                                'isdelete' => 1,
                                'deletetime' => $_SERVER['REQUEST_TIME']
                            ]);
                        }
                    }
                default:
            }
        }
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('feedback')->datatables($this->post());
        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        $resultObj->recordsTotal = $this->model('feedback')->count();
        return new json($resultObj);
    }

    function package()
    {
        $resultObj = new \stdClass();
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('order_package')->datatables($this->post());
        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }

        foreach ($resultObj->data as &$package) {
            $product = $this->model('order_product')
                ->table('product', 'left join', 'product.id=order_product.pid')
                ->where('order_product.package_id=?', [$package['id']])
                ->select([
                    'product.name',
                    'order_product.content',
                    'order_product.num',
                ]);
            $package['product'] = $product;
        }
        $resultObj->recordsTotal = $this->model('order_package')->count();
        return new json($resultObj);
    }

    function viporder()
    {
        $resultObj = new \stdClass();
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('vip_order')->datatables($this->post());

        $resultObj->recordsFiltered = count($resultObj->data);

        //获取用户对应id username
        $users = $this->model('user')->select(['id', 'name']);
        $user = array();
        foreach ($users as $u) {
            $user[$u['id']] = $u['name'];
        }

        $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));

        //循环更改oid
        foreach ($resultObj->data as &$ds) {
            $ds['oid'] = empty($ds['oid']) ? '无' : $user[$ds['oid']];
        }

        $resultObj->recordsTotal = $this->model('vip_order')->count();
        return new json($resultObj);
    }

    function source_viporder()
    {


        //获取当前渠道的用户id

        //生成json

        //筛选

        //获取当前渠道的用户id

        //生成json

        //筛选
        $resultObj = new \stdClass();
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('vip_order')->vipdatatables($this->post(), $this->session->id);
        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        $resultObj->recordsTotal = $this->model('vip_order')->vipcount($this->session->id);
        foreach ($resultObj->data as &$data) {

            $data['oidname'] = $this->model("user")
                ->where("id=(select oid from user where id=?)", [$data['uid']])
                ->find(['user.name']);

            $data['oidname'] = $data['oidname']['name'];
        }
        return new json($resultObj);
    }

    function refund()
    {
        $resultObj = new \stdClass();
        $resultObj->draw = $this->post('draw');
        $resultObj->data = $this->model('refund')->datatables($this->post());
        $resultObj->recordsFiltered = count($resultObj->data);
        if ($this->post('length') != -1) {
            $resultObj->data = array_slice($resultObj->data, $this->post('start'), $this->post('length'));
        }
        $resultObj->recordsTotal = $this->model('refund')->count();
        return new json($resultObj);
    }
}
