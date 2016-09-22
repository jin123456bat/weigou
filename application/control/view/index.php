<?php
namespace application\control\view;

use system\core\view;
use application\helper\erpSender;
use application\helper\erp\oms;
use system\core\image;
use application\helper\jpush;
use application\helper\sms;

class index extends view
{
    function __construct()
    {
        parent::__construct();
    }

    function index()
    {
        $this->response->setCode(302);
        if ($this->http->isMobile()) {
            $this->response->addHeader('Location', $this->http->url('', 'mobile', 'index'));
        } else {
            $this->response->addHeader('Location', 'http://willg.cn');
        }
    }

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

    function test()
    {
        $jpush = new jpush('240594b6ccdf89fe91209e6b', '802960b6f8210bd4c77afed8');
        $jpush->push("1", "2");
    }

    function import()
    {
        $file = $this->fil->receive($_FILES['file']);
        if (is_file($file)) {

        }
    }

    /* function upgrade()
    {
        if (file_exists('./upgrade') && file_get_contents('./upgrade')=='1')
        {
            exit('已经升级过了');
        }
        file_put_contents('./upgrade', '1');
        $this->model('product')->transaction();
        $products = $this->model('product')->where('selled>?',[1])->select();
        foreach ($products as $product)
        {
            if ($product['selled']!=1 && $product['oldprice']!=0 || $product['inprice']!=0 || $product['price']!=0 || $product['v1price']!=0 || $product['v2price']!=0)
            {
                if(!$this->model('product')->where('id=?',[$product['id']])->limit(1)->update([
                    'oldprice' => $product['oldprice']/$product['selled'],//更改oldprice
                    'price' => $product['price']/$product['selled'],//更改v0价格
                    'v1price' => $product['v1price']/$product['selled'],//v1价格
                    'v2price' => $product['v2price']/$product['selled'],//v2价格
                    'inprice' => $product['inprice']/$product['selled'],//进价
                ]))
                {
                    $this->model('product')->rollback();
                    unlink('./upgrade');
                    var_dump($product);
                    exit('错误1');
                }
            }

            //更改colleciton
            $collections = $this->model('collection')->where('pid=?',[$product['id']])->select();
            foreach ($collections as $collection)
            {
                if ($product['selled']!=1 && $collection['price']!=0 || $collection['v1price']!=0 || $collection['v2price']!=0)
                {
                    if(!$this->model('collection')->where('pid=? and content=?',[$collection['pid'],$collection['content']])->limit(1)->update([
                        'price' => $collection['price']/$product['selled'],//更改v0价格
                        'v1price' => $collection['v1price']/$product['selled'],//v1价格
                        'v2price' => $collection['v2price']/$product['selled'],//v2价格
                    ]))
                    {
                        $this->model('product')->rollback();
                        unlink('./upgrade');
                        exit('错误2');
                    }
                }
            }
        }
        $this->model('product')->commit();
        unlink('./upgrade');
        echo "升级完成";
    } */
    /*
        function cartdown(){
            $product=$this->model("order_product")->where("bind>1")->select();
            $this->model('product')->transaction();
            foreach($product as $p){
                $price=$p['price']/$p['bind'];
                if(!$this->model("order_product")->where("id=?",[$p['id']])->update(["price"=>$price])){
                    $this->model('product')->rollback();

                    exit('错误2');
                }
            }
            $this->model('product')->commit();
            exit('ok');

        }
    */
    /*
    function sendupdate()
    {


        $uid = $this->model('system')->get('uid', 'sms');
        $key = $this->model('system')->get('key', 'sms');
        $sign = $this->model('system')->get('sign', 'sms');
        $template = '版本更新，退订回复TD';

        $sms = new sms($uid, $key, $sign);
        $ucount = $this->model("user")->where("send=0")->find(['count(1)']);
        $ucount=$ucount['count(1)'];

        $j= ceil($ucount/100);
        for($i=0;$i<$j;$i++) {
            $user = $this->model("user")->where("send=0")->limit($i*100, 100)->select();
            $uw = '';
            foreach ($user as $u) {
                $uw[] = $u['telephone'];
            }
            $uw = implode(',', $uw);
            echo $uw . "<br />";


            //循环发送

            $num = $sms->send($uw, $template);
            if ($num > 0) {
                foreach ($user as $u) {
                    $this->model("user")->where("telephone=?", [$u['telephone']])->update(["send" => '1']);
                    echo $u['telephone'] . "发送成功<br />";
                }
                continue;

            } else {
                switch ($num) {
                    case '-1':
                        return new json(json::PARAMETER_ERROR, '没有该用户账户');
                    case '-2':
                        return new json(json::PARAMETER_ERROR, '接口密钥不正确');
                    case '-21':
                        return new json(json::PARAMETER_ERROR, 'MD5接口密钥加密不正确');
                    case '-11':
                        return new json(json::PARAMETER_ERROR, '该用户被禁用');
                    case '-14':
                        return new json(json::PARAMETER_ERROR, '短信内容出现非法字符');
                    case '-41':
                        return new json(json::PARAMETER_ERROR, '手机号码为空');
                    case '-42':
                        return new json(json::PARAMETER_ERROR, '短信内容为空');
                    case '-51':
                        return new json(json::PARAMETER_ERROR, '短信签名格式不正确');
                    case '-6':
                        return new json(json::PARAMETER_ERROR, 'IP限制');
                }
            }

        }
    }
    */

    function orderoff(){
        echo 12;
        $orders=$this->model("order_package")->where('orderno in (select `order`.orderno from `order` where  pay_status=0 and status=1 and unix_timestamp(now())-createtime>=3600 and (select task_user.orderno from task_user where task_user.orderno=order.orderno) is null)')->select(['id','orderno']);
        die(json_encode($orders));
    }
}
