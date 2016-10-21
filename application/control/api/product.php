<?php
namespace application\control\api;

use application\message\json;
use application\helper\user;

class product extends common
{
    private $_response;

    function __construct()
    {
        parent::__construct();
        $this->_response = $this->init();
    }


    /**
     * 获取商品详情
     */
    function detail()
    {
        if (!empty($this->_response))
            return $this->_response;

        $id = $this->data('id');
        $product = $this->model('product')
            ->table('store', 'left join', 'product.store=store.id')
            ->where('product.id=? and product.isdelete=?', [$id, 0])
            ->find([
                'product.id',//id
                'product.name',
                'product.description',
                'product.origin',
                'product.oldprice  as oldprice',//下面4个是价格
                'product.price  as price',
                'product.v1price  as v1price',
                'product.v2price  as v2price',
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
                'product.freetax',
            	'product.selled',//起售数
            ]);

        //商品是否存在
        if (empty($product)) {
            return new json(json::PARAMETER_ERROR, '商品不存在');
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
                $product['price'] = $price_collection[0]['min(price)']  . '~' . $price_collection[0]['max(price)'] ;
            }
            if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL) {
                $product['v1price'] = $price_collection[0]['min(v1price)']  . '~' . $price_collection[0]['max(v1price)'];
            }
            if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL) {
                $product['v2price'] = $price_collection[0]['min(v2price)']  . '~' . $price_collection[0]['max(v2price)'];
            }
        }

        //字典替换
        $product['origin'] = $this->model('country')->get($product['origin']);

        //收藏判断
        $userHelper = new user();
        $uid = $userHelper->isLogin();
        $productHelper = new \application\helper\product();
        $product['favourite'] = intval($productHelper->isFavourite($uid, $id));

        //商品详情图
        $product['image'] = $productHelper->getDetailImage($id);
        $product['tax'] = $productHelper->getTaxFields($id);

        //商品属性
        $filter = [
            'pid' => $id,
            'isdelete' => 0,
            'parameter' => 'name,type,value',
        ];
        $product['prototype'] = $this->model('prototype')->fetch($filter);

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

        $bind = $this->model('bind')->where('pid=?', [$id])->orderby('sort','asc')->select();
        foreach ($bind as &$b) {
            if ($b['content'] != '') {
                $stock = $this->model("collection")->where("pid=? and content=?", [$b['pid'], $b['content']])->find(['stock']);
                $b['stock'] = $stock['stock'];
            } else {
                $b['stock'] = $product['stock'];
            }
            
            $b['price'] = $b['price'] * $b['num'];
            $b['v1price'] = $b['v1price'] * $b['num'];
            $b['v2price'] = $b['v2price'] * $b['num'];
            $b['inprice'] = $b['inprice'] * $b['num'];
            $b['price']=sprintf("%.2f", $b['price']);
            $b['v1price'] = sprintf("%.2f", $b['v1price']);
            $b['v2price'] = sprintf("%.2f", $b['v2price']);
            $b['inprice'] = sprintf("%.2f", $b['inprice']);
        }
        $product['bind'] = $bind;

        return new json(json::OK, NULL, $product);
    }

    /**
     * 搜索热词
     */
    function searchwords()
    {
        if (!empty($this->_response))
            return $this->_response;

        $start = $this->data('start', 0);
        $length = $this->data('length', 10);
        //一个月以内的热搜词
        $words = $this->model('search_log')->where('time > ?', [$_SERVER['REQUEST_TIME'] - 30 * 3600 * 24])->limit($start, $length)->groupby('keywords')->orderby('num', 'desc')->orderby('time', 'desc')->select([
            'count(*) as num',
            'keywords',
            'time',
        ]);

        $total = $this->model('search_log')->where('time > ?', [$_SERVER['REQUEST_TIME'] - 30 * 3600 * 24])->groupby('keywords')->orderby('num', 'desc')->orderby('time', 'desc')->select([
            'count(*) as num',
            'keywords',
            'time',
        ]);

        $searchWordsReturnModel = [
            'current' => count($words),
            'total' => count($total),
            'start' => $this->data('start', 0),
            'length' => $this->data('length', 10),
            'data' => $words,
        ];

        return new json(json::OK, NULL, $searchWordsReturnModel);
    }

    /**
     * 商品搜索
     * @return \application\message\json
     */
    function search()
    {
        if (!empty($this->_response))
            return $this->_response;

        $keywords = htmlspecialchars($this->data('keywords', '','trim'));
        $keywords = substr($keywords, 0, 32);

        $product_filter = [
            'isdelete' => 0,
            'status' => 1,
            'start' => $this->data('start', 0),
            'length' => $this->data('length', 10),
            'sort' => [['product.sort', 'asc'], ['product.createtime', 'desc']],
            'parameter' => [
                'product.id',
                'product.name',
                'product.oldprice  as oldprice',
                'product.price  as price',
                'product.v1price  as v1price',
                'product.v2price  as v2price',
                'product.short_description',
                'store.name as store',
                'product.origin',
            	'product.selled',
            ]
        ];
        
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
                    $p['price'] = $price_collection[0]['min(price)']  ;//'~'.$price_collection[0]['max(price)'];
                }
                if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL) {
                    $p['v1price'] = $price_collection[0]['min(v1price)'] ;//'~'.$price_collection[0]['max(v1price)'];
                }
                if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL) {
                    $p['v2price'] = $price_collection[0]['min(v2price)']  ;//'~'.$price_collection[0]['max(v2price)'];
                }
            }
        }

        $product_filter['parameter'] = 'count(*)';
        unset($product_filter['start']);
        unset($product_filter['length']);
        $total = $this->model('product')->fetchAll($product_filter);

        $productReturnModel = [
            'current' => count($product),
            'total' => isset($total[0]['count(*)']) ? $total[0]['count(*)'] : 0,
            'start' => $this->data('start', 0),
            'length' => $this->data('length', 10),
            'data' => $product,
        ];
        
        if (!empty($keywords)) {
        	$product_filter['name'] = '%' . $keywords . '%';
        
        	$userHelper = new user();
        	$this->model('search_log')->insert([
        		'ip' => ip(),
        		'keywords' => $keywords,
        		'time' => $_SERVER['REQUEST_TIME'],
        		'uid' => $userHelper->isLogin(),
        		'total' => isset($total[0]['count(*)']) ? $total[0]['count(*)'] : 0,
        		'userAgent' => \application\helper\api::getUser(),
        	]);
        }

        return new json(json::OK, NULL, $productReturnModel);
    }

    /**
     * 首页商品
     */
    function top()
    {
        $product_filter = [
            'isdelete' => 0,
            'sort' => [ ['product.modifytime', 'desc']],
            'status' => 1,
            'start' => $this->data('start', 0),
            'length' => $this->data('length', 10),
            'parameter' => [
                'product.id',
                'product.name',
                'product.oldprice  as oldprice',
                'product.price  as price',
                'product.v1price as v1price',
                'product.v2price as v2price',
                'product.short_description',
                'store.name as store',
                'product.origin',
                'product.stock',
            	'product.selled',
            ]
        ];
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
                    $p['price'] = $price_collection[0]['min(price)']  . '~' . $price_collection[0]['max(price)'] ;
                }
                if ($price_collection[0]['min(v1price)'] !== NULL && $price_collection[0]['max(v1price)'] !== NULL) {
                    $p['v1price'] = $price_collection[0]['min(v1price)']  . '~' . $price_collection[0]['max(v1price)'] ;
                }
                if ($price_collection[0]['min(v2price)'] !== NULL && $price_collection[0]['max(v2price)'] !== NULL) {
                    $p['v2price'] = $price_collection[0]['min(v2price)']  . '~' . $price_collection[0]['max(v2price)'] ;
                }
            }
        }

        $product_filter['parameter'] = 'count(*)';
        unset($product_filter['start']);
        unset($product_filter['length']);


        $productReturnModel = [
            'current' => count($product),
            'total' => 5,
            'start' => $this->data('start', 0),
            'length' => $this->data('length', 10),
            'data' => $product,
        ];
        return new json(json::OK, NULL, $productReturnModel);
    }
}