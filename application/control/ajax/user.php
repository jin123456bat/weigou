<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;
use system\core\random;
use application\helper\sms;
use application\helper\idcard;

class user extends ajax
{
    function wechat_set_telephone3()
    {
        if ($this->session->login_type == 'wechat' && isWechat()) {
            $telephone = $this->post('telephone', NULL, 'telephone');
            $code = $this->post('code');
            $password = $this->post('password');
            if (empty($telephone))
                return new json(json::PARAMETER_ERROR, '请输入正确手机号码');
            if (empty($code))
                return new json(json::PARAMETER_ERROR, '请输入验证码');
            if (empty($password))
                return new json(json::PARAMETER_ERROR, '请输入密码');

            if ($this->model('smslog')->check($telephone, $code)) {
                $login_info = $this->session->login_info;

                $userHelper = new \application\helper\user();
                $user = $this->model('user')->where('telephone=?', [$telephone])->find();
                if (empty($user)) {
                    $user = $this->model('user')->where('wx_openid_web=?', [$login_info['wx_openid']])->find();
                    if (empty($user)) {
                        $user = $userHelper->createUserWithTelephone($telephone, $password);
                        if (!empty($login_info) && is_array($login_info)) {
                            $user = array_merge($user, $login_info);
                        }

                        if ($this->model('user')->insert($user)) {
                            $user['id'] = $this->model('user')->lastInsertId();
                            $userHelper->saveUserSession($user);
                            return new json(json::OK);
                        }
                    } else {
                        $salt = random::word(6);
                        $data = [
                            'password' => $userHelper->encrypt($password, $salt),
                            'salt' => $salt,
                            'telephone' => $telephone
                        ];
                        if ($this->model('user')->where('wx_openid_web=?', [$login_info['wx_openid']])->update($data)) {
                            if ($user['close'] == 1) {
                                return new json(json::PARAMETER_ERROR, '账号已封');
                            }
                            $userHelper->saveUserSession($user);
                            return new json(json::OK);
                        }
                    }
                }
            }
            return new json(json::PARAMETER_ERROR, '验证码已失效');
        }
        return new json(json::PARAMETER_ERROR, '请使用微信登陆');
    }

    function wechat_set_telephone2()
    {
        if ($this->session->login_type == 'wechat' && isWechat()) {
            $telephone = $this->post('telephone', NULL, 'telephone');
            $code = $this->post('code');
            if (empty($telephone))
                return new json(json::PARAMETER_ERROR, '请输入正确手机号码');

            if (empty($code))
                return new json(json::PARAMETER_ERROR, '请输入验证码');

            if ($this->model('smslog')->check($telephone, $code)) {
                $userHelper = new \application\helper\user();
                $user = $this->model('user')->where('telephone=?', [$telephone])->limit(1)->find();
                if (empty($user)) {
                    return new json(302, NULL, 3);
                } else {
                    $data = $this->session->login_info;
                    if (!empty($data) && is_array($data)) {
                        $this->model('user')->where('telephone=?', [$telephone])->limit(1)->update($data);
                    }
                    if ($user['close'] == 1) {
                        return new json(json::PARAMETER_ERROR, '账号已封');
                    }
                    $userHelper->saveUserSession($user);
                    return new json(json::OK);
                }
            }
            return new json(json::PARAMETER_ERROR, '验证码错误');
        }
        return new json(json::PARAMETER_ERROR, '请使用微信登陆');
    }


