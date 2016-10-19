<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;
use application\helper as helper;

class product extends ajax
{
    /**
     * 添加商品
     */
    function create()
    {
        $admin = $this->session->id;
        $this->model('product')->transaction();
        $productHelper = new helper\product();

        $product = $productHelper->createProductData($this->post());

        if (empty($product['store'])) {
            $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（请选择发货仓库）');
            return new json(json::PARAMETER_ERROR, '请选择发货仓库');
        }
        if ($product['outside'] == 2) {
            if (empty($product['ztax'])) {
                $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（请填写综合税种）');
                return new json(json::PARAMETER_ERROR, '请填写综合税种');
            }
        }
        if ($product['outside'] == 3) {
            if (empty($product['postTaxNo'])) {
                $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（请选择行邮税号）');
                return new json(json::PARAMETER_ERROR, '请选择行邮税号');
            }
        }
        if ($product['status'] == 1) {
            if (floatval($product['inprice']) == 0) {
                $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（进价不能为0）');
                return new json(json::PARAMETER_ERROR, '进价不能为0');
            }
        }
        if (empty($product['barcode'])) {
            $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（条形码不能为空）');
            return new json(json::PARAMETER_ERROR, '条形码不能为空');
        }
        if (intval($product['selled']) <= 0) {
            $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（售卖数不能为空）');
            return new json(json::PARAMETER_ERROR, '售卖数不能为空');
        }

        if ($this->model('product')->where('barcode=? and isdelete=?', [$product['barcode'], 0])->find()) {
            $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（存在相同条形码的商品）');
            return new json(json::PARAMETER_ERROR, '存在相同条形码的商品');
        }

        if ($this->model('product')->insert($product)) {
            $product_id = $this->model('product')->lastInsertId();
            if (is_array($this->post('category')) && !empty($this->post('category'))) {
                foreach ($this->post('category') as $category) {
                    if (!$this->model('category_product')->insert([
                        'cid' => $category,
                        'pid' => $product_id,
                        'createtime' => $_SERVER['REQUEST_TIME'],
                        'deletetime' => 0,
                        'isdelete' => 0,
                    ])
                    ) {
                        $this->model('product')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（分配分类错误）');
                        return new json(json::PARAMETER_ERROR, '分配分类错误');
                    }
                }
            }

            $hasListImage = false;
            if (is_array($this->post('image')) && !empty($this->post('image'))) {

                foreach ($this->post('image') as $image) {
                    if ($image['position'] == 1) {
                        $hasListImage = true;
                    }
                    if (!$this->model('product_img')->insert([
                        'pid' => $product_id,
                        'fid' => $image['id'],
                        'sort' => $image['sort'],
                        'position' => $image['position'],
                        'createtime' => $_SERVER['REQUEST_TIME'],
                        'deletetime' => 0,
                        'isdelete' => 0,
                    ])
                    ) {
                        $this->model('product')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（图片添加失败）');
                        return new json(json::PARAMETER_ERROR, '图片添加失败');
                    }
                }
            }

            if (!$hasListImage) {
                $this->model('product')->rollback();
                $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（必须设置列表图）');
                return new json(json::PARAMETER_ERROR, '必须设置列表图');
            }

            if (is_array($this->post('prototype')) && !empty($this->post('prototype'))) {
                foreach ($this->post('prototype') as $prototype) {
                    if (!$this->model('prototype')->insert([
                        'pid' => $product_id,
                        'name' => $prototype['name'],
                        'type' => $prototype['type'],
                        'value' => $prototype['value'],
                        'createtime' => $_SERVER['REQUEST_TIME'],
                        'deletetime' => $_SERVER['REQUEST_TIME'],
                        'isdelete' => 0,
                    ])
                    ) {
                        $this->model('product')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（属性添加失败）');
                        return new json(json::PARAMETER_ERROR, '属性添加失败');
                    }
                }
            }

            if (is_array($this->post('collection')) && !empty($this->post('collection'))) {
                foreach ($this->post('collection') as $collection) {
                    if (!$this->model('collection')->insert([
                        'pid' => $product_id,
                        'content' => $collection['content'],
                        'price' => $collection['price'],
                        'v1price' => $collection['v1price'],
                        'v2price' => $collection['v2price'],
                        'stock' => $collection['stock'],
                        'sku' => $collection['sku'],
                        'logo' => empty($collection['logo']) ? NULL : $collection['logo'],
                        'deletetime' => 0,
                        'createtime' => $_SERVER['REQUEST_TIME'],
                        'isdelete' => 0,
                        'available' => $collection['available'],
                    ])
                    ) {
                        $this->model('product')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（多属性添加失败）');
                        return new json(json::PARAMETER_ERROR, '多属性添加失败');
                    }
                }
            }

            if (is_array($this->post('fee_province')) && !empty($this->post('fee_province'))) {
                foreach ($this->post('fee_province') as $province) {
                    if (!$this->model('product_province')->insert([
                        'product_id' => $product_id,
                        'province_id' => $province
                    ])
                    ) {
                        $this->model('province')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（添加配送城市失败）');
                        return new json(json::PARAMETER_ERROR, '添加配送城市失败');
                    }
                }
            }

            if (is_array($this->post('bind')) && !empty($this->post('bind'))) {
                foreach ($this->post('bind') as $bind) {
                    if (empty($bind['num']) || empty($bind['unit']) || empty($bind['sort']) || empty($bind['price']) || empty($bind['inprice']) || empty($bind['v1price']) || empty($bind['v2price'])) {
                        continue;
                    }
                    if (!empty($this->model('bind')->where('pid=? and content=? and num=?', [$product_id, $bind['content'], $bind['num']])->find())) {
                        $this->model('product')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（无法捆绑相同数量的商品）');
                        return new json(json::PARAMETER_ERROR, '无法捆绑相同数量的商品');
                    }
                    if (!$this->model('bind')->insert([
                        'pid' => $product_id,
                        'content' => $bind['content'],
                        'num' => $bind['num'],
                        'inprice' => $bind['inprice'],
                        'price' => $bind['price'],
                        'v1price' => $bind['v1price'],
                        'v2price' => $bind['v2price'],
                        'unit' => $bind['unit'],
                        'sort' => $bind['sort'],
                    ])
                    ) {
                        $this->model('product')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败（商品捆绑添加失败）');
                        return new json(json::PARAMETER_ERROR, '商品捆绑添加失败');
                    }
                }
            }

            $this->model('product')->commit();
            $this->model("admin_log")->insertlog($admin, '商品管理，增加商品成功，商品id：' . $product_id, 1);
            return new json(json::OK, NULL, $product_id);
        } else {
            $this->model('product')->rollback();
            $this->model("admin_log")->insertlog($admin, '商品管理，增加商品失败');
            return new json(json::PARAMETER_ERROR, '添加失败');
        }
    }


