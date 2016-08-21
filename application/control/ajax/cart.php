<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;
use application\helper as helper;

class cart extends ajax
{
    /**
     * 将商品删除或者添加到购物车
     */
    function add()
    {
        $id = $this->post('id');
        $content = $this->post('content', '');
        $num = $this->post('num', 1, 'intval');

        $userHelper = new helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $productHelper = new helper\product();
        
        $bind = $this->data('bind',$productHelper->getSelled(['id'=>$id,'content'=>$content,'num'=>$num]),'intval');

        if ($productHelper->canBuy($id, $content)) {
        	$this->model('product')->transaction();
            $cartHelper = new helper\cart();
            if ($cartHelper->add($this->_uid, $id, $content, $num, $bind)) {
                $this->model('product')->commit();
                $num = $this->model('cart')->where('uid=?',[$uid])->find('ifnull(sum(num),0) as num');
                return new json(json::OK,NULL,$num['num']);
            } else {
                $this->model('product')->rollback();
                return new json(json::PARAMETER_ERROR, '添加到购物车失败');
            }
        }
        return new json(json::PARAMETER_ERROR, '该商品无法购买');
    }

    function del()
    {
        $id = $this->post('id');

        $userHelper = new helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);
        //根据id 去删除购物车商品
        $this->model("cart")->where("pid=? and uid=?", [$id, $uid])->delete();
        return new json(json::OK, '删除商品成功');
    }

    function delall()
    {
        $id = $this->post('id');

        $userHelper = new helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);
        //根据id 去删除购物车商品
        $cart = $this->model("cart")
            ->table('product', 'left join', 'product.id=cart.pid')
            ->where("product.store=? and cart.uid=?", [$id, $uid])->select('cart.pid');
        foreach ($cart as $c) {

            $this->model("cart")->where("pid=? and uid=?", [$c['pid'], $uid])->delete();
        }
        return new json(json::OK, '删除商品成功');
    }

    function deldown()
    {
        $userHelper = new helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $cart = $this->model("cart")
            ->table('product', 'left join', 'product.id=cart.pid')
            ->where("product.status=0 and cart.uid=?", [$uid])->select('cart.pid');

        foreach ($cart as $c) {

            $this->model("cart")->where("pid=? and uid=?", [$c['pid'], $uid])->delete();
        }
        return new json(json::OK, '删除商品成功');
    }


}