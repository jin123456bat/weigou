<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;
use system\core\random;

class source extends ajax
{
    function create()
    {
        $admin=$this->session->id;
        $name = $this->post('name');
        $name1 = $name;
        $phone = $this->post('phone');
        $wechat = $this->post('wechat');
        $password = $this->post('password');
        $user = $this->post('user');
        $product = $this->post('product');
        $ctype = $this->post('ctype');


        if (!empty($this->post('type'))) {   //子渠道添加
            $u_source = $this->session->id;
            if (!empty($userp = $this->model('user')->where('telephone=?', [$phone])->find())) {
                if($userp['source']!=$u_source){
                    $this->model("admin_log")->insertlog($admin, '创建渠道失败（手机号必须是当前渠道下人员的手机号）' );
                    return new json(json::PARAMETER_ERROR, '手机号必须是当前渠道下人员的手机号');
                }
            }else{
                $this->model("admin_log")->insertlog($admin, '创建渠道失败（手机号必须是当前渠道下人员的手机号）');
                return new json(json::PARAMETER_ERROR, '手机号必须是当前渠道下人员的手机号');
            }
        } else {
            $u_source = NULL;
        }

        if (empty($name) || empty($phone) || empty($wechat)) {
            $this->model("admin_log")->insertlog($admin, '创建渠道失败（请填写完完整）');
            return new json(json::PARAMETER_ERROR, '请填写完完整');
        }
        if (!empty($this->model('source')->where('name=? and isdelete=?', [$name, 0])->find())) {
            $this->model("admin_log")->insertlog($admin, '创建渠道失败（该渠道商已经存在）');
            return new json(json::PARAMETER_ERROR, '该渠道商已经存在');
        }

        if (!empty($userp = $this->model('user')->where('telephone=?', [$phone])->find())) {
            $uid = $userp['id'];
        } else {

            $uid = NULL;


            $invit = random::word(6);//邀请码
            $userdata = [
                'id' => NULL,
                'name' => $name,
                'telephone' => $phone,
                'password' => md5($password . $invit),
                'salt' => $invit,
                'invit' => $invit,
                'regtime' => $_SERVER['REQUEST_TIME'],
                'money' => '',
                'gravatar' => NULL,
                'vip' => 2,
                'master' => 1,
                'o_master' => NULL,
                'wx_name' => '',
                'wx_openid_web' => NULL,
                'wx_openid_ios' => NULL,
                'wx_openid_android' => NULL,
                'oid' => NULL,
                'qq_openid_web' => NULL,
                'qq_openid_ios' => NULL,
                'qq_openid_android' => NULL,
                'qq_time_web' => 0,
                'qq_time_ios' => 0,
                'qq_time_android' => 0,
                'qq_name' => '',
                'weibo_access_token' => NULL,
                'weibo_uid' => NULL,
                'weibo_starttime' => NULL,
                'weibo_endtime' => NULL,
                'weibo_scope' => NULL,
                'pay_password' => NULL,
                'pay_salt' => NULL,
                'description' => '',
                'score' => 0,
                'invittime' => $_SERVER['REQUEST_TIME'],
                'source' => NULL,
                'wechat_no' => $wechat,
                'close' => 0
            ];
            // $master = $name = $this->post('master');
            if ($ctype == 1) {
                $userdata['vip'] = 0;
                $userdata['master'] = 0;
            } else {
                if ($u_source == null) {
                    $userdata['vip'] = 2;
                    $userdata['master'] = 1;
                } else {
                    $userdata['vip'] = 0;
                    $userdata['master'] = 0;
                }

            }
            if ($this->model('user')->insert($userdata)) {
                $uid = $this->model('user')->lastInsertId();
            } else {
                $this->model("admin_log")->insertlog($admin, '创建渠道失败（创建不成功）');
                return new json(json::PARAMETER_ERROR, '创建不成功');
            }


        }
        $school=$this->model('source')->where('id=?',[$u_source])->find();
        $school= $school['school'];
        $array = [
            'name' => $name1,
            'password' => md5($password),
            'user' => $user,
            'product' => $product,
            'isdelete' => 0,
            'deletetime' => 0,
            'createtime' => $_SERVER['REQUEST_TIME'],
            'uid' => $uid,
            'u_source' => $u_source,
            'type' => $ctype,
            'school' => $school,
        ];
        if ($this->model('source')->insert($array)) {
            $array['id'] = $this->model('source')->lastInsertId();
            $this->model("admin_log")->insertlog($admin, '创建渠道成功,渠道id：'.$array['id'],1);
            return new json(json::OK, NULL, $array);
        }
        $this->model("admin_log")->insertlog($admin, '创建渠道失败（请求参数错误）');
        return new json(json::PARAMETER_ERROR);
    }