    function search()
    {
        $keywords = $this->get('keywords', '', 'trim');
        $product_filter = [
            'name' => '%' . $keywords . '%',
            'isdelete' => 0,
            'start' => $this->get('start', 0),
            'length' => $this->get('length', 10),
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
                'product.stock',
            ]
        ];
        $product = $this->model('product')->fetchAll($product_filter);
        $productHelper = new \application\helper\product();
        foreach ($product as &$p) {
            $p['origin'] = $this->model('dictionary')->get($p['origin'], 'name');
            $p['image'] = $productHelper->getListImage($p['id']);

            //销售量
            $p['sell'] = $this->model('order_product')->table('order_package', 'left join', 'order_package.id=order_product.package_id')->table('`order`', 'left join', 'order.orderno=order_package.orderno')->where('order.pay_status=?', [1])->where('order_product.pid=?', [$p['id']])->find('sum(order_product.num)');
            $p['sell'] = empty($p['sell']['sum(order_product.num)']) ? 0 : $p['sell']['sum(order_product.num)'];

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

        $product_filter['parameter'] = 'count(*)';
        unset($product_filter['start']);
        unset($product_filter['length']);
        $total = $this->model('product')->fetchAll($product_filter);

        $productReturnModel = [
            'current' => count($product),
            'total' => isset($total[0]['count(*)']) ? $total[0]['count(*)'] : 0,
            'start' => $this->get('start', 0),
            'length' => $this->get('length', 10),
            'data' => $product,
        ];

        return new json(json::OK, NULL, $productReturnModel);
    }

