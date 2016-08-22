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
                    'cart.num',
                    'product.price',
                    'product.v1price',
                    'product.v2price',
                    'product.outside',
                    'product.origin',
                    'cart.content',
                    'product.stock',
                    'product.auto_stock',
                    'product.freetax',
                //	'cart.bind',
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

                    //规格价格替换
                    if (!empty($p['content']))
                    {
                        $collection_price = $this->model('collection')->get($p['id'], $p['content']);
                        if (!empty($collection_price))
                        {
                            if ($collection_price['available'] == 1)
                            {
                                $p['price'] = $collection_price['price'];
                                $p['v1price'] = $collection_price['v1price'];
                                $p['v2price'] = $collection_price['v2price'];
                                $p['image'] = $this->model('upload')->get($collection_price['logo'], 'path');
                                $p['stock'] = $collection_price['stock'];
                            }
                            else
                            {
                                return new json(json::PARAMETER_ERROR, '系统错误，请清空购物车后重新下单');
                            }
                        }
                        else
                        {
                            return new json(json::PARAMETER_ERROR, '系统错误，请清空购物车后重新下单');
                        }
                    }
                    
                    //捆绑销售的单价
                    /* $priceInBind = $productHelper->getPriceByBind($p);
                    if ($priceInBind)
                    {
                    	$p['price'] = $priceInBind['price'];
                    	$p['v1price'] = $priceInBind['v1price'];
                    	$p['v2price'] = $priceInBind['v2price'];
                    } */

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
                        default:return new json(json::PARAMETER_ERROR, '系统错误，请重新登陆');
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

        $bind = $this->data('bind',$productHelper->getSelled(['id'=>$id,'content'=>$content,'num'=>$num]),'intval');

        if ($productHelper->canBuy($id, $content)) {
            $this->model('product')->transaction();
            $cartHelper = new helper\cart();
            if ($cartHelper->add($this->_uid, $id, $content, $num, $bind)) {
                $this->model('product')->commit();
                $num = $this->model('cart')->where('uid=?',[$this->_uid])->find('sum(num) as num');
                return new json(json::OK,NULL,$num['num']);
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
        $uid = 770;
        if (empty($uid))
            return new json(json::NOT_LOGIN);
        $cart['count'] = $this->model('cart')
            ->where('cart.uid=?', [$uid])
            ->find(['sum(num) as sum']);
        $cart['count'] = $cart['count'] ['sum'];
        return new json(json::OK, NULL, $cart);
    }
}