    function create2()
    {
        $admin=$this->session->id;
        $name = $this->post('name');
        $password = $this->post('password', '', 'md5');
        $user = $this->post('user');
        $school = $this->post('school');
        $issour = $this->post('issour');
        $product = $this->post('product');
        $usertelephone = $this->post('usertelephone');


        if (!empty($this->model('source')->where('name=? and isdelete=?', [$name, 0])->find())) {
            $this->model("admin_log")->insertlog($admin, '创建普通渠道失败（该渠道商已经存在）');
            return new json(json::PARAMETER_ERROR, '该渠道商已经存在');
        }

        if (!empty($usertelephone)) {
            $uid = $this->model('user')->where('telephone=?', [$usertelephone])->find();
            if (empty($uid)) {
                $this->model("admin_log")->insertlog($admin, '创建普通渠道失败（手机号码还没注册）');
                return new json(json::PARAMETER_ERROR, '手机号码还没注册');
            }
        } else {
            $this->model("admin_log")->insertlog($admin, '创建普通渠道失败（手机号码还没注册）');
            return new json(json::PARAMETER_ERROR, '手机号码还没注册');
        }

        //判断用户是否是导师
        if ($issour == 1) {
            //判断原来用户是否是导师 不是 则变成导师 帮顶上级导师
            if ($uid['master'] != 1) {
                if (empty($uid['oid'])) {
                    $this->model("user")->where("id=?", [$uid['id']])->update([
                        'oid' => '269',
                        'o_master' => '269',
                        'vip' => '2',
                        'master' => '1']);
                } else {
                    $this->model("user")->where("id=?", [$uid['id']])->update([
                        'vip' => '2',
                        'master' => '1']);
                }
            }
        }
        $array = [
            'name' => $name,
            'password' => $password,
            'user' => $user,
            'product' => $product,
            'isdelete' => 0,
            'deletetime' => 0,
            'createtime' => $_SERVER['REQUEST_TIME'],
            'uid' => $uid['id'],
            'u_source' => NULL,
            'type' => 1,
            'school' => $school,
        ];
        if ($this->model('source')->insert($array)) {
            $array['id'] = $this->model('source')->lastInsertId();
            $this->model("admin_log")->insertlog($admin, '创建普通渠道成功，渠道id：'.$array['id'],1);
            return new json(json::OK, NULL, $array);

        }
        $this->model("admin_log")->insertlog($admin, '创建普通渠道失败（请求参数错误）');
        return new json(json::PARAMETER_ERROR);
    }

    function remove()
    {
        $admin=$this->session->id;
        $id = $this->post('id');
        if (!empty($id)) {
            if ($this->model('source')->where('id=?', [$id])->limit(1)->update([
                'isdelete' => 1,
                'deletetime' => $_SERVER['REQUEST_TIME']
            ])
            ) {
                $this->model("admin_log")->insertlog($admin, '删除渠道成功，渠道id：' . $id, 1);
                return new json(json::OK);
            }
        }
        $this->model("admin_log")->insertlog($admin, '删除渠道失败（请求参数错误）' );
        return new json(json::PARAMETER_ERROR);
    }

    function changepwd()
    {
        $admin=$this->session->id;
        $id = $this->post('id');
        if (!empty($id)) {
            $password = $this->post('password');
            if (empty($password)) {
                $this->model("admin_log")->insertlog($admin, '渠道修改密码失败（密码不能为空）');
                return new json(json::PARAMETER_ERROR, '密码不能为空');
            }
            $password = md5($password);
            if ($this->model('source')->where('id=?', [$id])->limit(1)->update('password', $password)) {
                $this->model("admin_log")->insertlog($admin, '渠道修改密码成功，id：'.$id,1);
                return new json(json::OK);
            } else {
                $this->model("admin_log")->insertlog($admin, '渠道修改密码失败（密码更改失败）');
                return new json(json::PARAMETER_ERROR, '密码更改失败');
            }
        }
        $this->model("admin_log")->insertlog($admin, '渠道修改密码失败（id不能为空）');
        return new json(json::PARAMETER_ERROR, 'id不能为空');
    }