    /**
     * 移动首页商品排序
     */
    function topmove()
    {
        $admin = $this->session->id;
        $id = $this->post('id');
        $forward = $this->post('forward', 'up');
        $line = $this->model('product_top')->orderby('sort', 'asc')->select();

        foreach ($line as $index => $product) {
            if ($product['pid'] == $id) {
                $flag = $index;
            }
        }

        if ($flag == 0 && $forward == 'up') {
            $this->model("admin_log")->insertlog($admin, '商品管理，首页商品排序', 1);
            return new json(json::OK);
        }
        if ($flag == count($line) - 1 && $forward == 'down') {
            $this->model("admin_log")->insertlog($admin, '商品管理，首页商品排序', 1);
            return new json(json::OK);
        }
        if ($forward == 'up') {
            $temp = $line[$flag];
            $line[$flag] = $line[$flag - 1];
            $line[$flag - 1] = $temp;
        } else if ($forward == 'down') {
            $tmep = $line[$flag];
            $line[$flag] = $line[$flag + 1];
            $line[$flag + 1] = $tmep;
        }

        foreach ($line as $index => $product) {
            $this->model('product_top')->where('pid=?', [$product['pid']])->limit(1)->update('sort', $index);
        }
        $this->model("admin_log")->insertlog($admin, '商品管理，首页商品排序', 1);
        return new json(json::OK);
    }

    /**
     * 将商品从首页上下架
     */
    function untop()
    {
        $admin = $this->session->id;
        $id = $this->post('id');
        if (!empty($id)) {
            if ($this->model('product_top')->where('pid=?', [$id])->delete()) {
                $this->model("admin_log")->insertlog($admin, '商品管理，将商品从首页下架,商品id：' . $id, 1);
                return new json(json::OK);
            }
            $this->model("admin_log")->insertlog($admin, '商品管理，将商品从首页下架（参数错误）');
            return new json(json::PARAMETER_ERROR);
        }
        $this->model("admin_log")->insertlog($admin, '商品管理，将商品从首页下架（参数错误）');
        return new json(json::PARAMETER_ERROR);
    }

    /**
     * 将商品推送到首页
     * @return \application\message\json
     */
    function top()
    {
        $admin = $this->session->id;
        $id = $this->post('id');
        if (!empty($id)) {
            if (empty($this->model('product_top')->where('pid=?', [$id])->find())) {
                $total = $this->model('product_top')->select('count(*)');

                $data = [
                    'pid' => $id,
                    'sort' => $total[0]['count(*)'],
                    'time' => $_SERVER['REQUEST_TIME'],
                ];
                if ($this->model('product_top')->insert($data)) {
                    $data = $this->model('product_top')->table('product', 'left join', 'product.id=product_top.pid')->where('product.id=?', [$id])->find('product.name,product.id,product_top.sort');
                    $this->model("admin_log")->insertlog($admin, '商品管理，将商品推送到首页，商品id:' . $id, 1);
                    return new json(json::OK, NULL, $data);
                }
                $this->model("admin_log")->insertlog($admin, '商品管理，将商品推送到首页(请求参数错误)');
                return new json(json::PARAMETER_ERROR);
            }
            $this->model("admin_log")->insertlog($admin, '商品管理，将商品推送到首页(商品已经存在)');
            return new json(json::PARAMETER_ERROR, '已经存在了');
        }
    }


