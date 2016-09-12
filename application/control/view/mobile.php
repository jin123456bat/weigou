<?php
namespace application\control\view;

use system\core\view;
use application\helper\user;
use application\helper\product;
use system\core\image;
use application\helper\wechat;

/**
 * @author jin12
 *
 */
class mobile extends view
{
    function __construct()
    {
        parent::__construct();


        if ($_SERVER['HTTP_HOST'] == 'twillg.com') {
            header('Location: ' . 'http://www.twillg.com/index.php?' . http_build_query($_GET), true, 302);
            exit();
        }

        if (!empty($this->get('share_uid'))) {
            $this->session->share_uid = $this->get('share_uid');
        }
        if (!empty($this->get('user_source'))) {
            $this->session->user_source = $this->get('user_source');
        }


        $userHelper = new user();
        $uid = $userHelper->isLogin();
        if (!empty($uid)) {
            $user = $this->model('user')->where('id=?', [$uid])->find();
            if (!$userHelper->saveUserSession($user)) {
                $this->setViewname('login');
            }
            $userHelper->protectedUser($user);
            $this->assign('user', $user);
        } else {
            $temp_openid = $this->session->wx_openid;
            if (isWechat() && empty($temp_openid)) {
                $appid = $this->model('system')->get('appid', 'wechat');
                $appsecret = $this->model('system')->get('appsecret', 'wechat');
                $this->_wechat = new wechat($appid, $appsecret);
                if ($this->get->code === NULL) {
                    $location = $this->_wechat->getCode($this->http->url(), 'snsapi_userinfo');
                    header('Location: ' . $location, true, 302);
                    exit();
                } else {
                    $code = $this->get->code;
                    $codeinfo = $this->_wechat->getOpenid($code);
                    if (isset($codeinfo['openid']) && isset($codeinfo['access_token'])) {
                        $this->_userinfo = $this->_wechat->getUserInfo($codeinfo['access_token'], $codeinfo['openid']);
                        if (isset($this->_userinfo->openid) && !empty($this->_userinfo->openid)) {
                            $user = $this->model('user')->where('wx_openid_web=?', [$this->_userinfo->openid])->find();
                            if (!empty($user)) {
                                if (!$userHelper->saveUserSession($user)) {
                                    $this->setViewname('login');
                                }
                                $userHelper->protectedUser($user);
                                $this->assign('user', $user);
                            } else {
                                $this->session->wx_openid = $this->_userinfo->openid;
                            }
                        }
                    }
                }
            }
        }

        if (isWechat()) {
            if (!isset($this->_wechat) || empty($this->_wechat)) {
                $appid = $this->model('system')->get('appid', 'wechat');
                $appsecret = $this->model('system')->get('appsecret', 'wechat');
                $this->_wechat = new wechat($appid, $appsecret);
            }

            $jsApiTicket = $this->_wechat->getJsApiTicket();
            $jsApiTicket = $this->_wechat->getSignPackage($jsApiTicket);
            $this->assign('jsApiTicket', $jsApiTicket);
        }
        //判断是否有渠道

        if (!empty($user['source'])) {
            //判断是否是学校的
            $school = $this->model("source")->where("id=?", [$user['source']])->find(['school']);

            if ($school['school'] == 1) {
                $this->assign('school', 1);
            } else {
                $this->assign('school', 0);
            }

        } else {
            $this->assign('school', 0);
        }
        $this->assign('isWechat', isWechat());
    }

    function question()
    {
        $category = $this->model('question_category')
            ->orderby('sort', 'asc')
            ->orderby('id', 'desc')
            ->where('isdelete=?', [0])
            ->select();
        foreach ($category as &$c) {
            $question = $this->model('question')
                ->where('isdelete=? and cid=?', [0, $c['id']])
                ->orderby('sort', 'asc')
                ->orderby('id', 'desc')
                ->select();
            $c['question'] = $question;
        }
        $this->assign('category', $category);
        return $this;
    }

    function answer()
    {
        $id = $this->get('id');
        $question = $this->model('question')->where('id=?', [$id])->find();
        if (!empty($question)) {
            $this->assign('question', $question);
            return $this;
        }
    }

    function invitUrl()
    {
        $id = $this->get('id');
        if (!empty($id)) {
            $user = $this->model('user')->where('id=?', [$id])->find();
            if (!empty($user)) {
                $this->assign('current_user', $user);
                return $this;
            }
        }
        return $this->__404();
    }

    function wuliu()
    {
        $id = $this->get('orderno');
        $package = $this->model('order_package')->where('orderno=?', [$id])->find();


        //获取订单信息
        $order = $this->model('order')->where('orderno=?', [$id])->find();
        $list = array();
        $listt['remark'] = "订单提交成功";
        $listt['zone'] = "";
        $listt['datetime'] = date('Y-m-d H:i:s', $order['createtime']);
        $this->assign('ti', $listt);
        if ($order['pay_status'] == 1) {
            $listt['remark'] = "订单支付成功";
            $listt['zone'] = "";
            $listt['datetime'] = date('Y-m-d H:i:s', $order['pay_time']);
            $this->assign('fu', $listt);
        }
        if ($order['way_status'] == 1) {
            $listt['remark'] = "您的订单已导入，等待快递公司取件";
            $listt['zone'] = "";
            $listt['datetime'] = date('Y-m-d H:i:s', $order['way_time']);
            $this->assign('qu', $listt);
        }
        if ($order['receive'] == 1) {
            $listt['remark'] = "订单交易成功";
            $listt['zone'] = "";
            $listt['datetime'] = date('Y-m-d H:i:s', $order['receive_time']);
            $this->assign('su', $listt);
        }


        if (!empty($package)) {

            $response = \application\helper\express::queryJuhe($package['ship_type'], $package['ship_number']);
            $response = json_decode($response, true);
            /*

            */
            $package['ship_type'] = $this->model('ship')->get($package['ship_type']);


            if ($response['resultcode'] == 200) {

                $this->assign('wuliu', $response['result']);
            }


            // die(json_encode($response['result']));
            $this->assign('package', $package);
            return $this;
        }
        return $this->__404();
    }