    function power()
    {
        $admin=$this->session->id;
        $id = $this->post('id');
        $name = $this->post('name');
        if (in_array($name, ['user', 'product'])) {
            $source = $this->model('source')->where('id=?', [$id])->find();
            $this->model('source')->where('id=?', [$id])->limit(1)->update($name, $source[$name] == 1 ? 0 : 1);
            $this->model("admin_log")->insertlog($admin, '渠道修改权限成功，渠道id：'.$id,1);
            return new json(json::OK);
        }
    }

    function login()
    {
        $name = $this->post('name');
        $password = $this->post('password');
        $source = $this->model('source')->where('name = ? and isdelete=?', [$name, 0])->find();
        if (md5($password) == $source['password']) {
            $this->session->role = 'source';
            $this->session->id = $source['id'];
            $this->session->uid = $source['uid'];//绑定手机号id
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR, '密码错误');
    }

    function dashboard()
    {
        //默认的情况下为7天之内的
        $endtime = strtotime(date('Y-m-d')) + 3600 * 24;
        $starttime = $endtime - 7 * 3600 * 24;
        $start = $this->post('start');
        $end = $this->post('end');
        if (!empty($start)) {
            $starttime = strtotime($start);
        }
        if (!empty($end)) {
            $end = strtotime($end) + 3600 * 24;
        }

        if ($this->session->role == 'source') {
            $source_id = $this->session->id;
        }
        if (empty($source_id))
            return new json(json::NOT_LOGIN);

        $data = [];

        for ($i = $starttime; $i < $endtime; $i += 3600 * 24) {
            if ($this->get('type') == 'payed') {
                $total = $this->model('order')
                    ->table('user', 'left join', 'user.id=order.uid')
                    ->where('order.createtime > ? and order.createtime < ?', [$i, $i + 3600 * 24])
                    ->where('(user.source=? or user.o_master=?) and order.pay_status=?', [$source_id, $this->session->uid, 1])
                    ->find('sum(order.orderamount)');
                $payed = $this->model('order')
                    ->table('user', 'left join', 'user.id=order.uid')
                    ->where('order.createtime > ? and order.createtime < ? and order.pay_status = ?', [$i, $i + 3600 * 24, 1])
                    ->where('(user.source=? or user.o_master=?) and order.pay_status=?', [$source_id, $this->session->uid, 1])
                    ->find('sum(order.orderamount)');
                $data[] = [
                    'date' => date('Y-m-d', $i),
                    'total' => isset($total['sum(order.orderamount)']) && !empty($total['sum(order.orderamount)']) ? $total['sum(order.orderamount)'] : 0,
                    'payed' => isset($payed['sum(order.orderamount)']) && !empty($payed['sum(order.orderamount)']) ? $payed['sum(order.orderamount)'] : 0,
                ];
            }
            if ($this->get('type') == 'order') {
                $total = $this->model('order')
                    ->table('user', 'left join', 'user.id=order.uid')
                    ->where('order.createtime > ? and order.createtime < ?', [$i, $i + 3600 * 24])
                    ->where('(user.source=? or user.o_master=?) and order.pay_status=?', [$source_id, $this->session->uid, 1])
                    ->find('count(*)');
                $payed = $this->model('order')
                    ->table('user', 'left join', 'user.id=order.uid')
                    ->where('order.createtime > ? and order.createtime < ? and order.pay_status = ?', [$i, $i + 3600 * 24, 1])
                    ->where('(user.source=? or user.o_master=?) and order.pay_status=?', [$source_id, $this->session->uid, 1])
                    ->find('count(*)');
                $data[] = [
                    'date' => date('Y-m-d', $i),
                    'total' => $total['count(*)'],
                    'payed' => $payed['count(*)'],
                ];
            } else if ($this->get('type') == 'user') {
                $user = $this->model('user')->where('user.source=? or user.o_master=?', [$source_id, $this->session->uid])
                    ->where('regtime > ? and regtime < ?', [$i, $i + 3600 * 24])
                    ->find('count(*)');

                $click = $this->model('source_log')
                    ->where('time > ? and time < ?', [$i, $i + 3600 * 24])
                    ->where('source = ?', [$source_id])
                    ->find('count(*)');

                $result = [
                    'date' => date('Y-m-d', $i),
                    'user' => $user['count(*)'],
                    'click' => $click['count(*)'],
                ];

                $today = strtotime(date('Y-m-d'));
                if ($i == $today) {
                    $result['dashLengthColumn'] = 5;
                    $result['alpha'] = 0.2;
                }

                $data[] = $result;
            }


        }
        return new json($data);
    }