    /**
     * 保存商品信息
     */
    function save()
    {
        $admin = $this->session->id;
        $this->model('product')->transaction();
        $productHelper = new helper\product();


        $product = $productHelper->createProductData($this->post());
        if (!isset($product['id'])) {
            $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(商品id错误)');
            return new json(json::PARAMETER_ERROR, '商品id错误');
        }

        $product_id = $product['id'];

        //保存的时候商品创建时间不做任何修改
        unset($product['createtime']);

        //商品修改时间
        $product['modifytime'] = $_SERVER['REQUEST_TIME'];

        if (empty($product['store'])) {
            $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(请选择发货仓库)');
            return new json(json::PARAMETER_ERROR, '请选择发货仓库');
        }
        if ($product['outside'] == 2) {
            if (empty($product['ztax'])) {
                $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(请填写综合税种)');
                return new json(json::PARAMETER_ERROR, '请填写综合税种');
            }
        }
        if ($product['outside'] == 3) {
            if (empty($product['postTaxNo'])) {
                $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(请选择行邮税号)');
                return new json(json::PARAMETER_ERROR, '请选择行邮税号');
            }
        }
        if ($product['status'] == 1) {
            if (floatval($product['inprice']) == 0) {
                $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(进价不能为0)');
                return new json(json::PARAMETER_ERROR, '进价不能为0');
            }
        }
        if (empty($product['barcode'])) {
            $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(条形码不能为空)');
            return new json(json::PARAMETER_ERROR, '条形码不能为空');
        }
        if (intval($product['selled']) <= 0) {
            $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(售卖数不能为空)');
            return new json(json::PARAMETER_ERROR, '售卖数不能为空');
        }

       /* if ($this->model('product')->where('id!=? and barcode=? and isdelete=?', [$product_id, $product['barcode'], 0])->find()) {
            $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(存在相同条形码的商品)');
            return new json(json::PARAMETER_ERROR, '存在相同条形码的商品');
        }*/

        if ($this->model('product')->where('id=?', [$product_id])->update($product, '', true)) {
            $this->model('category_product')->where('pid=?', [$product_id])->delete();
            if (is_array($this->post('category')) && !empty($this->post('category'))) {
                foreach ($this->post('category') as $category) {
                    if (!$this->model('category_product')->insert([
                        'cid' => $category,
                        'pid' => $product_id,
                        'createtime' => $_SERVER['REQUEST_TIME'],
                        'deletetime' => 0,
                        'isdelete' => 0,
                    ])
                    ) {
                        $this->model('product')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(分配分类错误)');
                        return new json(json::PARAMETER_ERROR, '分配分类错误');
                    }
                }
            }

            $hasListImage = false;
            $this->model('product_img')->where('pid=?', [$product_id])->delete();
            if (is_array($this->post('image')) && !empty($this->post('image'))) {
                foreach ($this->post('image') as $image) {
                    if ($image['position'] == 1) {
                        $hasListImage = true;
                    }
                    if (!$this->model('product_img')->insert([
                        'pid' => $product_id,
                        'fid' => $image['id'],
                        'sort' => $image['sort'],
                        'position' => $image['position'],
                        'createtime' => $_SERVER['REQUEST_TIME'],
                        'deletetime' => 0,
                        'isdelete' => 0,
                    ])
                    ) {
                        $this->model('product')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(图片添加失败)');
                        return new json(json::PARAMETER_ERROR, '图片添加失败');
                    }
                }
            }
            if (!$hasListImage) {
                $this->model('product')->rollback();
                $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(必须设置列表图)');
                return new json(json::PARAMETER_ERROR, '必须设置列表图');
            }

            $this->model('prototype')->where('pid=?', [$product_id])->delete();
            if (is_array($this->post('prototype')) && !empty($this->post('prototype'))) {
                foreach ($this->post('prototype') as $prototype) {
                    if (!$this->model('prototype')->insert([
                        'pid' => $product_id,
                        'name' => $prototype['name'],
                        'type' => $prototype['type'],
                        'value' => $prototype['value'],
                        'createtime' => $_SERVER['REQUEST_TIME'],
                        'deletetime' => $_SERVER['REQUEST_TIME'],
                        'isdelete' => 0,
                    ])
                    ) {
                        $this->model('product')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(属性添加失败)');
                        return new json(json::PARAMETER_ERROR, '属性添加失败');
                    }
                }
            }

            $result = $this->model('collection')->where('pid=?', [$product_id])->find();

            if (!empty($result)) {
                if (!$this->model('collection')->where('pid=?', [$product_id])->delete()) {
                    $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(collection更新中断)');
                    return new json(json::PARAMETER_ERROR, 'collection更新中断');
                }
            }

            if (is_array($this->post('collection')) && !empty($this->post('collection'))) {
                foreach ($this->post('collection') as $collection) {
                    if (!$this->model('collection')->insert([
                        'pid' => $product_id,
                        'content' => $collection['content'],
                        'price' => $collection['price'],
                        'v1price' => $collection['v1price'],
                        'v2price' => $collection['v2price'],
                        'stock' => $collection['stock'],
                        'sku' => $collection['sku'],
                        'logo' => empty($collection['logo']) ? NULL : $collection['logo'],
                        'deletetime' => 0,
                        'createtime' => $_SERVER['REQUEST_TIME'],
                        'isdelete' => 0,
                        'available' => $collection['available']
                    ])
                    ) {
                        $this->model('product')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(多属性添加失败)');
                        return new json(json::PARAMETER_ERROR, '多属性添加失败');
                    }
                }
            }


            $this->model('product_province')->where('product_id=?', [$product_id])->delete();
            if (is_array($this->post('fee_province')) && !empty($this->post('fee_province'))) {
                foreach ($this->post('fee_province') as $province) {
                    if (!$this->model('product_province')->insert([
                        'product_id' => $product_id,
                        'province_id' => $province
                    ])
                    ) {
                        $this->model('province')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(添加配送城市失败)');
                        return new json(json::PARAMETER_ERROR, '添加配送城市失败');
                    }
                }
            }

            $this->model('bind')->where('pid=?', [$product_id])->delete();
            if (is_array($this->post('bind')) && !empty($this->post('bind'))) {
                foreach ($this->post('bind') as $bind) {
                    if (empty($bind['sort']) || empty($bind['unit']) || empty($bind['num']) || empty($bind['price']) || empty($bind['inprice']) || empty($bind['v1price']) || empty($bind['v2price'])) {
                        continue;
                    }

                    if (!empty($this->model('bind')->where('pid=? and content=? and num=?', [$product_id, $bind['content'], $bind['num']])->find())) {
                        $this->model('product')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(无法捆绑相同数量的商品)');
                        return new json(json::PARAMETER_ERROR, '无法捆绑相同数量的商品');
                    }

                    if (!$this->model('bind')->insert([
                        'pid' => $product_id,
                        'content' => $bind['content'],
                        'num' => $bind['num'],
                        'inprice' => $bind['inprice'],
                        'price' => $bind['price'],
                        'v1price' => $bind['v1price'],
                        'v2price' => $bind['v2price'],
                        'unit' => $bind['unit'],
                        'sort' => $bind['sort'],
                    ])
                    ) {
                        $this->model('product')->rollback();
                        $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(商品捆绑添加失败)');
                        return new json(json::PARAMETER_ERROR, '商品捆绑添加失败');
                    }
                }
            }

            //顺便清空购物车中的相关商品
            //$this->model('cart')->where('pid=?',[$product_id])->delete();

            $this->model('product')->commit();
            $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑成功，商品id：' . $product_id, 1);
            return new json(json::OK, NULL, $product_id);
        } else {
            $this->model('product')->rollback();
            $this->model("admin_log")->insertlog($admin, '商品管理，商品编辑(更新失败)');
            return new json(json::PARAMETER_ERROR, '更新失败');
        }
    }

