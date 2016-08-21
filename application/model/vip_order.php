<?php
namespace application\model;

use system\core\model;

class vip_orderModel extends model
{
    function __construct($table)
    {
        parent::__construct($table);
    }

    function datatables($post)
    {
        $this->table('user', 'left join', 'user.id=vip_order.uid');

        $parameter = [];
        foreach ($post['columns'] as $index => $columns) {
            if (!empty($columns['name'])) {
                $parameter[] = $columns['name'] . (empty($columns['data']) ? '' : (' as ' . $columns['data']));
                foreach ($post['order'] as $order) {
                    if ($order['column'] == $index) {
                        $this->orderby($columns['name'], $order['dir']);
                    }
                }
            }
        }
        if (isset($post['action']) && $post['action'] === 'filter') {
            if (!empty($post['orderno'])) {
                $this->where('vip_order.orderno like ?', ['%' . $post['orderno'] . '%']);
            }
            if (!empty($post['uid'])) {
                $this->where('vip_order.uid=?', [$post['uid']]);
            }
            if (!empty($post['createtime_from'])) {
                $this->where('vip_order.createtime >= ?', [strtotime($post['createtime_from'])]);
            }
            if (!empty($post['createtime_to'])) {
                $this->where('vip_order.createtime <= ?', [strtotime($post['createtime_to'])]);
            }
            if ($post['pay_status'] != '') {
                if ($post['pay_status'] == 1) {
                    $this->where('vip_order.paytime != 0');
                } else if ($post['pay_status'] == 0) {
                    $this->where('vip_order.paytime = 0');
                }
            }
            if (!empty($post['vip'])) {
                switch ($post['vip']) {
                    case '0-1':
                        $this->where('vip_order.vip_from = ? and vip_order.vip_to=?', [0, 1]);
                        break;
                    case '0-2':
                        $this->where('vip_order.vip_from = ? and vip_order.vip_to=?', [0, 2]);
                        break;
                    case '1-2':
                        $this->where('vip_order.vip_from = ? and vip_order.vip_to=?', [1, 2]);
                        break;
                }
            }
        }
        return $this->select($parameter);
    }

    function vipdatatables($post, $session_id)
    {
        $uid = $this->model("source")->where("id=?", [$session_id])->find(['uid']);
        $uid = $uid['uid'];
        $this->table('user', 'left join', 'user.id=vip_order.uid');
<<<<<<< HEAD
        $this->where('user.oid in (select id from user where oid=?) or user.oid=? or user.id=?', [$uid,$uid,$uid]);
=======
        
        $this->where('user.oid in (select id from user where oid=?) or user.oid=? or user.id=?', [$uid,$uid,$uid]);
        /* 
         * $this->where('user.oid =?', [$uid], 'or');
        	$this->where('user.id =?', [$uid], 'or');
         */
>>>>>>> d254762cfb08bc4773ca4f64fdd1578328ed185d
        $parameter = [];
        foreach ($post['columns'] as $index => $columns) {
            if (!empty($columns['name'])) {
                $parameter[] = $columns['name'] . (empty($columns['data']) ? '' : (' as ' . $columns['data']));
                foreach ($post['order'] as $order) {
                    if ($order['column'] == $index) {
                        $this->orderby($columns['name'], $order['dir']);
                    }
                }
            }
        }
        if (isset($post['action']) && $post['action'] === 'filter') {
            if (!empty($post['orderno'])) {
                $this->where('vip_order.orderno like ?', ['%' . $post['orderno'] . '%']);
            }
            if (!empty($post['uid'])) {
                $this->where('vip_order.uid=?', [$post['uid']]);
            }
            if (!empty($post['createtime_from'])) {
                $this->where('vip_order.createtime >= ?', [strtotime($post['createtime_from'])]);
            }
            if (!empty($post['createtime_to'])) {
                $this->where('vip_order.createtime <= ?', [strtotime($post['createtime_to'])]);
            }
            if ($post['pay_status'] != '') {
                if ($post['pay_status'] == 1) {
                    $this->where('vip_order.paytime != 0');
                } else if ($post['pay_status'] == 0) {
                    $this->where('vip_order.paytime = 0');
                }
            }
            if (!empty($post['vip'])) {
                switch ($post['vip']) {
                    case '0-1':
                        $this->where('vip_order.vip_from = ? and vip_order.vip_to=?', [0, 1]);
                        break;
                    case '0-2':
                        $this->where('vip_order.vip_from = ? and vip_order.vip_to=?', [0, 2]);
                        break;
                    case '1-2':
                        $this->where('vip_order.vip_from = ? and vip_order.vip_to=?', [1, 2]);
                        break;
                }
            }
        }
        return $this->select($parameter);
    }

    function count()
    {
        $result = $this->find('count(*)');
        return isset($result['count(*)']) && !empty($result['count(*)']) ? $result['count(*)'] : 0;
    }

    function vipcount($sessionid)
    {
        $result = $this->find('count(*)');
        return isset($result['count(*)']) && !empty($result['count(*)']) ? $result['count(*)'] : 0;
    }
}
