<?php
namespace application\control\api;

use application\helper as helper;
use application\message\json;
use application\helper\product;

class cart extends common
{
    private $_response;

    private $_uid;

    function __construct()
    {
        parent::__construct();
        $this->_response = $this->init();

        $userHelper = new helper\user();
        $this->_uid = $userHelper->isLogin();
    }

    /**
     * 购物车列表
     */
    function lists()
    {
        $this->_uid=2119;
        if (empty($this->_uid))
            return new json(json::NOT_LOGIN);


        //筛选所有仓库
        $store = $this->model('cart')
            ->table('product', 'left join', 'product.id=cart.pid')
            ->table('store', 'left join', 'product.store=store.id')
            ->where('cart.uid=?', [$this->_uid])
            ->where('product.isdelete=?', [0])
            ->groupby('store.id')
            ->select('store.id,store.name');

        $productHelper = new product();

        //用户信息
        $user = $this->model('user')->where('id=?', [$this->_uid])->find();

        foreach ($store as $index => &$st) {
            $product_filter = [
                'isdelete' => 0,

                'uid' => $this->_uid,
                'sort' => ['cart.time', 'asc'],
                'store' => $st['id'],
                'parameter' => [
                    'product.id',
                    'product.name',
                    'product.status',
                    'product.selled',
                    'cart.num',
                    'product.price * cart.bind as price',
                    'product.v1price * cart.bind as v1price',
                    'product.v2price * cart.bind as v2price',
                    'product.outside',
                    'product.origin',
                    'cart.content',
                    'cart.bind',
                    'product.stock',
                    'product.auto_stock',
                    'product.freetax',
                    'cart.bind',


                ],
            ];

            $amount = 0;
            $tax = 0;
            //筛选出仓库下的商品
            $product = $this->model('cart')->fetchAll($product_filter);
            if (!empty($product)) {
                foreach ($product as &$p) {
                    $p['origin'] = $this->model('country')->get($p['origin']);
                    $p['image'] = $productHelper->getListImage($p['id']);
                    $p['tax'] = $productHelper->getTaxFields($p['id']);

                    //判断bind表是否存在绑定的
                    $p['status'] = $this->cartstatus($p);


                    //规格价格替换
                    if (!empty($p['content'])) {
                        $collection_price = $this->model('collection')->get($p['id'], $p['content']);
                        if (!empty($collection_price)) {
                            if ($collection_price['available'] == 1) {
                                $p['price'] = $collection_price['price'] * $p['bind'];
                                $p['v1price'] = $collection_price['v1price'] * $p['bind'];
                                $p['v2price'] = $collection_price['v2price'] * $p['bind'];
                                $p['image'] = $this->model('upload')->get($collection_price['logo'], 'path');
                                $p['stock'] = $collection_price['stock'];
                            } else {
                                return new json(json::PARAMETER_ERROR, '系统错误，请清空购物车后重新下单');
                            }
                        } else {
                            return new json(json::PARAMETER_ERROR, '系统错误，请清空购物车后重新下单');
                        }
                    }

                    //捆绑销售的单价
                    $priceInBind = $productHelper->getPriceByBind($p);
                    if ($priceInBind) {
                        $p['price'] = $priceInBind['price'] * $priceInBind['num'];
                        $p['v1price'] = $priceInBind['v1price'] * $priceInBind['num'];
                        $p['v2price'] = $priceInBind['v2price'] * $priceInBind['num'];
                    }
                    if ($p['content'] != '' || $p['bind'] > 1) {

                        $unit = $this->model("bind")->where("content=? and num=? and pid=?", [$p['content'], $p['bind'], $p['id']])->find(['unit']);

                        $unit = $unit['unit'];


                    }
                    //将商品名称增加 规格 还有捆绑数量
                    if ($p['content'] != '' && $p['bind'] >= 1) {
                        $p['name'] .= "(" . $p['content'] . "," . $p['bind'] . $unit . ")";
                    } elseif ($p['content'] != '') {
                        $p['name'] .= "(" . $p['content'] . ")";
                    } elseif ($p['bind'] > 1) {

                        $p['name'] .= "(" . $p['bind'] . $unit . ")";

                    }


                    //计算总价
                    switch ($user['vip']) {
                        case 0:
                            $amount += $p['price'] * $p['num'];
                            $temp_tax = $p['tax'] * $p['price'] * $p['num'];
                            if (in_array($p['outside'], [2, 3]) && $p['freetax'] == 0) {
                                $tax += $temp_tax;
                            }
                            break;
                        case 1:
                            $amount += $p['v1price'];
                            $temp_tax = $p['tax'] * $p['v1price'] * $p['num'];
                            if (in_array($p['outside'], [2, 3]) && $p['freetax'] == 0) {
                                $tax += $temp_tax;
                            }
                            break;
                        case 2:
                            $amount += $p['v2price'];
                            $temp_tax = $p['tax'] * $p['v2price'] * $p['num'];
                            if (in_array($p['outside'], [2, 3]) && $p['freetax'] == 0) {
                                $tax += $temp_tax;
                            }
                            break;
                        default:
                            return new json(json::PARAMETER_ERROR, '系统错误，请重新登陆');
                    }
                }


                $st['product'] = $product;//商品内容
                $st['amount'] = $amount;//商品总价
                $st['tax'] = $tax;//税款
                $st['discount'] = 0;//活动优惠? 这个价格怎么出来的?
            }
        }

        return new json(json::OK, NULL, $store);
    }