    function weibo_set_telephone3()
    {
        if ($this->session->login_type == 'weibo') {
            $telephone = $this->post('telephone', NULL, 'telephone');
            $code = $this->post('code');
            $password = $this->post('password');
            if (empty($telephone))
                return new json(json::PARAMETER_ERROR, '请输入正确手机号码');
            if (empty($code))
                return new json(json::PARAMETER_ERROR, '请输入验证码');
            if (empty($password))
                return new json(json::PARAMETER_ERROR, '请输入密码');

            if ($this->model('smslog')->check($telephone, $code)) {
                $login_info = $this->session->login_info;

                $userHelper = new \application\helper\user();
                $user = $this->model('user')->where('telephone=?', [$telephone])->find();
                if (empty($user)) {
                    $user = $this->model('user')->where('weibo_uid=?', [$login_info['weibo_uid']])->find();
                    if (empty($user)) {
                        $user = $userHelper->createUserWithTelephone($telephone, $password);

                        if (!empty($login_info) && is_array($login_info)) {
                            $user = array_merge($user, $login_info);
                        }

                        if ($this->model('user')->insert($user)) {
                            $user['id'] = $this->model('user')->lastInsertId();
                            $userHelper->saveUserSession($user);
                            return new json(json::OK);
                        }
                    } else {
                        $salt = random::word(6);
                        $data = [
                            'password' => $userHelper->encrypt($password, $salt),
                            'salt' => $salt,
                            'telephone' => $telephone
                        ];
                        if ($this->model('user')->where('weibo_uid=?', [$login_info['weibo_uid']])->update($data)) {
                            if ($user['close'] == 1) {
                                return new json(json::PARAMETER_ERROR, '账号已封');
                            }
                            $userHelper->saveUserSession($user);
                            return new json(json::OK);
                        }
                    }
                }
            }
            return new json(json::PARAMETER_ERROR, '验证码已失效');
        }
        return new json(json::PARAMETER_ERROR, '请使用QQ账号登陆');
    }

    function weibo_set_telephone2()
    {
        if ($this->session->login_type == 'weibo') {
            $telephone = $this->post('telephone', NULL, 'telephone');
            $code = $this->post('code');
            if (empty($telephone))
                return new json(json::PARAMETER_ERROR, '请输入正确手机号码');

            if (empty($code))
                return new json(json::PARAMETER_ERROR, '请输入验证码');

            if ($this->model('smslog')->check($telephone, $code)) {
                $userHelper = new \application\helper\user();
                $user = $this->model('user')->where('telephone=?', [$telephone])->limit(1)->find();
                if (empty($user)) {
                    return new json(302, NULL, 3);
                } else {
                    $data = $this->session->login_info;
                    if (!empty($data) && is_array($data)) {
                        $this->model('user')->where('telephone=?', [$telephone])->limit(1)->update($data);
                    }
                    if ($user['close'] == 1) {
                        return new json(json::PARAMETER_ERROR, '账号已封');
                    }
                    $userHelper->saveUserSession($user);
                    return new json(json::OK);
                }
            }
            return new json(json::PARAMETER_ERROR, '验证码错误');
        }
        return new json(json::PARAMETER_ERROR, '请使用QQ账号登陆');
    }


    function qq_set_telephone3()
    {
        if ($this->session->login_type == 'qq') {
            $telephone = $this->post('telephone', NULL, 'telephone');
            $code = $this->post('code');
            $password = $this->post('password');
            if (empty($telephone))
                return new json(json::PARAMETER_ERROR, '请输入正确手机号码');
            if (empty($code))
                return new json(json::PARAMETER_ERROR, '请输入验证码');
            if (empty($password))
                return new json(json::PARAMETER_ERROR, '请输入密码');

            if ($this->model('smslog')->check($telephone, $code)) {
                $login_info = $this->session->login_info;
                $user_info = $this->session->user_info;

                $userHelper = new \application\helper\user();
                $user = $this->model('user')->where('telephone=?', [$telephone])->find();
                if (empty($user)) {
                    $user = $this->model('user')->where('qq_openid_web=?', [$login_info['openid']])->find();
                    if (empty($user)) {
                        $user = $userHelper->createUserWithTelephone($telephone, $password);
                        $user['qq_time_web'] = $login_info['time'];
                        $user['qq_openid_web'] = $login_info['openid'];

                        if (!empty($user_info) && is_array($user_info)) {
                            $user = array_merge($user, $user_info);
                        }

                        if ($this->model('user')->insert($user)) {
                            $user['id'] = $this->model('user')->lastInsertId();

                            $userHelper->saveUserSession($user);
                            return new json(json::OK);
                        }
                    } else {
                        $salt = random::word(6);
                        $data = [
                            'password' => $userHelper->encrypt($password, $salt),
                            'salt' => $salt,
                            'telephone' => $telephone
                        ];
                        if ($this->model('user')->where('qq_openid_web=?', [$login_info['openid']])->update($data)) {
                            if ($user['close'] == 1) {
                                return new json(json::PARAMETER_ERROR, '账号已封');
                            }
                            $userHelper->saveUserSession($user);
                            return new json(json::OK);
                        }
                    }
                }
            }
            return new json(json::PARAMETER_ERROR, '验证码已失效');
        }
        return new json(json::PARAMETER_ERROR, '请使用QQ账号登陆');
    }

