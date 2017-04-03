<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;
use system\core\random;

/**
 * @author jin12
 *
 */
class admin extends ajax
{
    function login()
    {
        $username = $this->post('username');
        $password = $this->post('password');
        $adminHelper = new \application\helper\admin();
        if ($admin = $adminHelper->auth($username, $password)) {
            $adminHelper->saveAdminSession($admin);
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR, '用户名或密码错误');
    }

    /**
     * 管理员修改自己的登录密码
     * @return \application\message\json
     */
    function changeMyPwd()
    {
        $adminHelper = new \application\helper\admin();
        $aid = $adminHelper->getAdminId();
        if (empty($aid)) {
            return new json(json::NOT_LOGIN);
        }

        $old_password = $this->post('old_password');
        $new_password = $this->post('new_password');

        $admin = $this->model('admin')->where('id=?', [$aid])->find();
        if ($admin['password'] == $adminHelper->encrypt($old_password, $admin['salt'])) {
            $salt = random::word(6);
            $new_password = $adminHelper->encrypt($new_password, $salt);
            if ($this->model('admin')->where('id=?', [$aid])->limit(1)->update([
                'password' => $new_password,
                'salt' => $salt
            ])
            ) {
                $this->model("admin_log")->insertlog($aid, '管理员修改自己的密码', 1);
                return new json(json::OK);
            }
        } else {
            return new json(json::PARAMETER_ERROR, '旧密码错误');
        }
    }


    function changePassword()
    {
        $adminHelper = new \application\helper\admin();
        $aid = $adminHelper->getAdminId();
        if (empty($aid)) {
            return new json(json::NOT_LOGIN);
        }
        $id = $this->post('id');
        $password = $this->post('password');
        if (!empty($password)) {
            if (strlen($password) <= 6) {
                return new json(json::PARAMETER_ERROR, '密码长度太短');
            }
            $salt = random::word(6);
            $password = $adminHelper->encrypt($password, $salt);
            if ($this->model('admin')->where('id=?', [$id])->limit(1)->update([
                'password' => $password,
                'salt' => $salt,
            ])
            ) {
                $this->model("admin_log")->insertlog($aid, '管理员更改密码成功，用户id：' . $id, 1);
                return new json(json::OK);
            }
            return new json(json::PARAMETER_ERROR);
        } else {
            return new json(json::OK);
        }
    }

    /**
     * 删除管理员用户
     * @return \application\message\json
     */
    function remove()
    {
        $adminHelper = new \application\helper\admin();
        $aid = $adminHelper->getAdminId();
        if (empty($aid)) {
            return new json(json::NOT_LOGIN);
        }

        $id = $this->post('id');
        if ($this->model('admin')->where('id=?', [$id])->delete()) {
            $this->model("admin_log")->insertlog($aid, '管理员删除失败，用户id：' . $id, 1);
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR);
    }

    /**
     * 更改管理员角色
     */
    function role()
    {
        $admin = $this->session->id;
        $id = $this->post('id');
        $role = $this->post('role');
        $this->model('admin')->where('id=?', [$id])->limit(1)->update('role', $role);
        $this->model("admin_log")->insertlog($admin, '管理员更改用户组成功,用户id：' . $id . ',role：' . $role, 1);

        return new json(json::OK);
    }

    /**
     * 添加管理员账户
     */
    function create()
    {
        $username = $this->post('username');
        $password = $this->post('password');
        $role = $this->post('role', '', 'intval');
        if (strlen($password) < 6)
            return new json(json::PARAMETER_ERROR, '密码长度太短');

        if (!empty($this->model('admin')->where('username=?', [$username])->find()))
            return new json(json::PARAMETER_ERROR, '用户名已存在');

        if (empty($role))
            return new json(json::PARAMETER_ERROR, '请选择权限组');

        $adminHelper = new \application\helper\admin();
        $admin = $adminHelper->createAdminData($username, $password, $role);
        if ($this->model('admin')->insert($admin)) {
            $admin['id'] = $this->model('admin')->lastInsertId();
            return new json(json::OK, NULL, $admin);
        }
        return new json(json::PARAMETER_ERROR, '用户名已经存在');
    }


    function  sendsms()
    {

        $body = array();
        $content = $this->post('content');

        $code = $this->model('sendsms')->orderby('id', 'desc')->find(array('id'));
        $code = $code['id'] + 1;


        $uid = $this->model('system')->get('uid', 'sms');
        $key = $this->model('system')->get('key', 'sms');
        $sign = $this->model('system')->get('sign', 'sms');
        $template = $content . ' 退订回复TD';

        $sms = new sms($uid, $key, $sign);
        $j = 0;
        do {
            $ucount = $this->model("user")->where("send!=" . $code)->find(['count(1)']);
            $ucount = $ucount['count(1)'];

            $j = ceil($ucount / 100);
            $array = array();
            for ($i = 0; $i <= $j; $i++) {


                $user = $this->model("user")->where("send!=" . $code)->limit($i * 100, 100)->select(['id', 'telephone']);
                $array[$i] = $user;
                $uw = '';
                foreach ($user as $u) {
                    $uw[] = $u['telephone'];
                }

                if (!is_array($uw)) {
                    continue;
                }
                $uw = implode(',', $uw);


                //循环发送
                $num = 1;
                $num = $sms->send($uw, $template);

                if ($num > 0) {
                    foreach ($user as $u) {
                        $this->model("user")->where("telephone=?", [$u['telephone']])->update(["send" => $code]);
                        $body[]['phone'] = $u['telephone'];
                    }
                    unset($user);


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
        } while ($j > 0);


        $this->model('sendsms')->insert(array(
            "content" => $content,
            "user" => 0));
        return new json(json::OK, NULL, $body);
    }
}