    /**
     * 删除商品
     * @return \application\message\json
     */
    function remove()
    {
        $admin = $this->session->id;
        $id = $this->post('id');
        if ($this->model('product')->where('id=?', [$id])->update([
            'isdelete' => 1,
            'deletetime' => $_SERVER['REQUEST_TIME']
        ])
        ) {
            $this->model("admin_log")->insertlog($admin, '商品管理，删除商品成功，商品id：' . $id, 1);
            return new json(json::OK);
        }
        $this->model("admin_log")->insertlog($admin, '商品管理，删除商品失败（参数错误）');
        return new json(json::PARAMETER_ERROR);
    }

    function restore()
    {
        $admin = $this->session->id;
        $id = $this->post('id');
        if ($this->model('product')->where('id=?', [$id])->update('isdelete', 0)) {
            $this->model("admin_log")->insertlog($admin, '商品管理，回收站商品恢复，商品id：' . $id, 1);
            return new json(json::OK);
        }
        $this->model("admin_log")->insertlog($admin, '商品管理，回收站商品恢复失败（参数错误）');
        return new json(json::PARAMETER_ERROR);
    }

    function import()
    {
        $admin = $this->session->id;
        function emptyProduct($product)
        {
            $result = true;
            foreach ($product as $value) {
                if (!empty($value)) {
                    $result = false;
                    return $result;
                }
            }
            return $result;
        }
        
        $config = config('file');
        //文件类型
        $config->type = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/vnd.ms-office',
            'application/zip',
        ];
        //允许文件的最大值
        $config->size = 1024 * 1024 * 10;
        $file = $this->file->receive($_FILES['file'], $config);
        if (is_file($file)) {
            $phpexcel_root = ROOT . '/extends/PHPExcel';
            include $phpexcel_root . '/PHPExcel/IOFactory.php';

            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
            if ($objReader->canRead($file)) {
                try {
                    //读取excel中的数据
                    $objPHPExcel = $objReader->load($file);
                    $sheet = $objPHPExcel->getSheet(0);
                    $rowNum = $sheet->getHighestRow();
                    $colNum = $sheet->getHighestColumn();
                } catch (\Exception $e) {
                    $this->model("admin_log")->insertlog($admin, '商品管理，导入商品信息失败（无法做为一个excel文件解析）');
                    return new json(json::PARAMETER_ERROR, '无法做为一个excel文件解析');
                }

                $data = [];
                //模拟A-AC
                $Ein = array("A", "B", "C", "D", "E", "F", "G", "H",
                    "I", "J", "K", "L", "M", "N", "O", "P", "Q",
                    "R", "S", "T", "U", "V", "W", "X", "Y", "Z",
                    "AA", "AB", "AC");

                for ($row = 2; $row <= $rowNum; $row++) {//行数是以第2行开始

                    $dataset = [];

                    for ($column = 0; $column < count($Ein); $column++) {//列数是以A列开始
                        //$dataset[] = $sheet->getCell($column.$row)->getValue();
                        $dataset[] = $sheet->getCell($Ein[$column] . $row)->getCalculatedValue();
                    }
                    $data[] = $dataset;
                }

                if (empty($data)) {
                    $this->model("admin_log")->insertlog($admin, '导入商品信息错误（该文档中不包含任何信息）');
                    return new json(json::PARAMETER_ERROR, '该文档中不包含任何信息');
                }

                $productHelper = new \application\helper\product();

                $result = [];

                foreach ($data as $product) {

                    if (emptyProduct($product)) {
                        continue;
                    }

                    if (empty($product)) {
                        continue;
                    }
                    //商品id
                    $product_id = (string)$product[0];

                    if (empty($product_id)) {
                        $product_id = NULL;
                    } else {
                        if (!$this->model("product")->where("id=?", [$product_id])->find()) {
                            $product_id = NULL;
                        }
                    }

                    //商品sku
                    $product_sku = (string)$product[1];

                    //商品条形码
                    $product_barcode = (string)$product[2];

                    //商品进价
                    $product_inprice = (string)$product[3];

                    //商品销售数量
                    $product_selled = (string)$product[4];

                    if (!empty($product[3])) {
                        $bind[0]['inprice'] = (string)$product[3];
                        $bind[0]['num'] = (string)$product[4];
                    }

                    if (!empty($product[5])) {

                        $bind[1]['inprice'] = (string)$product[5];
                        $bind[1]['num'] = (string)$product[6];
                    }
                    if (!empty($product[7])) {
                        $bind[2]['inprice'] = (string)$product[7];
                        $bind[2]['num'] = (string)$product[8];
                    }


                    //商品下架原因
                    $product_down_reason = (string)$product[9];

                    //商品名称
                    $product_name = (string)$product[10];

                    //商品类别
                    $product_outside = (string)$product[11];
                    switch ($product_outside) {
                        case '普通商品':
                            $product_outside = 0;
                            break;
                        case '进口商品':
                            $product_outside = 1;
                            break;
                        case '直供商品':
                            $product_outside = 2;
                            break;
                        case '直邮商品':
                            $product_outside = 3;
                            break;
                        default:
                            $product_outside = 0;
                    }


                    //包税
                    $product_freetax = (string)$product[12];
                    if ($product_freetax == '是') {
                        $product_freetax = 1;
                    } else {
                        $product_freetax = 0;
                    }

                    //排序
                    $product_order = (string)$product[13];

                    //原价
                    $product_oldprice = (string)$product[14];

                    //v0价
                    $product_price = (string)$product[15];

                    //v1价
                    $product_v1price = (string)$product[16];

                    //v2价
                    $product_v2price = (string)$product[17];

                    if (!empty($product[15])) {
                        $bind[0]['price'] = (string)$product[15];
                        $bind[0]['v1price'] = (string)$product[16];
                        $bind[0]['v2price'] = (string)$product[17];
                    }
                    if (!empty($product[18])) {
                        $bind[1]['price'] = (string)$product[18];
                        $bind[1]['v1price'] = (string)$product[19];
                        $bind[1]['v2price'] = (string)$product[20];
                    }
                    if (!empty($product[21])) {
                        $bind[2]['price'] = (string)$product[21];
                        $bind[2]['v1price'] = (string)$product[22];
                        $bind[2]['v2price'] = (string)$product[23];
                    }


                    //库存
                    $product_stock = (string)$product[24];

                    //状态
                    $product_status = (string)$product[25];
                    if ($product_status == '上架') {
                        $product_status = 1;
                    } else {
                        $product_status = 0;
                    }

                    //运费
                    $product_fee = (string)$product[26];

                    //供应商
                    $publish = trim((string)$product[27]);

                    //单位
                    $unit = trim((string)$product[28]);
                    $bind1 = array();

                    if (count($bind) >= 1) {
                        for ($b = 0; $b < count($bind); $b++) {

                            if (!isset($bind[$b]['inprice']) || $bind[$b]['inprice'] == 0) {

                                unset($bind[$b]);
                                continue;
                            }

                            if (!isset($bind[$b]['num'])) {
                                unset($bind[$b]);
                                continue;
                            }

                            if (!isset($bind[$b]['price']) || $bind[$b]['price'] == 0) {
                                unset($bind[$b]);

                                continue;
                            }

                            if (!isset($bind[$b]['v1price']) || $bind[$b]['v1price'] == 0) {
                                unset($bind[$b]);
                                continue;
                            }

                            if (!isset($bind[$b]['v2price']) || $bind[$b]['v2price'] == 0) {
                                unset($bind[$b]);
                                continue;
                            }


                            $bind[$b]['unit'] = $unit;
                            $bind1[] = $bind[$b];
                        }
                    }


                    $countbind = count($bind1) - 1;
                    if ($countbind > 0) {
                        $product_price = $bind1[$countbind]['price'];

                        //v1价
                        $product_v1price = $bind1[$countbind]['v1price'];

                        //v2价
                        $product_v2price = $bind1[$countbind]['v2price'];
                        //售卖数
                        $product_selled = $bind1[$countbind]['num'];
                    }
                    if (!empty($publish)) {
                        $publish = $this->model('publish')->where('name=?', [$publish])->find();
                        if (!empty($publish)) {
                            $publish = $publish['id'];
                        } else {
                            $publish = NULL;
                        }
                    } else {
                        $publish = NULL;
                    }

                    $product = [
                        //'id' => $product_id,
                        'sku' => $product_sku,
                        'barcode' => $product_barcode,
                        'inprice' => $product_inprice,
                        'selled' => $product_selled,
                        'down_reason' => $product_down_reason,
                        'name' => $product_name,
                        'outside' => $product_outside,
                        'freetax' => $product_freetax,
                        'sort' => $product_order,
                        'oldprice' => $product_oldprice,
                        'price' => $product_price,
                        'v1price' => $product_v1price,
                        'v2price' => $product_v2price,
                        'stock' => $product_stock,
                        'status' => $product_status,
                        'fee' => $product_fee,
                        'publish' => $publish
                    ];

                    if (empty($product_id)) {
                        $productData = $productHelper->createProductData($product);

                        $sql = $this->model('product')->insert($productData);
                        $product_id = $this->model("product")->lastInsertId();
                    } else {
                        $product['modifytime'] = $_SERVER['REQUEST_TIME'];
                        $sql = $this->model('product')->where('id=?', [$product_id])->update($product);
                    }

                    //生成导入结果
                    $product['id'] = $product_id;

                    $this->model("bind")->where("pid=?", [$product_id])->delete();
                    if (count($bind1) > 1) {
                        $j = 1;
                        
                        $bind1 = array_reverse($bind1);
                        foreach ($bind1 as &$b) {
                            $b['pid'] = $product_id;
                            $b['sort'] = $j;
                            $b['content'] = '';
                            try {
                                $this->model("bind")->insert($b);

                            } catch (Exception $e) {

                                $this->model("admin_log")->insertlog($admin, '导入商品信息错误（商品' . $b['pid'] . '错误）');
                                return new json(json::PARAMETER_ERROR, '商品' . $b['pid'] . "错误");
                            }
                            $j++;
                        }
                    }
                    
                    if (!$sql) {
                        $product['success'] = false;
                    } else {
                        $product['success'] = true;
                    }
                    $result[] = $product;
                }
                $this->model("admin_log")->insertlog($admin, '导入商品信息成功', 1);
                return new json(json::OK, NULL, $result);
            } else {
                $this->model("admin_log")->insertlog($admin, '导入商品信息失败（上传的文件无法读取）');
                return new json(json::PARAMETER_ERROR, '上传的文件无法读取');
            }
        } else {
            $this->model("admin_log")->insertlog($admin, '导入商品信息失败（文件上传失败,请检查文件类型或者文件大小）');
            return new json(json::PARAMETER_ERROR, '文件上传失败,请检查文件类型或者文件大小');
        }
    }
}