    function qq_set_telephone2()
    {
        if ($this->session->login_type == 'qq') {
            $telephone = $this->post('telephone', NULL, 'telephone');
            $code = $this->post('code');
            if (empty($telephone))
                return new json(json::PARAMETER_ERROR, '请输入正确手机号码');

            if (empty($code))
                return new json(json::PARAMETER_ERROR, '请输入验证码');

            if ($this->model('smslog')->check($telephone, $code)) {
                $userHelper = new \application\helper\user();
                $user = $this->model('user')->where('telephone=?', [$telephone])->limit(1)->find();
                if (empty($user)) {
                    return new json(302, NULL, 3);
                } else {
                    $qq_info = $this->session->login_info;
                    $user_info = $this->session->user_info;
                    $data = [
                        'qq_openid_web' => $qq_info['openid'],
                        'qq_time_web' => $qq_info['time'],
                    ];
                    if (!empty($user_info) && is_array($user_info)) {
                        $data = array_merge($data, $user_info);
                    }
                    $this->model('user')->where('telephone=?', [$telephone])->limit(1)->update($data);
                    if ($user['close'] == 1) {
                        return new json(json::PARAMETER_ERROR, '账号已封');
                    }
                    $userHelper->saveUserSession($user);
                    return new json(json::OK);
                }
            }
            return new json(json::PARAMETER_ERROR, '验证码错误');
        }
        return new json(json::PARAMETER_ERROR, '请使用QQ账号登陆');
    }

    /**
     * 修改登陆密码，已经登陆了的
     * @return \application\message\json
     */
    function setpassword()
    {
        $telephone = $this->post('telephone', NULL, 'telephone');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');

        $password = $this->post('password');
        if (strlen($password) < 6)
            return new json(json::PARAMETER_ERROR, '密码太短');

        $smscode = $this->post('smscode', NULL);
        if (empty($smscode))
            return new json(json::PARAMETER_ERROR, '验证码错误');

        if ($this->model('smslog')->check($telephone, $smscode)) {
            $userHelper = new \application\helper\user();
            $salt = random::word(6);
            $password = $userHelper->encrypt($password, $salt);
            if ($this->model('user')->where('telephone=?', [$telephone])->update([
                'password' => $password,
                'salt' => $salt,
            ])
            ) {
                return new json(json::OK);
            }
            return new json(json::PARAMETER_ERROR, '密码更改失败');
        }
        return new json(json::PARAMETER_ERROR, '验证码错误');
    }

    /**
     * 使用手机号和密码注册，附加短信验证码
     * @return \application\message\json
     */
    function register()
    {
        if (!empty($this->_response))
            return $this->_response;


        $sms_code = $this->post('sms_code', NULL);
        if (empty($sms_code))
            return new json(json::PARAMETER_ERROR, '请输入验证码');
        $telephone = $this->post('telephone', NULL, 'telephone');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');
        $password = $this->post('password');
        if (strlen($password) < 6)
            return new json(json::PARAMETER_ERROR, '密码长度太短');

        $name = $this->post('name', '');

        if ($this->model('smslog')->check($telephone, $sms_code)) {
            if ($this->model('user')->telephoneExist($telephone))
                return new json(json::PARAMETER_ERROR, '手机号码已注册');
            $userHelper = new \application\helper\user();
            $user = $userHelper->createUserWithTelephone($telephone, $password);
            $user['name'] = $name;
            if ($this->model('user')->insert($user)) {
                $user['id'] = $this->model('user')->lastInsertId();
                $userHelper->saveUserSession($user);
                $userHelper->protectedUser($user);

                return new json(json::OK);
            }
            return new json(json::PARAMETER_ERROR, '注册失败');
        } else {
            return new json(json::PARAMETER_ERROR, '验证码错误');
        }
    }