    function cartstatus($product)
    {
        if ($product['status'] == 0) {
            return $product['status'];
        }
        //
        if ($product['content'] != '') {
            if ($product['bind'] > 1) {
                $bind = $this->model("bind")->where('content=? and num=? and pid=?', [$product['content'], $product['bind'], $product['id']])->find();
                if (!$bind) {
                    return 0;
                } else {
                    return 1;
                }
            } elseif ($product['bind'] == 1) {
                $bind = $this->model("bind")->where('content=? and num=? and pid=?', [$product['content'], $product['bind'], $product['id']])->find();
                if (!$bind) {
                    //找collent表
                    $collection = $this->model("collection")->where("content=? and pid=?", [$product['content'], $product['id']])->find();
                    if (!$collection) {
                        return 0;
                    } else {
                        return 1;
                    }
                } else {
                    return 1;
                }
            }
        } else {
            //判断bind参数是否跟商品的售卖数相同
            if ($product['bind'] == $product['selled']) {
                return 1;
            } else {
                $bind = $this->model("bind")->where("pid=? and num=? and content=''", [$product['id'], $product['bind']])->find();
                if ($bind) {
                    return 1;
                } else {
                    return 0;
                }
            }
            //不同 走bind表 核对

        }
    }

    /**
     * 将商品删除或者添加到购物车
     */
    function add()
    {
        if (!empty($this->_response))
            return $this->_response;

        $id = $this->data('id');
        $content = $this->data('content', '');
        $num = $this->data('num', 1, 'intval');
        if (empty($this->_uid))
            return new json(json::NOT_LOGIN);

        $productHelper = new helper\product();

        $bind = $this->data('bind', $productHelper->getSelled(['id' => $id, 'content' => $content, 'num' => $num]), 'intval');

        if ($productHelper->canBuy($id, $content)) {
            $this->model('product')->transaction();
            $cartHelper = new \application\helper\cart();
            if ($cartHelper->add($this->_uid, $id, $content, $num, $bind)) {
                $this->model('product')->commit();
                $num = $this->model('cart')->where('uid=?', [$this->_uid])->find('sum(num) as num');
                return new json(json::OK, NULL, $num['num']);
            } else {
                $this->model('product')->rollback();
                return new json(json::PARAMETER_ERROR, '添加到购物车失败');
            }
        }
        return new json(json::PARAMETER_ERROR, '该商品无法购买');
    }

    /**
     * 购物车清空
     * @return \application\message\json
     */
    function clear()
    {
        return new json(json::PARAMETER_ERROR, '该接口已经废除');

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();

        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $this->model('cart')->where('uid=?', [$uid])->delete();
        return new json(json::OK);
    }

    /*
     * 清空失效的商品
     * copy
     *
     */
    function cleardown()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();


        if (empty($uid))
            return new json(json::NOT_LOGIN);
        $cart = $this->model('cart')
            ->table('product', 'left join', 'product.id=cart.pid')
            ->where('cart.uid=? and product.status=0', [$uid])->select("cart.pid");
        $arr = array();
        foreach ($cart as $c) {
            $this->model('cart')
                //->table('product', 'left join', 'product.id=cart.pid')
                ->where('cart.uid=? and cart.pid =(?)', [$uid, $c['pid']])->delete();
        }


        //删除不存在bind的商品
        $cart = $this->model("cart")->where("uid=?", [$uid])->select();

        if ($cart) {
            foreach ($cart as $c) {
                //属性不为空 判断bind表是否存在

                $bind = $this->model("bind")->where("pid=? and num=? and content=?", [$c['pid'], $c['bind'], $c['content']])->find();

                if (!$bind) {

                    if ($c['content'] != '') {
                        $bind1 = $this->model("product")
                            ->table("collection", "left join", "collection.pid=product.id")
                            ->where("collection.content=? and selled=?", [$c['content'], $c['bind']])
                            ->find();
                        if (!$bind1) {


                            $this->model('cart')
                                //->table('product', 'left join', 'product.id=cart.pid')
                                ->where('cart.uid=? and cart.pid =(?) and content=? and num=? and bind=?', [$uid, $c['pid'], $c['content'], $c['num'], $c['bind']])->delete();
                        }

                    } else {
                        $bind1 = $this->model("product")->where("id=?", [$c['pid']])->find();
                        if ($c['bind'] != $bind1['selled']) {


                            $this->model('cart')
                                //->table('product', 'left join', 'product.id=cart.pid')
                                ->where('cart.uid=? and cart.pid =(?) and content=? and num=? and bind=?', [$uid, $c['pid'], $c['content'], $c['num'], $c['bind']])->delete();
                        }
                    }
                    continue;
                } else {

                    if($c['content']!='') {
                        $bind = $this->model("collection")
                            ->table("product", 'left join', 'product.id=collection.pid')
                            ->where("collection.pid=? and collection.content=? and product.selled=?", [$c['pid'], $c['content'], $c['bind']])->find();

                        if (!$bind) {
                            $this->model('cart')
                                //->table('product', 'left join', 'product.id=cart.pid')
                                ->where('cart.uid=? and cart.pid =(?) and content=? and num=? and bind=?', [$uid, $c['pid'], $c['content'], $c['num'], $c['bind']])->delete();
                            continue;
                        }
                    }
                    continue;

                }


            }
        }


        return new json(json::OK);
    }

    /*
     *
     * 获取购物车的数量
     * copy
     */
    function cartnum()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();

        if (empty($uid))
            return new json(json::NOT_LOGIN);
        $cart['count'] = $this->model('cart')
            ->where('cart.uid=?', [$uid])
            ->find(['sum(num) as sum']);
        $cart['count'] = $cart['count'] ['sum'];
        return new json(json::OK, NULL, $cart);
    }
}