    /*
     * 普通渠道
     */
    function dashboard2()
    {
        //默认的情况下为7天之内的
        $endtime = strtotime(date('Y-m-d')) + 3600 * 24;
        $starttime = $endtime - 7 * 3600 * 24;
        $start = $this->post('start');
        $end = $this->post('end');
        if (!empty($start)) {
            $starttime = strtotime($start);
        }
        if (!empty($end)) {
            $end = strtotime($end) + 3600 * 24;
        }

        if ($this->session->role == 'source') {
            $source_id = $this->session->id;
        }
        if (empty($source_id))
            return new json(json::NOT_LOGIN);

        $data = [];

        for ($i = $starttime; $i < $endtime; $i += 3600 * 24) {
            if ($this->get('type') == 'payed') {
                $total = $this->model('order')
                    ->table('user', 'left join', 'user.id=order.uid')
                    ->where('order.createtime > ? and order.createtime < ?', [$i, $i + 3600 * 24])
                    ->where('user.source=? or user.oid=?', [$source_id, $this->session->uid])
                    ->find('sum(order.orderamount)');
                $payed = $this->model('order')
                    ->table('user', 'left join', 'user.id=order.uid')
                    ->where('order.createtime > ? and order.createtime < ? and order.pay_status = ?', [$i, $i + 3600 * 24, 1])
                    ->where('user.source=? or user.oid=?', [$source_id, $this->session->uid])
                    ->find('sum(order.orderamount)');
                $data[] = [
                    'date' => date('Y-m-d', $i),
                    'total' => isset($total['sum(order.orderamount)']) && !empty($total['sum(order.orderamount)']) ? $total['sum(order.orderamount)'] : 0,
                    'payed' => isset($payed['sum(order.orderamount)']) && !empty($payed['sum(order.orderamount)']) ? $payed['sum(order.orderamount)'] : 0,
                ];
            }
            if ($this->get('type') == 'order') {
                $total = $this->model('order')
                    ->table('user', 'left join', 'user.id=order.uid')
                    ->where('order.createtime > ? and order.createtime < ?', [$i, $i + 3600 * 24])
                    ->where('user.source=? or user.oid=?', [$source_id, $this->session->uid])
                    ->find('count(*)');
                $payed = $this->model('order')
                    ->table('user', 'left join', 'user.id=order.uid')
                    ->where('order.createtime > ? and order.createtime < ? and order.pay_status = ?', [$i, $i + 3600 * 24, 1])
                    ->where('user.source=? or user.oid=?', [$source_id, $this->session->uid])
                    ->find('count(*)');
                $data[] = [
                    'date' => date('Y-m-d', $i),
                    'total' => $total['count(*)'],
                    'payed' => $payed['count(*)'],
                ];
            } else if ($this->get('type') == 'user') {
                $user = $this->model('user')->where('user.source=? or user.oid=?', [$source_id, $this->session->uid])
                    ->where('regtime > ? and regtime < ?', [$i, $i + 3600 * 24])
                    ->find('count(*)');

                $click = $this->model('source_log')
                    ->where('time > ? and time < ?', [$i, $i + 3600 * 24])
                    ->where('source = ?', [$source_id])
                    ->find('count(*)');

                $result = [
                    'date' => date('Y-m-d', $i),
                    'user' => $user['count(*)'],
                    'click' => $click['count(*)'],
                ];

                $today = strtotime(date('Y-m-d'));
                if ($i == $today) {
                    $result['dashLengthColumn'] = 5;
                    $result['alpha'] = 0.2;
                }

                $data[] = $result;
            }


        }
        return new json($data);
    }
}