    /**
     * 发送短信验证码
     * @return \application\message\json
     */
    function code()
    {
        $telephone = $this->post('telephone', NULL, 'telephone');
        if (empty($telephone)) {
            return new json(json::PARAMETER_ERROR, '手机号码错误');
        }

        $checkTelephone = $this->post('checkTelephone', NULL, 'intval');
        if ($checkTelephone !== NULL) {
            if ($checkTelephone) {
                if (!empty($this->model('user')->where('telephone=?', [$telephone])->find())) {
                    return new json(json::PARAMETER_ERROR, '手机号码已经注册');
                }
            } else {
                if (empty($this->model('user')->where('telephone=?', [$telephone])->find())) {
                    return new json(json::PARAMETER_ERROR, '手机号码尚未注册');
                }
            }
        }

        if ($this->model('smslog')->check($telephone)) {
            $uid = $this->model('system')->get('uid', 'sms');
            $key = $this->model('system')->get('key', 'sms');
            $sign = $this->model('system')->get('sign', 'sms');
            $template = $this->model('system')->get('template', 'sms');

            $sms = new sms($uid, $key, $sign);
            $code = random::number(6);
            $content = sprintf($template, $code);
            $num = $sms->send($telephone, $content);
            if ($num > 0) {
                $this->model('smslog')->create($telephone, $code);
                return new json(json::OK, NULL, $code);
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
            return new json(json::PARAMETER_ERROR, '验证码发送失败');
        } else {
            return new json(json::PARAMETER_ERROR, '验证码发送频率太高，请稍后再试');
        }
    }


    /**
     * 验证手机号和验证码是否匹配
     */
    function checkCode()
    {
        $sms_code = $this->post('sms_code', NULL);
        if (empty($sms_code))
            return new json(json::PARAMETER_ERROR, '验证码错误');

        $telephone = $this->post('telephone', NULL, 'telephone');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号错误');

        if ($this->model('smslog')->check($telephone, $sms_code)) {
            $this->session->auth_telephone = true;
            $this->session->auth_telephone_time = $_SERVER['REQUEST_TIME'];

            $this->session->telephone = $telephone;
            $this->session->sms_code = $sms_code;

            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR, '验证码错误');
    }


    /**
     * 检查用户要注册的手机号码和验证码
     * @return \application\message\json
     */
    function checkTelephoneAndCode()
    {
        $telephone = $this->post('telephone', NULL, 'telephone');
        $code = $this->post('code', NULL);

        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');

        if (empty($code))
            return new json(json::PARAMETER_ERROR, '请输入验证码');

        if (!$this->model('smslog')->check($telephone, $code))
            return new json(json::PARAMETER_ERROR, '验证码错误');

        $user = $this->model('user')->where('telephone=?', [$telephone])->find();
        if (!empty($user))
            return new json(json::PARAMETER_ERROR, '该手机号码已经注册');


        $this->session->telephone = $telephone;
        $this->session->code = $code;

        return new json(json::OK);
    }

    /**
     * 验证用户的支付密码是否正确
     */
    function auth_pay_password()
    {
        $pay_password = $this->post('pay_password');
        if (empty($pay_password))
            return new json(json::PARAMETER_ERROR, '请输入支付密码');

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            return new json(json::NOT_LOGIN);
        }

        $user = $this->model('user')->where('id=?', [$uid])->find();
        if (empty($user))
            return new json(json::PARAMETER_ERROR, '系统错误');


        $error_num = $this->model('auth_paypassword_log')->where('time > ?', [$_SERVER['REQUEST_TIME'] - 12 * 3600])->select('count(*)');
        $error_num = isset($error_num['count(*)']) && !empty($error_num['count(*)']) ? $error_num['count(*)'] : 0;
        if ($error_num >= 5)
            return new json(json::PARAMETER_ERROR, '密码尝试次数过多，请等待12小时后在来尝试');

        if ($userHelper->encrypt($pay_password, $user['pay_salt'], 'sha1') === $user['pay_password']) {
            $this->session->auth_paypassword = true;
            $this->session->auth_paypassword_time = $_SERVER['REQUEST_TIME'];

            $this->model('auth_paypassword_log')->where('uid=?', [$uid])->delete();

            return new json(json::OK, NULL, $error_num);
        }


        $this->model('auth_paypassword_log')->insert([
            'ip' => ip(),
            'paypassword' => $pay_password,
            'uid' => $uid,
            'time' => $_SERVER['REQUEST_TIME'],
        ]);

        return new json(json::PARAMETER_ERROR, '密码错误');
    }