    /**
     * 已经发货的订单详情
     */
    function orderinfo_way()
    {
        $orderno = $this->get('orderno');
        if (!empty($orderno)) {
            $order = $this->model('order')->where('orderno=?', [$orderno])->find();
            if (!empty($order)) {
                $order['is_task'] = !empty($this->model('task_user')->where('orderno=?', [$orderno])->find());

                $this->assign('order', $order);

                $address = $this->model('address')
                    ->table('province', 'left join', 'province.id=address.province')
                    ->table('city', 'left join', 'city.id=address.city')
                    ->table('county', 'left join', 'county.id=address.county')
                    ->where('address.id=?', [$order['address']])
                    ->find([
                        'address.id,
					address.name,
					address.telephone,
					address.zcode,
					address.identify,
					address.host,
					address.address,
					province.id as province_id,
					city.id as city_id,
					county.id as county_id,
					province.name as province,
					city.name as city,
					county.name as county'
                    ]);
                $this->assign('address', $address);

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
                    $this->assign('package', $package);

                    return $this;
                } else {
                    $this->setViewname('orderinfo');
                    return $this->orderinfo();
                }
            }
        }
    }

    function orderinfo()
    {
        $orderno = $this->get('orderno');
        if (!empty($orderno)) {
            $order = $this->model('order')->where('orderno=?', [$orderno])->find();
            if (!empty($order)) {

                $order['is_task'] = !empty($this->model('task_user')->where('orderno=?', [$orderno])->find());

                $this->assign('order', $order);

                $address = $this->model('address')
                    ->table('province', 'left join', 'province.id=address.province')
                    ->table('city', 'left join', 'city.id=address.city')
                    ->table('county', 'left join', 'county.id=address.county')
                    ->where('address.id=?', [$order['address']])
                    ->find([
                        'address.id,
						address.name,
						address.telephone,
						address.zcode,
						address.identify,
						address.host,
						address.address,
						province.id as province_id,
						city.id as city_id,
						county.id as county_id,
						province.name as province,
						city.name as city,
						county.name as county'
                    ]);
                $this->assign('address', $address);

                $store = $this->model('order_package')
                    ->table('store', 'left join', 'store.id=order_package.store_id')->where('order_package.orderno=?', [$orderno])->select([
                        'store.name',
                        'order_package.id as package_id'
                    ]);

                $productHelper = new \application\helper\product();
                foreach ($store as &$st) {
                    $product = $this->model('order_product')
                        ->table('product', 'left join', 'product.id=order_product.pid')
                        ->where('package_id=?', [$st['package_id']])
                        ->select([
                            'product.name',
                            'product.id',
                            'order_product.num',
                            'order_product.price',
                            'order_product.content',
                            'order_product.refund',
                        ]);
                    foreach ($product as &$p) {
                        $p['image'] = $productHelper->getListImage($p['id']);
                    }
                    $st['product'] = $product;
                }

                $this->assign('store', $store);

                /* $log = $this->model('order_log')->where('orderno=?',[$orderno])->select();
                $this->assign('log', $log); */
                //获取物流信息
                $courier = $this->model("order_package")->where("orderno=?", [$orderno])->find();
             
                if ($courier['ship_number']) {


                    //调取物流信息
                    $response = \application\helper\express::queryJuhe($courier['ship_type'], $courier['ship_number']);
                    $response = json_decode($response, true);

                    if ($response['resultcode'] == 200) {
                        $courier['info'] = $response['result']['list'][0]['remark'];

                    }

                }

                $this->assign('courier', $courier);
                return $this;

            }
        }
        return $this->__404();
    }

    function searchResult()
    {
        $keywords = $this->get('keywords');
        $keywords = substr($keywords, 0, 32);

        $product_filter = [
            'isdelete' => 0,
            'status' => 1,
            'sort' => ['product.sort', 'asc'],
            'parameter' => [
                'product.id',
                'product.name',
                'product.oldprice',
                'product.price',
                'product.v1price',
                'product.v2price',
                'product.short_description',
                'store.name as store',
                'product.origin',
            ]
        ];
        if (!empty($keywords)) {
            $product_filter['name'] = '%' . $keywords . '%';

            $userHelper = new user();
            $this->model('search_log')->insert([
                'ip' => ip(),
                'keywords' => $keywords,
                'time' => $_SERVER['REQUEST_TIME'],
                'uid' => $userHelper->isLogin(),
            ]);

        }
        $product = $this->model('product')->fetchAll($product_filter);
        $productHelper = new \application\helper\product();
        foreach ($product as &$p) {
            $p['origin'] = $this->model('country')->get($p['origin']);
            $p['image'] = $productHelper->getListImage($p['id']);

            //商品价格
            $filter = [
                'pid' => $p['id'],
                'isdelete' => 0,
                'available' => 1,
                'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)',
            ];
            $price_collection = $this->model('collection')->fetch($filter);
            if (!empty($price_collection)) {
                if ($price_collection[0]['sum(stock)'] !== NULL) {
                    $p['stock'] = $price_collection[0]['sum(stock)'];
                }
                if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)'] !== NULL) {
                    $p['price'] = $price_collection[0]['min(price)'] . '起';//'~'.$price_collection[0]['max(price)'];
                }
                if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL) {
                    $p['v1price'] = $price_collection[0]['min(v1price)'] . '起';//'~'.$price_collection[0]['max(v1price)'];
                }
                if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL) {
                    $p['v2price'] = $price_collection[0]['min(v2price)'] . '起';//'~'.$price_collection[0]['max(v2price)'];
                }
            }
        }
        $this->assign('product', $product);

        return $this;
    }

    function create_bankcard()
    {
        $this->assign('province', $this->model('province')->select());
        return $this;
    }

    function drawal()
    {
        $id = $this->get('default');
        if (!empty($id)) {
            $bankcard = $this->model('bankcard')->where('id=? and isdelete=?', [$id, 0])->find();
            $this->assign('bankcard', $bankcard);
        } else {
            $userHelper = new \application\helper\user();
            $uid = $userHelper->isLogin();
            if (!empty($uid)) {
                $bankcard = $this->model('bankcard')->where('uid=? and isdelete=?', [$uid, 0])->find();
                $this->assign('bankcard', $bankcard);
            } else {
                $this->response->setCode(302);
                $this->response->addHeader('Location', $this->http->url('', 'mobile', 'login'));
            }
        }
        return $this;
    }

    function bankcard()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('', 'mobile', 'login'));
        } else {
            $filter = [
                'uid' => $uid,
                'isdelete' => 0,
                'parameter' => [
                    'bankcard.id',
                    'bankcard.type',
                    'bankcard.account',
                    'bankcard.name',
                    'bankcard.bank',
                    'bankcard.subbank',
                    'province.id as province_id',
                    'city.id as city_id',
                    'province.name as province',
                    'city.name as city'
                ],
            ];
            $bankcard = $this->model('bankcard')->fetchAll($filter);
            $this->assign('bankcard', $bankcard);
            return $this;
        }
    }

    function myinvit()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('', 'mobile', 'login'));
        } else {
            $temp = [];
            $share = $this->model('system')->where('type=?', ['share'])->select();
            foreach ($share as $s) {
                $temp[$s['name']] = $s['value'];
            }
            $this->assign('share', $temp);
            $this->assign('weibo_appkey', $this->model('system')->get('appkey', 'weibo'));
            return $this;
        }
    }

    function teaminfo()
    {
        $id = $this->get('id');
        if (!empty($id)) {
            $user = $this->model('user')->where('id=?', [$id])->find();
            if (!empty($user)) {
                $this->assign('current_user', $user);

                $time = strtotime(date('Y-m-d'));
                $dayteam = $this->model('user')->where('oid=? and invittime>?', [$id, $time])->find('count(*)');
                $dayteam = isset($dayteam['count(*)']) && !empty($dayteam['count(*)']) ? $dayteam['count(*)'] : 0;
                $this->assign('dayteam', $dayteam);

                $time = $_SERVER['REQUEST_TIME'] - 24 * 3600 * 7;
                $weekteam = $this->model('user')->where('oid=? and invittime>?', [$id, $time])->find('count(*)');
                $weekteam = isset($weekteam['count(*)']) && !empty($weekteam['count(*)']) ? $weekteam['count(*)'] : 0;
                $this->assign('weekteam', $weekteam);

                $time = $_SERVER['REQUEST_TIME'] - 24 * 3600 * 30;
                $monthteam = $this->model('user')->where('oid=? and invittime>?', [$id, $time])->find('count(*)');
                $monthteam = isset($monthteam['count(*)']) && !empty($monthteam['count(*)']) ? $monthteam['count(*)'] : 0;
                $this->assign('monthteam', $monthteam);

                $team = $this->model('user')->where('oid=?', [$id])->find('count(*)');
                $team = isset($team['count(*)']) && !empty($team['count(*)']) ? $team['count(*)'] : 0;
                $this->assign('team', $team);

                $profit = $this->model('swift')->where('source in (?)', [2, 3, 4, 5, 6, 7])->where('uid=?', [$id])->find('sum(money)');
                $profit = isset($profit['sum(money)']) && !empty($profit['sum(money)']) ? $profit['sum(money)'] : 0;
                $this->assign('profit', $profit);

                $time = strtotime(date('Y-m-d'));
                $dayprofit = $this->model('swift')->where('source in (?)', [2, 3, 4, 5, 6, 7])->where('uid=? and time>?', [$id, $time])->find('sum(money)');
                $dayprofit = isset($dayprofit['sum(money)']) && !empty($dayprofit['sum(money)']) ? $dayprofit['sum(money)'] : 0;
                $this->assign('dayprofit', $dayprofit);

                $time = $_SERVER['REQUEST_TIME'] - 24 * 3600 * 7;
                $weekprofit = $this->model('swift')->where('source in (?)', [2, 3, 4, 5, 6, 7])->where('uid=? and time>?', [$id, $time])->find('sum(money)');
                $weekprofit = isset($weekprofit['sum(money)']) && !empty($weekprofit['sum(money)']) ? $weekprofit['sum(money)'] : 0;
                $this->assign('weekprofit', $weekprofit);

                $time = $_SERVER['REQUEST_TIME'] - 24 * 3600 * 30;
                $monthprofit = $this->model('swift')->where('source in (?)', [2, 3, 4, 5, 6, 7])->where('uid=? and time>?', [$id, $time])->find('sum(money)');
                $monthprofit = isset($monthprofit['sum(money)']) && !empty($monthprofit['sum(money)']) ? $monthprofit['sum(money)'] : 0;
                $this->assign('monthprofit', $monthprofit);

                return $this;
            }
        }
        return $this->__404();
    }

    function applyvip()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('', 'mobile', 'login'));
        } else {
            return $this;
        }
    }

    function theme()
    {
        $id = $this->get('id');
        if (!empty($id)) {
            $theme = $this->model('theme')->table('upload', 'left join', 'theme.logo=upload.id')->where('theme.id=?', [$id])->find('theme.id,upload.path as logo,theme.title');

            if (!empty($theme)) {
                $subtheme = $this->model('subtheme')->where('theme_id=?', [$id])->orderby('subtheme.sort', 'asc')->select();

                $productHelper = new product();
                foreach ($subtheme as &$st) {
                    $product = $this->model('subtheme_product')
                        ->table('product', 'left join', 'subtheme_product.product_id=product.id')
                        ->where('subtheme_product.subtheme_id=?', [$st['id']])
                        ->select([
                            'product.id',
                            'product.name',
                            'product.oldprice',
                            'product.price',
                            'product.v1price',
                            'product.v2price',
                            'product.origin',
                        ]);

                    foreach ($product as &$p) {
                        $p['image'] = $productHelper->getListImage($p['id']);
                        $p['origin'] = $this->model('country')->get($p['origin']);

                        //商品属性
                        $filter = [
                            'pid' => $id,
                            'isdelete' => 0,
                            'parameter' => 'name,type,value',
                        ];
                        $p['prototype'] = $this->model('prototype')->fetch($filter);

                        //商品的可选属性集合
                        $filter = [
                            'pid' => $id,
                            'isdelete' => 0,
                            'parameter' => [
                                'content',
                                'price',
                                'v1price',
                                'v2price',
                                'sku',
                                'stock',
                                'upload.path as logo',
                                'available',
                            ]
                        ];
                        $p['collection'] = $this->model('collection')->fetchAll($filter);

                        //商品的价格等替换
                        $filter = [
                            'pid' => $p['id'],
                            'isdelete' => 0,
                            'available' => 1,
                            'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)',
                        ];
                        $price_collection = $this->model('collection')->fetch($filter);
                        if (!empty($price_collection)) {
                            if ($price_collection[0]['sum(stock)'] !== NULL) {
                                $p['stock'] = $price_collection[0]['sum(stock)'];
                            }
                            if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)'] !== NULL) {
                                $p['price'] = $price_collection[0]['min(price)'] . '~' . $price_collection[0]['max(price)'];
                            }
                            if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL) {
                                $p['v1price'] = $price_collection[0]['min(v1price)'] . '~' . $price_collection[0]['max(v1price)'];
                            }
                            if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL) {
                                $p['v2price'] = $price_collection[0]['min(v2price)'] . '~' . $price_collection[0]['max(v2price)'];
                            }
                        }
                    }

                    $st['product'] = $product;
                }

                $theme['subtheme'] = $subtheme;
                $this->assign('theme', $theme);

                return $this;
            }
        }
        return $this->__404();
    }

    function taskorderdetail()
    {
        $orderno = $this->get('orderno');
        $o_orderno = $this->get('o_orderno');

        $task_user = $this->model('task_user')->where('orderno=?', [$orderno])->find();
        if (!empty($task_user)) {
            if (!empty($task_user['o_orderno']) && $task_user['o_orderno'] != $orderno) {
                $task_user = $this->model('task_user')->where('orderno=?', [$task_user['o_orderno']])->find();
            }

            $this->assign('task_user', $task_user);

            $order = $this->model('order')
                ->table('task_user', 'left join', 'task_user.orderno=order.orderno')
                ->where('task_user.orderno=?', [$orderno])->find();

            $userHelper = new \application\helper\user();
            $uid = $userHelper->isLogin();
            if (!empty($uid)) {
                if (empty($o_orderno)) {
                    $order = $this->model('task_user')
                        ->table('`order`', 'left join', 'order.orderno=task_user.orderno')
                        ->where('task_user.tid=? and order.uid=? and task_user.o_orderno is null', [$order['tid'], $uid])
                        ->find();
                } else {
                    $order = $this->model('task_user')
                        ->table('`order`', 'left join', 'order.orderno=task_user.orderno')
                        ->where('task_user.tid=? and order.uid=? and task_user.o_orderno=?', [$order['tid'], $uid, $o_orderno])
                        ->find();
                }
                $this->assign('order', $order);
            }

            $task = $this->model('task')->where('id=?', [$task_user['tid']])->find();
            $this->assign('task', $task);

            $product = $this->model('product')
                ->table('store', 'left join', 'store.id=product.store')
                ->where('product.id=?', [$task['pid']])
                ->find([
                    'product.*',
                    'store.name as store',
                ]);
            $productHelper = new \application\helper\product();
            $product['origin'] = $this->model('country')->get($product['origin']);
            $product['image'] = $productHelper->getDetailImage($product['id']);
            $product['listImage'] = $productHelper->getListImage($product['id'], true);
            $product['tax'] = $productHelper->getTaxFields($product['id']);

            $this->assign('product', $product);

            $complete_user = [];
            if (empty($task_user['o_orderno'])) {
                $main_order = $this->model('order')->where('orderno=?', [$task_user['orderno']])->find();
                if ($main_order['pay_status'] == 1 && $main_order['status'] == 1) {
                    $complete_user_gravatar = $this->model('user')
                        ->table('upload', 'left join', 'upload.id=user.gravatar')
                        ->where('user.id=?', [$main_order['uid']])
                        ->find('upload.path as gravatar');
                    $complete_user[] = $complete_user_gravatar['gravatar'];
                }
                $complete_user_gravatar = $this->model('task_user')
                    ->table('`order`', 'left join', 'order.orderno=task_user.orderno')
                    ->table('user', 'left join', 'user.id=order.uid')
                    ->table('upload', 'left join', 'user.gravatar=upload.id')
                    ->where('task_user.o_orderno=? and order.pay_status=? and order.status=?', [$main_order['orderno'], 1, 1])
                    ->select([
                        'upload.path as gravatar'
                    ]);
                foreach ($complete_user_gravatar as $gravatar) {
                    $complete_user[] = $gravatar['gravatar'];
                }
            } else {
                $main_order = $this->model('order')
                    ->table('user', 'left join', 'user.id=order.uid')
                    ->table('upload', 'left join', 'upload.id=user.gravatar')
                    ->where('order.orderno=?', [$task_user['o_orderno']])
                    ->find(['upload.path as gravatar', 'order.pay_status']);

                if ($main_order['pay_status'] == 1) {
                    $complete_user[] = $main_order['gravatar'];
                }

                $complete_user_gravatar = $this->model('task_user')
                    ->table('`order`', 'left join', 'order.orderno=task_user.orderno')
                    ->table('user', 'left join', 'user.id=order.uid')
                    ->table('upload', 'left join', 'upload.id=user.gravatar')
                    ->where('task_user.o_orderno=? and order.pay_status=?', [$task_user['o_orderno'], 1])
                    ->select('upload.path as gravatar');
                foreach ($complete_user_gravatar as $gravatar) {
                    $complete_user[] = $gravatar['gravatar'];
                }
            }
            $this->assign('complete_user', $complete_user);

            if (!empty($uid)) {
                $filter = [
                    'sort' => ['host', 'desc'],
                    'uid' => $uid,
                    'isdelete' => 0,
                    'parameter' => 'address.id,
									address.name,
									address.telephone,
									address.zcode,
									address.identify,
									address.host,
									address.address,
									province.id as province_id,
									city.id as city_id,
									county.id as county_id,
									province.name as province,
									city.name as city,
									county.name as county'
                ];
                $address = $this->model('address')->fetchAll($filter);
                $this->assign('address', $address);
            }

            $this->assign('province', $this->model('province')->select());

            $temp = [];
            $share = $this->model('system')->where('type=?', ['share'])->select();
            foreach ($share as $s) {
                $temp[$s['name']] = $s['value'];
            }
            $this->assign('share', $temp);
            $this->assign('weibo_appkey', $this->model('system')->get('appkey', 'weibo'));

            return $this;

        }
        return $this->__404();
    }

    function taskdetail()
    {
        $id = $this->get('id');
        if (!empty($id)) {
            $userHelper = new \application\helper\user();
            $uid = $userHelper->isLogin();
            $task = $this->model('task')->where('id=? and isdelete=?', [$id, 0])->find();
            if (!empty($task)) {
                $this->assign('task', $task);

                $productHelper = new \application\helper\product();
                $product = $this->model('product')
                    ->table('store', 'left join', 'product.store=store.id')
                    ->where('product.id=?', [$task['pid']])
                    ->find([
                        'product.*',
                        'store.name as store',
                    ]);
                $product['image'] = $productHelper->getDetailImage($product['id']);
                $product['origin'] = $this->model('country')->get($product['origin']);
                $product['tax'] = $productHelper->getTaxFields($product['id']);

                $this->assign('product', $product);

                $filter = [
                    'sort' => ['host', 'desc'],
                    'uid' => $uid,
                    'isdelete' => 0,
                    'parameter' => 'address.id,
								address.name,
								address.telephone,
								address.zcode,
								address.identify,
								address.host,
								address.address,
								province.id as province_id,
								city.id as city_id,
								county.id as county_id,
								province.name as province,
								city.name as city,
								county.name as county'
                ];
                $address = $this->model('address')->fetchAll($filter);
                $this->assign('address', $address);

                $this->assign('province', $this->model('province')->select());

                return $this;
            }
        }
        return $this->__404();
    }

    function taskProgress()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('', 'mobile', 'login'));
        } else {
            return $this;
        }
    }

    function task()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('', 'mobile', 'login'));
        } else {
            $product = $this->model('task')
                ->table('product', 'left join', 'product.id=task.pid')
                ->table('store', 'left join', 'product.store=store.id')
                ->where('task.isdelete=?', [0])
                ->where('(auto_stock=? and stock>?) or auto_stock=?', [1, 0, 0])
                ->where('product.isdelete=?', [0])
                ->where('(product.auto_status=? and product.status=?) or (product.auto_status=? and product.avaliabletime_from < ? and product.avaliabletime_to > ?)', [0, 1, 1, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME']])
                ->select([
                    'task.id',
                    'product.name',
                    'task.price',
                    'task.teamnum',
                    'task.score',
                    'task.day',
                    'store.name as store',
                    'task.pid',
                    'product.outside',
                    'product.freetax',
                    'product.oldprice',
                    'product.origin'
                ]);

            $productHelper = new \application\helper\product();
            foreach ($product as &$p) {
                $p['image'] = $productHelper->getListImage($p['pid']);
                $p['origin'] = $this->model('country')->get($p['origin']);
                $p['tax'] = $productHelper->getTaxFields($p['pid']);
            }
            $this->assign('product', $product);

            $order = $this->model('task_user')
                ->table('task', 'left join', 'task.id=task_user.tid')
                ->table('`order`', 'left join', 'order.orderno=task_user.orderno')
                ->table('product', 'left join', 'product.id=task.pid')
                ->table('store', 'left join', 'product.store=store.id')
                ->where('order.uid=? and order.status=?', [$uid, 1])
                ->select([
                    'order.orderno',//订单号
                    'order.pay_status',//是否支付
                    'task_user.status',//团购是否成功 或者正在进行 或者取消
                    'task_user.o_orderno',
                    'order.status as order_status',

                    'task.id',
                    'product.name',
                    'task.price',
                    'task.teamnum',
                    'task.score',
                    'task.day',
                    'store.name as store',
                    'task.pid',
                    'product.oldprice',
                    'product.origin',
                    'product.outside',
                    'product.freetax',
                ]);

            $productHelper = new \application\helper\product();
            foreach ($order as &$product) {
                $product['image'] = $productHelper->getListImage($product['pid']);
                $product['origin'] = $this->model('country')->get($product['origin']);
                $product['tax'] = $productHelper->getTaxFields($product['pid']);

                if (empty($product['o_orderno'])) {
                    $complete_user_num = $this->model('task_user')
                        ->table('`order`', 'left join', 'order.orderno=task_user.orderno')
                        ->where('task_user.o_orderno=? and order.status=? and order.pay_status=?', [$product['orderno'], 1, 1])
                        ->find('count(*)');
                    $product['complete_order_num'] = $complete_user_num['count(*)'];
                    if ($product['pay_status'] == 1 && $product['order_status'] == 1) {
                        $product['complete_order_num']++;
                    }
                } else {
                    $complete_user_num = $this->model('task_user')
                        ->table('`order`', 'left join', 'order.orderno=task_user.orderno')
                        ->where('task_user.o_orderno=? and order.pay_status=? and order.status=?', [$product['o_orderno'], 1, 1])
                        ->find('count(*)');
                    $product['complete_order_num'] = $complete_user_num['count(*)'];
                    $main_order = $this->model('order')->where('orderno=?', [$product['o_orderno']])->find();
                    if ($main_order['pay_status'] == 1 && $main_order['status'] == 1) {
                        $product['complete_order_num']++;
                    }
                }
            }


            $this->assign('order', $order);

            return $this;
        }
    }

    function myeqcode()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'login'));
        } else {
            $content = htmlspecialchars_decode($this->http->url('', 'mobile', 'index', [
                'share_uid' => $uid,
            ]));
            $image = new image();
            $file = $image->QRCode($content);
            $file = str_replace(ROOT, '.', $file);
            if (is_file($file)) {
                $this->assign('eqcode', $file);
                return $this;
            }
        }
    }

    function teacher()
    {
        $id = $this->get('id');
        if (!empty($id)) {
            $teacher = $this->model('user')->table('upload', 'left join', 'upload.id=user.gravatar')->where('user.id=?', [$id])->find([
                'user.id',
                'user.name',
                'user.description',
                'upload.path as gravatar',
            ]);
            if (!empty($teacher)) {

                $browse = $this->model('college_user')
                    ->table('college', 'left join', 'college.id=college_user.college_id')
                    ->where('college.uid=?', [$id])
                    ->find('count(*)');

                $teacher['browse'] = isset($browse['count(*)']) ? $browse['count(*)'] : 0;

                $college = $this->model('college')
                    ->table('upload as upload1', 'left join', 'upload1.id=college.logo1')
                    ->table('upload as upload2', 'left join', 'upload2.id=college.logo2')
                    ->where('college.uid=?', [$id])
                    ->where('college.isdelete=?', [0])
                    ->orderby('college.sort', 'asc')
                    ->select([
                        'college.id',
                        'college.title',
                        'upload1.path as logo1',
                        'upload2.path as logo2',
                        'college.isgood',
                        'left(college.description,10) as description',
                        '(select count(*) from college_user where college_user.college_id=college.id) as browse'
                    ]);

                $teacher['college'] = $college;
                $this->assign('teacher', $teacher);
                return $this;
            }
        }
    }

    function advise()
    {
        $id = $this->get('id');
        if (!empty($id)) {
            $college = $this->model('college')->where('id=? and isdelete=?', [$id, 0])->find();
            if (!empty($college)) {
                $advise = $this->model('advise')->orderby('sort', 'asc')->where('isdelete=?', [0])->select();
                $this->assign('advise', $advise);
                return $this;
            }
        }
        return $this->__404();
    }

    function college_detail()
    {
        $id = $this->get('id');
        if (!empty($id)) {
            $collegeHelper = new \application\helper\college();
            $college = $this->model('college')->where('id=? and isdelete=?', [$id, 0])->find();
            if (!empty($college)) {
                //更新浏览量
                $collegeHelper->createLog($id);

                $college['browse'] = $collegeHelper->getBrowse($id);
                $this->assign('college', $college);

                $teacher = $this->model('user')->where('id=?', [$college['uid']])->find();
                $this->assign('teacher', $teacher);

                return $this;
            }
        }
        return $this->__404();
    }

    function college()
    {
        $filter = [
            'isdelete' => 0,
            'sort' => [['isgood', 'desc'], ['sort', 'asc']],
            'parameter' => [
                'college.id',
                'college.title',
                'upload1.path as logo1',
                'upload2.path as logo2',
                'user.name as username',
                'college.createtime',
                'college.isgood',
                'left(college.description,10) as description',
                '(select count(*) from college_user where college_user.college_id=college.id) as browse'
            ],
        ];
        $college = $this->model('college')->fetchAll($filter);

        //伪造创业学院的课程点击量的初始值
        $init_num = $this->model('system')->get('initnum', 'college');
        foreach ($college as &$c) {
            $c['browse'] += $init_num;
        }

        $this->assign('college', $college);

        $teacher = $this->model('teacher')->table('user', 'left join', 'user.id=teacher.uid')->table('upload', 'left join', 'upload.id=user.gravatar')->orderby('teacher.sort', 'asc')->select([
            'user.id',
            'user.name',
            'user.description',
            'upload.path as gravatar',
        ]);
        $this->assign('teacher', $teacher);

        return $this;
    }

    function cart()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();

        if (!empty($uid)) {

            //筛选所有仓库
            $store = $this->model('cart')
                ->table('product', 'left join', 'product.id=cart.pid')
                ->table('store', 'left join', 'product.store=store.id')
                ->where('product.status=1 and cart.uid=?', [$uid])
                ->groupby('store.id')
                ->select('store.id,store.name');
            $storel = $store;

            $productHelper = new product();
            if ($store) {
                //用户信息
                $user = $this->model('user')->where('id=?', [$uid])->find();
                //在售商品
                foreach ($store as &$st) {
                    $product_filter = [
                        'isdelete' => 0,
                        'status' => 1,
                        'uid' => $uid,
                        'sort' => ['cart.time', 'asc'],
                        'store' => $st['id'],
                        'parameter' => [
                            'product.id',
                            'product.name',
                            'cart.num',
                            'product.price * cart.bind as price',//商品单价 * 用户选择的捆绑数
                            'product.v1price * cart.bind as v1price',
                            'product.v2price * cart.bind as v2price',
                            'product.outside',//是否是海外商品  是海外商品的话显示税率
                            'product.origin',
                            'cart.content', //选择的属性
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
                    foreach ($product as &$p) {
                        $p['origin'] = $this->model('country')->get($p['origin']);
                        $p['image'] = $productHelper->getListImage($p['id']);
                        $p['tax'] = $productHelper->getTaxFields($p['id']);

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
                                }
                            }
                        }

                        //计算总价
                        switch ($user['vip']) {
                            case 0:
                                $amount += $p['price'] * $p['num'];
                                $tax += $productHelper->calculationTax($p['id'], $amount);
                                break;
                            case 1:
                                $amount += $p['v1price'] * $p['num'];
                                $tax += $productHelper->calculationTax($p['id'], $amount);
                                break;
                            case 2:
                                $amount += $p['v2price'] * $p['num'];
                                $tax += $productHelper->calculationTax($p['id'], $amount);
                                break;
                            default:
                                $amount += $p['price'] * $p['num'];
                                $tax += $productHelper->calculationTax($p['id'], $amount);
                        }
                    }
                    $st['product'] = $product;//商品内容
                    $st['amount'] = $amount;//商品总价
                    $st['tax'] = $tax;//实收税款
                    $st['discount'] = 0;//活动优惠? 这个价格怎么出来的?
                }

                $this->assign('store', $store);
                //下架商品
                $cartl = $this->model("cart")
                    ->table('product', 'left join', 'product.id=cart.pid')
                    ->where('cart.uid=? and ((product.status=0 and product.auto_status=0) or (auto_status=1 and (product.avaliabletime_from>? or product.avaliabletime_to<?)))', [$uid,$_SERVER['REQUEST_TIME'],$_SERVER['REQUEST_TIME']])->select([
                        "cart.num",
                        "product.name",
                        "product.id",
                        "product.price * cart.bind as price",//乘以捆绑数
                        "product.v1price * cart.bind as v1price",
                        "product.v2price * cart.bind as v2price",
                    ]);
                foreach ($cartl as &$p) {
                    $p['image'] = $productHelper->getListImage($p['id']);
                }
                $this->assign('cartl', $cartl);

                $this->assign('province', $this->model('province')->select());

                $filter = [
                    'uid' => $uid,
                    'sort' => [['host', 'desc'], ['id', 'desc']],
                    'isdelete' => 0,
                    'parameter' => 'address.id,
								address.name,
								address.telephone,
								address.zcode,
								address.identify,
								address.host,
								address.address,
								province.id as province_id,
								city.id as city_id,
								county.id as county_id,
								province.name as province,
								city.name as city,
								county.name as county'
                ];
                $address = $this->model('address')->fetchAll($filter);
                $this->assign('address', $address);
            }
        } else {
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'login'));
        }
        return $this;
    }

    function favourite()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'login'));
        } else {
            $product_filter = [
                'isdelete' => 0,
                'uid' => $uid,
                'status' => 1,
                'sort' => ['favourite.createtime', 'asc'],
                'parameter' => [
                    'product.id',
                    'product.name',
                    'product.oldprice',
                    'product.price',
                    'product.v1price',
                    'product.v2price',
                    'product.short_description',
                    'store.name as store',
                    'product.origin',
                ]
            ];

            $product = $this->model('favourite')->fetchAll($product_filter);

            $productHelper = new \application\helper\product();
            foreach ($product as &$p) {
                $p['origin'] = $this->model('country')->get($p['origin']);
                $p['image'] = $productHelper->getListImage($p['id']);

                //商品价格
                $filter = [
                    'pid' => $p['id'],
                    'isdelete' => 0,
                    'available' => 1,
                    'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)',
                ];
                $price_collection = $this->model('collection')->fetch($filter);
                if (!empty($price_collection)) {
                    if ($price_collection[0]['sum(stock)'] !== NULL) {
                        $p['stock'] = $price_collection[0]['sum(stock)'];
                    }
                    if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)'] !== NULL) {
                        $p['price'] = $price_collection[0]['min(price)'] . '起';//.'~'.$price_collection[0]['max(price)'];
                    }
                    if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL) {
                        $p['v1price'] = $price_collection[0]['min(v1price)'] . '起';//.'~'.$price_collection[0]['max(v1price)'];
                    }
                    if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL) {
                        $p['v2price'] = $price_collection[0]['min(v2price)'] . '起';//.'~'.$price_collection[0]['max(v2price)'];
                    }
                }
            }

            $this->assign('product', $product);
            return $this;
        }
    }

    function vip()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();

        if (empty($uid)) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'login'));
        } else {
            $user = $this->model('user')->where('id=?', [$uid])->find();
            //获取是否是校园的
            $school = 0;
            if (!empty($user['source'])) {
                $sc = $this->model("source")->where("id=?", [$user['source']])->find();
                $school = $sc['school'];
            }
            if ($user['vip'] == 0 && $school == 0) {
                $this->response->setCode(302);
                $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'login'));
            } else {
                return $this;
            }
        }
    }

    function coupon()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        $uid = 1946;
        if (empty($uid)) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'login'));
        } else {
            $filter = [
                'uid' => $uid,
                'isdelete' => 0,
            ];
            $coupon = $this->model('coupon')->where("used=0")->fetch($filter);
            foreach ($coupon as &$c) {
                $c['product_id'] = empty($c['product_id']) ? 0 : 1;
            }
            // die(json_encode($coupon));
            $this->assign('coupon', $coupon);

            return $this;
        }
    }

    function invalid_coupon()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        $uid = 1946;
        if (empty($uid)) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'login'));
        } else {
            $filter = [
                'uid' => $uid,

            ];
            $coupon = $this->model('coupon')->where("isdelete=1 or used=1")->fetch($filter);
            foreach ($coupon as &$c) {
                $c['product_id'] = empty($c['product_id']) ? 0 : 1;
                $c['endtime'] = 0;
            }
            //die(json_encode($coupon));
            $this->assign('coupon', $coupon);

            return $this;
        }
    }

    function edit_address()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'login'));
        } else {
            $id = $this->get('id', 0, 'intval');
            $filter = [
                'parameter' => 'address.id,
								address.name,
								address.telephone,
								address.zcode,
								address.identify,
								address.host,
								address.address,
								province.id as province_id,
								city.id as city_id,
								county.id as county_id,
								province.name as province,
								city.name as city,
								county.name as county'
            ];
            $address = $this->model('address')->where('address.id=? and address.uid=? and address.isdelete=?', [$id, $uid, 0])->fetchAll($filter);
            if (empty($address)) {
                return $this->__404();
            } else {
                $this->assign('province', $this->model('province')->select());

                $this->assign('address', $address[0]);
                return $this;
            }
        }
    }

    function create_address()
    {
        $userHelper = new user();
        if ($userHelper->isLogin()) {
            $province = $this->model('province')->select();
            $this->assign('province', $province);

            return $this;
        } else {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'login'));
        }
    }

    function address()
    {
        $userHelper = new user();
        if ($userHelper->isLogin()) {
            $filter = [
                'uid' => $userHelper->isLogin(),
                'isdelete' => 0,
                'parameter' => 'address.id,
								address.name,
								address.telephone,
								address.zcode,
								address.identify,
								address.host,
								address.address,
								province.id as province_id,
								city.id as city_id,
								county.id as county_id,
								province.name as province,
								city.name as city,
								county.name as county'
            ];
            $address = $this->model('address')->fetchAll($filter);
            $this->assign('address', $address);

            return $this;
        } else {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'login'));
        }
    }

    function resetpwd()
    {
        if ($this->session->auth_telephone !== true || $_SERVER['REQUEST_TIME'] - $this->session->auth_telephone_time > 5 * 60) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'forgetpwd'));
        } else {
            $this->assign('telephone', $this->session->telephone);
            $this->assign('sms_code', $this->session->sms_code);

            return $this;
        }
    }

    function order()
    {
        $userHelper = new user();

        if (!$userHelper->isLogin()) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'login'));
        } else {
            $filter = [
                'uid' => $userHelper->isLogin(),
                'sort' => ['createtime', 'desc'],
                'isdelete' => 0,
                'parameter' => [
                    '`order`.*',
                ],
            ];
            $order = $this->model('order')->fetchAll($filter);

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
                        'product.outside',
                    ]);

                foreach ($t_order['product'] as &$product) {
                    $total_product_num += $product['num'];
                    $product['image'] = $productHelper->getListImage($product['id']);
                    $product['tax'] = $productHelper->getTaxFields($product['id']);
                }

                $t_order['product_num'] = $total_product_num;


                $task = $this->model('task_user')->where('orderno=?', [$t_order['orderno']])->find();

                $t_order['is_task'] = !empty($task);
                $t_order['tast_status'] = 0;
                $t_order['tid'] = 0;
                if ($t_order['is_task']) {
                    $t_order['tast_status'] = $task['status'];
                    $t_order['tid'] = $task['tid'];
                }
            }

            //公告
            //获取公告
            $note = $this->model("notice")->where("status=true")->find();
            $is_note = false;
            $note_title = "";
            $note_url = '';

            if ($note) {
                $is_note = true;
                $note_url = "http://" . $_SERVER['SERVER_NAME'] . '/index.php?c=mobile&a=notice';

                $note_title = $note['title'];
            }
            //die(json_encode($order));exit;
            $this->assign('is_note', $is_note);
            $this->assign('note_title', $note_title);
            $this->assign('note_url', $note_url);
            $this->assign('order', $order);
            return $this;
        }
    }

    function register2()
    {
        $this->assign('telephone', $this->session->telephone);
        $this->assign('code', $this->session->code);
        return $this;
    }

    function reset_paypassword()
    {
        if ($this->session->auth_paypassword !== true || $_SERVER['REQUEST_TIME'] - $this->session->auth_paypassword_time > 5 * 60) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'auth_paypassword', [
                'href' => urlencode($this->http->url('view', 'mobile', 'reset_paypassword')),
            ]));
        }
        return $this;
    }

    function account_manage()
    {
        if (!(new user())->isLogin()) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'login'));
        }
        return $this;
    }

    function account()
    {
        $userHelper = new \application\helper\user();
        if (!$userHelper->isLogin()) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'mobile', 'login'));
        } else {
            //获取内容
            $list = $this->model("center_list")->where("is_del=0")->select();

            $this->assign('list', $list);
            return $this;
        }

    }

    /**
     * 商品详情
     */
    function product()
    {

        $id = $this->get('id', 0, 'intval');
        $product = $this->model('product')
            ->table('store', 'left join', 'product.store=store.id')
            ->where('product.id=? and product.isdelete=?', [$id, 0])
            ->find([
                'product.id',//id
                'product.name',
                'product.description',
                'product.origin',
                'product.oldprice',//下面4个是价格
                'product.price',
                'product.v1price',
                'product.v2price',
                'product.auto_status',//下面4个状态判断
                'product.status',
                'product.avaliabletime_from',
                'product.avaliabletime_to',
                'product.auto_stock',//是否库存限制
                'product.stock',//库存
                'store.name as store',//发货仓库
                'product.tags',//商品标签
                'product.outside',//是否是海外商品
                'product.short_description',//短描述
                'product.freetax',//是否保税 1 包
                'product.ztax',//是否保税 1 包
                'product.selled',//起售数
            ]);

        //商品是否存在
        if (!empty($product))
        {
        	$product['price'] = $product['price'] * $product['selled'];
        	$product['oldprice'] = $product['oldprice'] * $product['selled'];
        	$product['v1price'] = $product['v1price'] * $product['selled'];
        	$product['v2price'] = $product['v2price'] * $product['selled'];
        	
            //商品上下架
            if (
                ($product['auto_status'] == 1 && ($_SERVER['REQUEST_TIME'] < $product['avaliabletime_from'] || $_SERVER['REQUEST_TIME'] > $product['avaliabletime_to']))
                || ($product['auto_status'] == 0 && $product['status'] == 0)
            ) {
                $this->assign('product_status', 0);
            } else {
                $this->assign('product_status', 1);
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
                    $product['price'] = $price_collection[0]['min(price)'] * $product['selled'] . '起';//.'~'.$price_collection[0]['max(price)'];
                }
                if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL) {
                    $product['v1price'] = $price_collection[0]['min(v1price)'] * $product['selled'] . '起';//.'~'.$price_collection[0]['max(v1price)'];
                }
                if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL) {
                    $product['v2price'] = $price_collection[0]['min(v2price)'] * $product['selled'] . '起';//.'~'.$price_collection[0]['max(v2price)'];
                }
            }

            //字典替换
            $product['origin'] = $this->model('country')->get($product['origin']);

            //收藏判断
            $userHelper = new user();
            $uid = $userHelper->isLogin();
            $productHelper = new \application\helper\product();
            $product['favourite'] = intval($productHelper->isFavourite($uid, $id));

            //税
            $product['tax'] = $productHelper->getTaxFields($id);

            //商品详情图
            $product['image'] = $productHelper->getDetailImage($id);
            $product['listImage'] = $productHelper->getListImage($id, true);

            //商品属性
            $filter = [
                'pid' => $id,
                'isdelete' => 0,
                'parameter' => 'name,type,value',
                'type' => 'radio',
            ];
            $product['radioPrototype'] = $this->model('prototype')->fetch($filter);
            $filter = [
                'pid' => $id,
                'isdelete' => 0,
                'parameter' => 'name,type,value',
                'type' => 'text',
            ];
            $product['textPrototype'] = $this->model('prototype')->fetch($filter);

            //商品的可选属性集合
            $filter = [
                'pid' => $id,
                'isdelete' => 0,
                'parameter' => [
                    'content',
                    'price',
                    'v1price',
                    'v2price',
                    'sku',
                    'stock',
                    'upload.path as logo',
                    'available',
                ]
            ];
            $collections = $this->model('collection')->fetchAll($filter);
            foreach ($collections as &$collection)
            {
            	$collection['price'] = $collection['price'] * $product['selled'];
            	$collection['v1price'] = $collection['v1price'] * $product['selled'];
            	$collection['v2price'] = $collection['v2price'] * $product['selled'];
            }
            $product['collection'] = $collections;
            
            $this->assign('product', $product);
            //die(json_encode($product));
            $this->assign('province', $this->model('province')->select());

            $filter = [
                'uid' => $uid,
                'sort' => [['host', 'desc'], ['id', 'desc']],
                'isdelete' => 0,
                'parameter' => 'address.id,
								address.name,
								address.telephone,
								address.zcode,
								address.identify,
								address.host,
								address.address,
								province.id as province_id,
								city.id as city_id,
								county.id as county_id,
								province.name as province,
								city.name as city,
								county.name as county'
            ];
            $address = $this->model('address')->fetchAll($filter);
            $this->assign('address', $address);

            $temp = [];
            $share = $this->model('system')->where('type=?', ['share'])->select();
            foreach ($share as $s) {
                $temp[$s['name']] = $s['value'];
            }
            $this->assign('share', $temp);
            $this->assign('weibo_appkey', $this->model('system')->get('appkey', 'weibo'));
            //获取购物车数量
            $cartnum = $this->model("cart")->where("uid=?", [$uid])->find('sum(num) as cou');
            $this->assign('num', $cartnum['cou'] > 0 ? $cartnum['cou'] : 0);
            //税收
            $sui = 0;
            if (intval($product['ztax']) > 0) {
                $sui = $this->model('tax')->where("id=?", [$product['ztax']])->find(['ztax']);

                $sui = $sui['ztax'];
            }
            $sui *= 100;

            $this->assign('ztax', $sui);

            return $this;
        }
        return $this->__404();
    }

    /**
     * 分类下的商品列表
     * @return \application\control\view\mobile
     */
    function category_product()
    {
        $id = $this->get('id');
        $category = $this->model('category')->where('id=? and isdelete=?', [$id, 0])->find();
        if (!empty($category)) {
            $this->assign('category', $category);

            $product_filter = [
                'isdelete' => 0,
                'status' => 1,
                'cid' => $id,
                'sort' => [['product.sort', 'asc'], ['product.createtime', 'desc']],
                'parameter' => [
                    'product.id',
                    'product.name',
                    'product.oldprice',
                    'product.price',
                    'product.v1price',
                    'product.v2price',
                    'product.short_description',
                    'store.name as store',
                    'product.origin',
                	'product.selled'
                ]
            ];
            $product = $this->model('category_product')->fetchAll($product_filter);
            $productHelper = new product();
            foreach ($product as &$p) {
            	$p['oldprice'] = $p['oldprice'] * $p['selled'];
            	$p['price'] = $p['price'] * $p['selled'];
            	$p['v1price'] = $p['v1price'] * $p['selled'];
            	$p['v2price'] = $p['v2price'] * $p['selled'];
            	
                $p['origin'] = $this->model('country')->get($p['origin']);

                $p['image'] = $productHelper->getListImage($p['id']);

                //商品价格
                $filter = [
                    'pid' => $p['id'],
                    'isdelete' => 0,
                    'available' => 1,
                    'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)',
                ];
                $price_collection = $this->model('collection')->fetch($filter);
                if (!empty($price_collection)) {
                    if ($price_collection[0]['sum(stock)'] !== NULL) {
                        $p['stock'] = $price_collection[0]['sum(stock)'];
                    }
                    if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)'] !== NULL) {
                        $p['price'] = $price_collection[0]['min(price)'] * $p['selled'] . '起';//.'~'.$price_collection[0]['max(price)'];
                    }
                    if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL) {
                        $p['v1price'] = $price_collection[0]['min(v1price)'] * $p['selled'] . '起';//.'~'.$price_collection[0]['max(v1price)'];
                    }
                    if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL) {
                        $p['v2price'] = $price_collection[0]['min(v2price)'] * $p['selled'] . '起';//.'~'.$price_collection[0]['max(v2price)'];
                    }
                }
            }

            $this->assign('product', $product);

            return $this;
        }

        return $this->__404();
    }

    /**
     * 分类页
     */
    function category()
    {
        $filter = [
            'isdelete' => 0,
            'cid' => 0,
            'sort' => ['category.sort', 'asc'],
            'parameter' => 'category.id,
							category.name,
							upload.path as logo,
							category.description',
        ];
        $category = $this->model('category')->fetchAll($filter);
        $this->assign('category', $category);

        return $this;
    }

    /**
     * 手机端搜索页
     */
    function search()
    {
        $words = $this->model('search_log')->where('time > ?', [$_SERVER['REQUEST_TIME'] - 30 * 3600 * 24])->limit(0, 20)->groupby('keywords')->orderby('num', 'desc')->orderby('time', 'desc')->select([
            'count(*) as num',
            'keywords',
            'time',
        ]);
        $this->assign('words', $words);

        return $this;
    }

    /**
     * 分享
     * @return \application\control\view\mobile
     */
    function spread()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('', 'mobile', 'login'));
        } else {
            $temp = [];
            $share = $this->model('system')->where('type=?', ['share'])->select();
            foreach ($share as $s) {
                $temp[$s['name']] = $s['value'];
            }
            $this->assign('share', $temp);
            $this->assign('weibo_appkey', $this->model('system')->get('appkey', 'weibo'));
            return $this;
        }
    }

    function login()
    {
        $qq_appid = $this->model('system')->get('appid', 'qq');
        $this->assign('qq_appid', $qq_appid);

        $weibo_appkey = $this->model('system')->get('appkey', 'weibo');
        $this->assign('weibo_appkey', $weibo_appkey);

        return $this;
    }

    /**
     * 手机端首页
     * @return \application\control\view\mobile
     */
    function index()
    {
        $carousel = $this->model('carousel')->table('upload', 'left join', 'upload.id=carousel.logo')->where('carousel.isdelete=? and carousel.position=?', [0, 'index'])->orderby('sort', 'asc')->select([
            'carousel.id',
            'upload.path as logo',
            'carousel.linktype',
            'carousel.url',
        ]);
        $this->assign('carousel', $carousel);

        $product_filter = [
            'isdelete' => 0,
            'sort' => ['product_top.sort', 'asc'],
            'status' => 1,
            'parameter' => [
                'product.id',
                'product.name',
                'product.oldprice',
                'product.price',
                'product.v1price',
                'product.v2price',
                'product.short_description',
                'store.name as store',
                'product.origin',
                'product.stock',
            	'product.selled',
            ],
        ];
        $product = $this->model('product_top')
            ->fetchAll($product_filter);
        $productHelper = new \application\helper\product();

        $theme = $this->model('subtheme_product')
            ->where("subtheme_product.subtheme_id=27")->select(['product_id']);

        foreach ($theme as &$t) {
            $t = $t['product_id'];
        }


        foreach ($product as &$p) {
        	$p['oldprice'] = $p['oldprice']  * $p['selled'];
        	$p['price'] = $p['price']  * $p['selled'];
        	$p['v1price'] = $p['v1price'] * $p['selled'];
        	$p['v2price'] = $p['v2price'] * $p['selled'];
        	
            if (in_array($p['id'], $theme)) {
                $p['is_theme'] = 1;
            } else {
                $p['is_theme'] = 0;
            }
            $p['origin'] = $this->model('country')->get($p['origin']);
            $p['image'] = $productHelper->getListImage($p['id']);

            //商品价格
            $filter = [
                'pid' => $p['id'],
                'isdelete' => 0,
                'available' => 1,
                'parameter' => 'max(price),min(price),max(v1price),min(v1price),max(v2price),min(v2price),sum(stock)',
            ];
            $price_collection = $this->model('collection')->fetch($filter);
            if (!empty($price_collection)) {
                if ($price_collection[0]['sum(stock)'] !== NULL) {
                    $p['stock'] = $price_collection[0]['sum(stock)'];
                }
                if ($price_collection[0]['min(price)'] !== NULL && $price_collection[0]['max(price)'] !== NULL) {
                    $p['price'] = $price_collection[0]['min(price)'] * $p['selled'] . '起';//.'~'.$price_collection[0]['max(price)'];
                }
                if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL) {
                    $p['v1price'] = $price_collection[0]['min(v1price)'] * $p['selled'] . '起';//.'~'.$price_collection[0]['max(v1price)'];
                }
                if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL) {
                    $p['v2price'] = $price_collection[0]['min(v2price)'] * $p['selled'] . '起';//.'~'.$price_collection[0]['max(v2price)'];
                }
            }
        }

        $this->assign('product', $product);

        return $this;
    }

    /**
     * 消息提示页面
     * @param unknown $msg
     * @param number $second
     * @param string $url
     */
    private function message($msg, $second = 5, $url = '')
    {
        return $msg;
    }

    /**
     * 404页面
     */
    function __404()
    {
        return '404';
    }

    function notice()
    {
        $note = $this->model("notice")->where("status=true")->find();
        if (!$note) {
            return $this->__404();
        }
        $type = $this->get("type");
        $this->assign("type", $type);
        $this->assign("note", $note);
        return $this;
    }

    function student_confirm()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('', 'mobile', 'login'));
        }

        $user = $this->model("user")->table("upload","left join","upload.id=user.gravatar")->where("user.id=?", [$uid])->find(["user.*","upload.path"]);

        // die(json_encode($user));
        $this->assign('user', $user);
        return $this;

    }
}