    function name()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);
        $name = $this->post('name', '');
        $this->model('user')->where('id=?', [$uid])->update('name', $name);
        return new json(json::OK);
    }

    /**
     * 设置支付密码
     */
    function pay_password()
    {
        $pay_password = $this->post('pay_password', NULL);
        if (empty($pay_password))
            return new json(json::PARAMETER_ERROR, '支付密码不得为空');

        if (strlen($pay_password) != 6)
            return new json(json::PARAMETER_ERROR, '支付密码必须是6位');

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $user = $this->model('user')->where('id=?', [$uid])->find();
        if (empty($user)) {
            return new json(json::PARAMETER_ERROR, '系统错误');
        } else {
            if (!empty($user['pay_password'])) {
                if ($this->session->auth_paypassword !== true || $_SERVER['REQUEST_TIME'] - $this->session->auth_paypassword_time > 5 * 60) {
                    return new json(json::PARAMETER_ERROR, '请先验证原支付密码');
                }
            }
        }


        $salt = random::word(6);
        $pay_password = $userHelper->encrypt($pay_password, $salt, 'sha1');
        $data = [
            'pay_password' => $pay_password,
            'pay_salt' => $salt
        ];

        if ($this->model('user')->where('id=?', [$uid])->update($data)) {

            $this->model('auth_paypassword_log')->where('uid=?', [$uid])->delete();

            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR, '设置失败');
    }

    /**
     * 设置头像
     * @return \application\message\json
     */
    function setGravatar()
    {
        $file = $this->post('file');

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        if ($this->model('user')->where('id=?', [$uid])->limit(1)->update('gravatar', $file)) {
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR);
    }

    function login()
    {

        $telephone = $this->post('telephone', NULL, 'telephone');
        $password = $this->post('password');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');
        $userData = $this->model('user')->getByTelephone($telephone);
        if (empty($userData))
            return new json(json::PARAMETER_ERROR, '用户尚未注册');
        $userHelper = new \application\helper\user();
        if ($userHelper->auth($password, $userData['password'], $userData['salt'])) {
            if ($userData['close'] == 1) {
                return new json(json::PARAMETER_ERROR, '账号已封，请联系管理员');
            }

            $userHelper->saveUserSession($userData);
            $userHelper->protectedUser($userData);

            if (isWechat()) {
                $wx_openid = $this->post('wx_openid', NULL);
                if (!empty($wx_openid)) {
                    $this->model('user')->where('telephone=?', [$telephone])->limit(1)->update([
                        'wx_openid_web' => $wx_openid,
                    ]);
                }
            }

            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR, '密码错误');
    }

    function search()
    {
        $name = $this->get('keywords', '', 'trim');
        $filter = [
            'start' => $this->get('start', 0),
            'length' => $this->get('length', 10),
            'parameter' => [
                'user.id',
                'upload.path as gravatar',
                'user.name',
                'user.money',
                'user.vip',
                'user.master',
                'user.telephone',
            ],
        ];
        $user = $this->model('user')->where('user.telephone like ?', ['%' . $name . '%'], 'or')->where('user.name like ?', ['%' . $name . '%'], 'or')->fetchAll($filter);

        $filter['parameter'] = 'count(*)';
        unset($filter['start']);
        unset($filter['length']);
        $total = $this->model('user')->fetch($filter);

        $userReturnModel = [
            'current' => count($user),
            'total' => isset($total[0]['count(*)']) ? $total[0]['count(*)'] : 0,
            'start' => $this->get('start', 0),
            'length' => $this->get('length', 10),
            'data' => $user,
        ];
        return new json(json::OK, NULL, $userReturnModel);
    }

    /**
     * 设置或者更改绑定的手机号
     */
    function telephone()
    {
        $sms_code = $this->post('sms_code', NULL);

        if (empty($sms_code))
            return new json(json::PARAMETER_ERROR, '请输入验证码');

        $telephone = $this->post('telephone', NULL, 'telephone');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');

        if (!empty($this->model('user')->where('telephone=?', [$telephone])->find())) {
            return new json(json::PARAMETER_ERROR, '手机号码已经注册');
        }

        if ($this->model('smslog')->check($telephone, $sms_code)) {
            $userHelper = new \application\helper\user();
            $uid = $userHelper->isLogin();
            if ($uid === NULL)
                return new json(json::NOT_LOGIN, '尚未登陆');

            $this->model('user')->where('id=?', [$uid])->update('telephone', $telephone);
            return new json(json::OK);
        } else {
            return new json(json::PARAMETER_ERROR, '验证码错误');
        }
    }

    /**
     * 获取或者设置用户个人介绍
     * @return \application\message\json
     */
    function description()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $description = $this->post('description', NULL, 'htmlspecialchars');
        if ($description === NULL) {
            $user = $this->model('user')->where('id=?', [$uid])->find();
            return new json(json::OK, NULL, isset($user['description']) ? $user['description'] : '');
        }
        $this->model('user')->where('id=?', [$uid])->update('description', $description);
        return new json(json::OK);
    }

    /**
     * 管理员修改密码
     * @return \application\message\json
     */
    function password()
    {
        $userHelper = new \application\helper\user();
        $id = $this->post('id');
        $password = $this->post('password', '');
        $paypassword = $this->post('paypassword', '');

        $adminHelper = new \application\helper\admin();
        if (empty($adminHelper->getAdminId())) {
            return new json(json::NOT_LOGIN, '尚未登录');
        }

        if (empty($id))
            return new json(json::PARAMETER_ERROR);
        if (strlen($password) < 6 && strlen($password) != 0)
            return new json(json::PARAMETER_ERROR, '登陆密码最少6位');

        $data = [];

        //判断是否需要修改登录密码
        if (!empty($password)) {
            $salt = random::word(6);
            $password = $userHelper->encrypt($password, $salt);
            $data['salt'] = $salt;
            $data['password'] = $password;
        }

        //判断是否需要修改支付密码
        if (!empty($paypassword)) {
            $paysalt = random::word(6);
            $paypassword = $userHelper->encrypt($paypassword, $salt, 'sha1');
            $data['pay_salt'] = $paysalt;
            $data['pay_password'] = $paypassword;
        }

        //假如没提交任何数据 直接返回修改成功
        if (empty($data)) {
            return new json(json::OK);
        }

        //对于渠道用户无法修改密码
        $user = $this->model('user')->where('id=?', [$id])->find();
        if (!empty($user) && $user['password'] != 1) {
            if ($this->model('user')->where('id=?', [$id])->update($data)) {
                return new json(json::OK);
            }
        }
        return new json(json::PARAMETER_ERROR);
    }

    /**
     * 管理员修改金额
     * @return \application\message\json
     */
    function money()
    {
        $id = $this->post('id');
        $money = $this->post('money', 0);
        $note = $this->post('note', '');
        $this->model('user')->transaction();
        if ($this->model('user')->where('id=?', [$id])->limit(1)->increase('money', $money)) {
            $userMoney = $this->model('user')->where('id=?', [$id])->find('money');
            if ($userMoney['money'] < 0) {
                $this->model('user')->rollback();
                return new json(json::PARAMETER_ERROR, '用户余额不能减的太少了');
            }
            if (!$this->model('swift')->insert([
                'uid' => $id,
                'money' => $money,
                'type' => floatval($money) > 0 ? 0 : 1,
                'time' => $_SERVER['REQUEST_TIME'],
                'note' => $note,
                'source' => 0,
            ])
            ) {
                $this->model('user')->rollback();
                return new json(json::PARAMETER_ERROR, '记录流水失败');
            }
            $this->model('user')->commit();
            return new json(json::OK);
        }
        $this->model('user')->rollback();
        return new json(json::PARAMETER_ERROR);
    }

    function vip()
    {
        return new json(json::PARAMETER_ERROR, '暂时关闭');

        $id = $this->post('id', 0, 'intval');
        $vip = $this->post('vip', 0, 'intval');
        $master = $this->post('master', 0, 'intval');
        $this->model('user')->where('id=?', [$id])->limit(1)->update([
            'master' => $master,
            'vip' => $vip
        ]);
        return new json(json::OK);
    }

    function remove()
    {
        return new json(json::PARAMETER_ERROR, '暂时关闭');

        $id = $this->post('id');
        if (!empty($id)) {
            if ($this->model('user')->where('id=?', [$id])->delete()) {
                return new json(json::OK);
            }
            return new json(json::PARAMETER_ERROR);
        }
        return new json(json::PARAMETER_ERROR);
    }

    /**
     * 用户关系绑定
     */
    function invit()
    {
        $invit = $this->post('invit');
        if (empty($invit))
            return new json(json::PARAMETER_ERROR, '请输入邀请码');
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $invit_user = $this->model('user')->where('invit=?', [$invit])->find();
        if (!empty($invit_user)) {
            if ($invit_user['vip'] == 0 && $invit_user['school']==0) {

                return new json(json::PARAMETER_ERROR, '邀请码错误');
            }

            if ($invit_user['master'] == 1) {
                $o_master = $invit_user['id'];
            } else {
                $o_master = $invit_user['o_master'];
            }

            if ($this->model('user')->where('id=?', [$uid])->update([
                'oid' => $invit_user['id'],
                'o_master' => $o_master,
                'invittime' => $_SERVER['REQUEST_TIME']
            ])
            ) {
                return new json(json::OK);
            }
            return new json(json::PARAMETER_ERROR, '不要重复绑定邀请人');
        }
        return new json(json::PARAMETER_ERROR, '邀请码错误');
    }

    function team()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $name = $this->post('name', '');
        $whole = $this->post('whole', 0, 'intval');//是否是全部  否则是直属团队
        $vip = $this->post('vip', NULL);
        $sort = $this->post('sort', 'invittime');

        if (!in_array($sort, ['invittime', 'total', 'total7', 'team', 'team7'])) {
            return new json(json::PARAMETER_ERROR, 'sort参数错误');
        }

        $filter_string = 'oid=?';
        $filter_array = [$uid];

        if (!empty($name)) {
            $filter_string = $filter_string . ' and user.name like ?';
            $filter_array = array_merge($filter_array, ['%' . $name . '%']);
        }
        if ($vip !== NULL && $vip !== '') {
            $vip = intval($vip);
            if ($vip == 3) {
                $filter_string = $filter_string . ' and master=?';
                $filter_array = array_merge($filter_array, [1]);
            } else {
                $filter_string = $filter_string . ' and vip=?';
                $filter_array = array_merge($filter_array, [$vip]);
            }
        }

        $endtime7 = strtotime(date('Y-m-d')) + 24 * 3600;
        $starttime7 = $endtime7 - 7 * 24 * 3600;

        $user = $this->model('user')->orderby($sort, 'desc')->table('upload', 'left join', 'upload.id = user.gravatar')->where($filter_string, $filter_array)->select([
            'user.id',
            'user.description',
            'user.name',
            'upload.path as gravatar',
            'user.invittime',
            '(select sum(swift.money) from swift where swift.uid=user.id and swift.source in (2,3,4,5)) as total',//总收益
            '(select sum(swift.money) from swift where swift.uid=user.id and swift.source in (2,3,4,5) and swift.time < ' . $endtime7 . ' and swift.time > ' . $starttime7 . ') as total7',//最近7天收益
            '(select count(*) from user as user2 where user2.oid=user.id) as team',//团队发展总人数
            '(select count(*) from user as user2 where user2.oid=user.id and user2.invittime < ' . $endtime7 . ' and user2.invittime > ' . $starttime7 . ') as team7',//团队发展最近7天人数
        ]);

        if ($whole) {
            $temp = $user;
            while (!empty($user) && is_array($user)) {
                $container = [];
                foreach ($user as $u) {
                    $filter_array = [$u['id']];
                    $filter_string = 'oid=?';
                    if (!empty($name)) {
                        $filter_string = $filter_string . ' and user.name like ?';
                        $filter_array = array_merge($filter_array, ['%' . $name . '%']);
                    }
                    if ($vip !== NULL) {
                        $vip = intval($vip);
                        if ($vip == 3) {
                            $filter_string = $filter_string . ' and master=?';
                            $filter_array = array_merge($filter_array, [1]);
                        } else {
                            $filter_string = $filter_string . ' and vip=?';
                            $filter_array = array_merge($filter_array, [$vip]);
                        }
                    }

                    $temp_user = $this->model('user')->table('upload', 'left join', 'upload.id = user.gravatar')->where($filter_string, $filter_array)->select([
                        'user.id',
                        'user.description',
                        'upload.path as gravatar',
                        'user.name',
                        'user.invittime',
                        '(select sum(swift.money) from swift where swift.uid=user.id and swift.source in (2,3,4,5)) as total',//总收益
                        '(select sum(swift.money) from swift where swift.uid=user.id and swift.source in (2,3,4,5) and swift.time < ' . $endtime7 . ' and swift.time > ' . $starttime7 . ') as total7',//最近7天收益
                        '(select count(*) from user as user2 where user2.oid=user.id) as team',//团队发展总人数
                        '(select count(*) from user as user2 where user2.oid=user.id and user2.invittime < ' . $endtime7 . ' and user2.invittime > ' . $starttime7 . ') as team7',//团队发展最近7天人数
                    ]);
                    $temp = array_merge($temp, $temp_user);
                    $container = array_merge($container, $temp_user);
                }
                $user = $container;
            }

            usort($temp, function ($a, $b) use ($sort) {
                if ($a[$sort] == $b[$sort])
                    return 0;
                if ($a[$sort] > $b[$sort])
                    return -1;
                return 1;
            });

            return new json(json::OK, NULL, $temp);
        } else {
            return new json(json::OK, NULL, $user);
        }
    }

    function wechat()
    {
        $id = $this->post('id');
        $wechat = $this->post('wechat');
        if (!empty($id)) {
            $this->model('user')->where('id=?', [$id])->limit(1)->update('wechat_no', $wechat);
        }
        return new json(json::OK);
    }

    function close()
    {
        $adminHelper = new \application\helper\admin();
        $aid = $adminHelper->getAdminId();
        if (empty($aid)) {
            return new json(json::NOT_LOGIN);
        }
        $id = $this->post('id');

        $user = $this->model('user')->where('id=?', [$id])->find();
        if (!empty($user)) {
            if ($user['close'] == 0) {
                if ($this->model('user')->where('id=?', [$id])->update([
                    'close' => 1,
                ])
                ) {
                    return new json(json::OK);
                }
            } else {
                if ($this->model('user')->where('id=?', [$id])->update([
                    'close' => 0,
                ])
                ) {
                    return new json(json::OK);
                }
            }
        }
        return new json(json::PARAMETER_ERROR);
    }

    function student()
    {

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();

        if (empty($uid))
            return new json(json::NOT_LOGIN);
        $identify = $this->post("numberc");

        $user = $this->model("user")->where("id=?", [$uid])->find(["school"]);
        if ($user['school'] == 1) {
            return new json(json::PARAMETER_ERROR, "请勿重复验证");
        }
        if ($user['school'] == 2) {
            return new json(json::PARAMETER_ERROR, "您已被拒绝通过");
        }


        //审核身份证号跟用户名是否匹配
        if (strlen($identify) != 15 && strlen($identify) != 18 && strlen($identify) != 0)
            return new json(json::PARAMETER_ERROR, '身份证号码必须是15或者18位');
        //判断年龄
        $date = strtotime(substr('370702199411024812', 6, 8));//获得出生年月日的时间戳
        $today = strtotime('today');//获得今日的时间戳
        $diff = floor(($today - $date) / 86400 / 365);//得到两个日期相差的大体年数
        if($diff<16 || $diff>30){
            return new json(json::PARAMETER_ERROR, '您的年龄不符合我们个规定');
        }
        if (!empty($identify)) {
            if (idcard::auth($this->post('name'), $identify) == 0) {
                return new json(json::PARAMETER_ERROR, '用户名和身份证号码不匹配');
            }
        }

        //将学生信息保存到数据库
        if ($this->model("student_info")->insert([
            "uid" => $uid,
            "name" => $this->post("name"),
            "school" => $this->post("scname"),
            "card" => $this->post("num"),
            "cartnum" => $identify,
            "zhuanye" => $this->post("zhuanye"),
            "cl" => $this->post("cl"),
        ])
        ) {

            //将用户的school改为1
            $this->model("user")->where("id=?", [$uid])->update(["school" => 1]);
            return new json(json::OK);
        } else {
            return new json(json::PARAMETER_ERROR, "验证失败，请重试");
        }
    }

    function school()
    {
        $id = $this->post('id');
        if ($id) {
            $this->model("user")->where("id=?", [$id])->update(["school" => 2]);
            return new json(json::OK);
        } else {
            return new json(json::PARAMETER_ERROR, "修改失败，请重试");
        }
    }
}