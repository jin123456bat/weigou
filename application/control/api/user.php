<?php
namespace application\control\api;

use application\message\json;
use application\helper\sms;
use system\core\random;
use system\core\http;

class user extends common
{
    private $_response;

    function __construct()
    {
        parent::__construct();
        $this->_response = $this->init();
    }

    /**
     * 解绑微博
     * @return \application\message\json
     */
    function unbindWeibo()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $this->model('user')->where('id=?', [$uid])->update([
            'weibo_uid' => NULL,
            'weibo_name' => NULL,
            'weibo_access_token' => NULL,
            'weibo_starttime' => NULL,
            'weibo_endtime' => NULL,
        ]);
        return new json(json::OK);
    }

    /**
     * 绑定微博
     * @return \application\message\json
     */
    function bindWeibo()
    {
        if (!empty($this->_response))
            return $this->_response;

        $uid = $this->data('uid');
        $access_token = $this->data('access_token');
        $nickname = $this->data('nickname', '');
        $expires_in = $this->data('expires_in', 0, 'intval');
        if (empty($uid) || empty($access_token) || empty($expires_in))
            return new json(json::PARAMETER_ERROR);

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $data = [
            'weibo_uid' => $uid,
            'weibo_name' => $nickname,
            'weibo_access_token' => $access_token,
            'weibo_starttime' => $_SERVER['REQUEST_TIME'],
            'weibo_endtime' => $_SERVER['REQUEST_TIME'] + $expires_in
        ];

        $user = $this->model('user')->where('id=?', [$uid])->find();
        if (empty($user['name'])) {
            $data['name'] = $nickname;
        }

        if ($this->model('user')->where('id=?', [$uid])->limit(1)->update($data)) {
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR, '请不要重复绑定');
    }

    /**
     * 解绑QQ
     * @return \application\message\json
     */
    function unbindQQ()
    {
        if (!empty($this->_response)) {
            return $this->_response;
        }

        $client = $this->data('client');
        if (!in_array($client, ['web', 'ios', 'android'], true)) {
            return new json(json::PARAMETER_ERROR, 'client参数错误');
        }

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $this->model('user')->where('id=?', [$uid])->update([
            'qq_openid_' . $client => NULL,
            'qq_name' => '',
            'qq_time_' . $client => NULL,
        ]);
        return new json(json::OK);
    }

    /**
     * 绑定QQ
     * @return \application\message\json
     */
    function bindQQ()
    {
        if (!empty($this->_response)) {
            return $this->_response;
        }

        $client = $this->data('client');
        if (!in_array($client, ['web', 'ios', 'android'], true)) {
            return new json(json::PARAMETER_ERROR, 'client参数错误');
        }

        $openid = $this->data('openid');
        $nickname = $this->data('nickname', '');
        if (empty($openid))
            return new json(json::PARAMETER_ERROR);

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $data = [
            'qq_openid_' . $client => $openid,
            'qq_name' => $nickname,
            'qq_time_' . $client => $_SERVER['REQUEST_TIME'] + 27 * 3600 * 24  //QQ的时间是一个月
        ];

        $user = $this->model('user')->where('id=?', [$uid])->find();
        if (empty($user['name'])) {
            $data['name'] = $nickname;
        }

        if ($this->model('user')->where('id=?', [$uid])->limit(1)->update($data)) {
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR, '请不要重复绑定');
    }

    /**
     * 解绑微信
     */
    function unbindWechat()
    {
        if (!empty($this->_response))
            return $this->_response;

        $client = $this->data('client');
        if (!in_array($client, ['web', 'ios', 'android'], true)) {
            return new json(json::PARAMETER_ERROR, 'client参数错误');
        }

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $this->model('user')->where('id=?', [$uid])->update([
            'wx_openid_' . $client => NULL,
            'wx_name' => '',
        ]);
        return new json(json::OK);
    }

    /**
     * 绑定微信
     */
    function bindWechat()
    {
        if (!empty($this->_response)) {
            return $this->_response;
        }

        $client = $this->data('client');
        if (!in_array($client, ['web', 'ios', 'android'], true)) {
            return new json(json::PARAMETER_ERROR, 'client参数错误');
        }

        $openid = $this->data('openid');
        $nickname = $this->data('nickname', '');
        if (empty($openid)) {
            return new json(json::PARAMETER_ERROR);
        }

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid)) {
            return new json(json::NOT_LOGIN);
        }

        $data = [
            'wx_openid_' . $client => $openid,
            'wx_name' => $nickname
        ];

        $user = $this->model('user')->where('id=?', [$uid])->find();
        if (empty($user['name']) && !empty($nickname)) {
            $data['name'] = $nickname;
        }

        if ($this->model('user')->where('id=?', [$uid])->limit(1)->update($data)) {
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR, '请不要重复绑定');
    }

    function loginWeibo3()
    {
        if (!empty($this->_response))
            return $this->_response;

        $uid = $this->data('uid');
        $access_token = $this->data('access_token');
        $expires_in = $this->data('expires_in');
        if (empty($uid) || empty($access_token) || empty($expires_in))
            return new json(json::PARAMETER_ERROR);

        $telephone = $this->data('telephone');
        $code = $this->data('code');
        $password = $this->data('password');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');
        if (empty($code))
            return new json(json::PARAMETER_ERROR, '请输入验证码');
        if (empty($password))
            return new json(json::PARAMETER_ERROR, '请输入密码');
        if (strlen($password) < 6)
            return new json(json::PARAMETER_ERROR, '密码不得低于6位');

        $nickname = $this->data('nickname', '');
        $headimgurl = $this->data('headimgurl', '');

        if ($this->model('smslog')->check($telephone, $code)) {
            if (!empty($this->model('user')->where('telephone=?', [$telephone])->find())) {
                return new json(json::PARAMETER_ERROR, '手机号已经注册');
            }

            $userHelper = new \application\helper\user();
            $user = $this->model('user')->where('weibo_uid=?', [$uid])->table('upload', 'left join', 'user.gravatar=upload.id')->find([
                'user.*',
                'upload.path as gravatar'
            ]);
            if (empty($user)) {
                $user = $userHelper->createUserWithTelephone($telephone, $password);
                $user['weibo_uid'] = $uid;
                $user['weibo_access_token'] = $access_token;
                $user['weibo_starttime'] = $_SERVER['REQUEST_TIME'];
                $user['weibo_endtime'] = $_SERVER['REQUEST_TIME'] + $expires_in;
                $user['weibo_name'] = $nickname;
                if (!empty($nickname)) {
                    $user['name'] = $nickname;
                }
                if (!empty($headimgurl)) {
                    $filename = './application/upload/' . md5($headimgurl) . '.gif';
                    file_put_contents($filename, http::get($headimgurl));
                    if (file_exists($filename)) {
                        $this->model('upload')->insert([
                            'name' => $headimgurl,
                            'type' => 'gif',
                            'path' => $filename,
                            'time' => $_SERVER['REQUEST_TIME'],
                            'size' => filesize($filename)
                        ]);
                        $fileid = $this->model('upload')->lastInsertId();
                        $user['gravatar'] = $fileid;
                    }
                }
                if ($this->model('user')->insert($user)) {
                    $user['id'] = $this->model('user')->lastInsertId();
                    $userHelper->saveUserSession($user);
                    $userHelper->protectedUser($user);
                    return new json(json::OK, NULL, $user);
                }
            } else {
                $salt = random::word(6);
                if ($this->model('user')->where('weibo_uid=?', [$uid])->limit(1)->update([
                    'telephone' => $telephone,
                    'password' => $userHelper->encrypt($password, $salt),
                    'salt' => $salt,
                ])
                ) {
                    if ($user['close'] == 1) {
                        return new json(json::PARAMETER_ERROR, '账号已封');
                    }
                    $userHelper->saveUserSession($user);
                    $userHelper->protectedUser($user);
                    return new json(json::OK, NULL, $user);
                } else {
                    return new json(json::PARAMETER_ERROR);
                }
            }
        }
    }

    function loginWeibo2()
    {
        if (!empty($this->_response))
            return $this->_response;

        $access_token = $this->data('access_token');
        $expires_in = $this->data('expires_in');//有效期 access_token
        $uid = $this->data('uid');
        if (empty($access_token) || empty($expires_in) || empty($uid))
            return new json(json::PARAMETER_ERROR);

        $telephone = $this->data('telephone', '', 'telephone');
        $code = $this->data('code');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');
        if (empty($code))
            return new json(json::PARAMETER_ERROR, '请输入验证码');

        $nickname = $this->data('nickname');//昵称
        $headimgurl = $this->data('headimgurl');//头像地址 高清的

        if ($this->model('smslog')->check($telephone, $code)) {
            $user = $this->model('user')->where('telephone=?', [$telephone])->find();
            if (empty($user)) {
                return new json(json::OK, NULL, 3);
            } else {
                if (empty($user['weibo_uid']) || $_SERVER['REQUEST_TIME'] > $user['weibo_endtime']) {
                    $data = [
                        'weibo_name' => $nickname,
                        'weibo_uid' => $uid,
                        'weibo_access_token' => $access_token,
                        'weibo_starttime' => $_SERVER['REQUEST_TIME'],
                        'weibo_endtime' => $_SERVER['REQUEST_TIME'] + $expires_in
                    ];

                    if (empty($user['name']) && !empty($nickname)) {
                        $data['name'] = $nickname;
                    }

                    if (empty($user['gravatar']) && !empty($headimgurl)) {
                        $filename = './application/upload/' . md5($headimgurl) . '.gif';
                        file_put_contents($filename, http::get($headimgurl));
                        if (file_exists($filename)) {
                            $this->model('upload')->insert([
                                'name' => $headimgurl,
                                'type' => 'gif',
                                'path' => $filename,
                                'time' => $_SERVER['REQUEST_TIME'],
                                'size' => filesize($filename)
                            ]);
                            $fileid = $this->model('upload')->lastInsertId();
                            $data['gravatar'] = $fileid;
                        }
                    }

                    if ($this->model('user')->where('telephone=?', [$telephone])->limit(1)->update($data)) {
                        $userHelper = new \application\helper\user();
                        $user = $this->model('user')->table('upload', 'left join', 'upload.id=user.gravatar')->where('telephone=?', [$telephone])->find([
                            'user.*',
                            'upload.path as gravatar',
                        ]);
                        if ($user['close'] == 1) {
                            return new json(json::PARAMETER_ERROR, '账号已封');
                        }
                        $userHelper->saveUserSession($user);
                        $userHelper->protectedUser($user);
                        return new json(json::OK, NULL, $user);
                    }
                } else {
                    return new json(json::PARAMETER_ERROR, '已经绑定过QQ账号');
                }
            }
        }
        return new json(json::PARAMETER_ERROR, '验证码错误');
    }

    function loginWeibo()
    {
        if (!empty($this->_response))
            return $this->_response;

        $uid = $this->data('uid');
        if (empty($uid))
            return new json(json::PARAMETER_ERROR);

        $user = $this->model('user')->table('upload', 'left join', 'upload.id=user.gravatar')->where('weibo_uid=?', [$uid])->find([
            'user.*',
            'upload.path as gravatar',
        ]);
        if (empty($user)) {
            return new json(json::OK, NULL, 2);
        } else {
            if (empty($user['telephone'])) {
                return new json(json::OK, NULL, 2);
            } else {
                if ($user['close'] == 1) {
                    return new json(json::PARAMETER_ERROR, '账号已封');
                }
                $userHelper = new \application\helper\user();
                $userHelper->saveUserSession($user);
                $userHelper->protectedUser($user);
                return new json(json::OK, NULL, $user);
            }
        }
    }

    function loginQQ3()
    {
        if (!empty($this->_response)) {
            return $this->_response;
        }
        $client = $this->data('client');
        if (!in_array($client, ['web', 'ios', 'android'], true)) {
            return new json(json::PARAMETER_ERROR, 'client参数错误');
        }

        $openid = $this->data('openid');
        $time = $_SERVER['REQUEST_TIME'];
        if (empty($openid))
            return new json(json::PARAMETER_ERROR);

        $telephone = $this->data('telephone');
        $code = $this->data('code');
        $password = $this->data('password');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');
        if (empty($code))
            return new json(json::PARAMETER_ERROR, '请输入验证码');
        if (empty($password))
            return new json(json::PARAMETER_ERROR, '请输入密码');
        if (strlen($password) < 6)
            return new json(json::PARAMETER_ERROR, '密码不得低于6位');

        $nickname = $this->data('nickname', '');
        $headimgurl = $this->data('headimgurl', '');

        if ($this->model('smslog')->check($telephone, $code)) {
            if (!empty($this->model('user')->where('telephone=?', [$telephone])->find())) {
                return new json(json::PARAMETER_ERROR, '手机号已经注册');
            }

            $userHelper = new \application\helper\user();
            $user = $this->model('user')->where('qq_openid_' . $client . '=?', [$openid])->table('upload', 'left join', 'user.gravatar=upload.id')->find([
                'user.*',
                'upload.path as gravatar'
            ]);
            if (empty($user)) {
                $user = $userHelper->createUserWithTelephone($telephone, $password);
                $user['qq_openid_' . $client] = $openid;
                $user['qq_time_' . $client] = $time;
                $user['qq_name'] = $nickname;
                if (!empty($nickname)) {
                    $user['name'] = $nickname;
                }
                if (!empty($headimgurl)) {
                    $filename = './application/upload/' . md5($headimgurl) . '.gif';
                    file_put_contents($filename, http::get($headimgurl));
                    if (file_exists($filename)) {
                        $this->model('upload')->insert([
                            'name' => $headimgurl,
                            'type' => 'gif',
                            'path' => $filename,
                            'time' => $_SERVER['REQUEST_TIME'],
                            'size' => filesize($filename)
                        ]);
                        $fileid = $this->model('upload')->lastInsertId();
                        $user['gravatar'] = $fileid;
                    }
                }
                if ($this->model('user')->insert($user)) {
                    $user['id'] = $this->model('user')->lastInsertId();
                    $userHelper->saveUserSession($user);
                    $userHelper->protectedUser($user);
                    return new json(json::OK, NULL, $user);
                }
            } else {
                $salt = random::word(6);
                if ($this->model('user')->where('qq_openid_' . $client . '=?', [$openid])->limit(1)->update([
                    'telephone' => $telephone,
                    'password' => $userHelper->encrypt($password, $salt),
                    'salt' => $salt,
                ])
                ) {
                    if ($user['close'] == 1) {
                        return new json(json::PARAMETER_ERROR, '账号已封');
                    }
                    $userHelper->saveUserSession($user);
                    $userHelper->protectedUser($user);
                    return new json(json::OK, NULL, $user);
                } else {
                    return new json(json::PARAMETER_ERROR);
                }
            }
        }
    }

    function loginQQ2()
    {
        if (!empty($this->_response)) {
            return $this->_response;
        }
        $client = $this->data('client');
        if (!in_array($client, ['web', 'ios', 'android'], true)) {
            return new json(json::PARAMETER_ERROR, 'client参数错误');
        }

        $openid = $this->data('openid');
        $time = $_SERVER['REQUEST_TIME'];
        if (empty($openid))
            return new json(json::PARAMETER_ERROR);

        $telephone = $this->data('telephone');
        $code = $this->data('code');

        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');
        if (empty($code))
            return new json(json::PARAMETER_ERROR, '请输入验证码');

        if ($this->model('smslog')->check($telephone, $code)) {
            $user = $this->model('user')->where('telephone=?', [$telephone])->find();
            if (empty($user)) {
                return new json(json::OK, NULL, 3);
            } else {
                if (empty($user['qq_openid_' . $client]) || $_SERVER['REQUEST_TIME'] > $user['qq_time_' . $client] - 3600 * 3) {
                    $nickname = $this->data('nickname', '');
                    $headimgurl = $this->data('headimgurl');

                    $data = [
                        'qq_name' => $nickname,
                        'qq_openid_' . $client => $openid,
                        'qq_time_' . $client => $time,
                    ];

                    if (empty($user['name']) && !empty($nickname)) {
                        $data['name'] = $nickname;
                    }

                    if (empty($user['gravatar']) && !empty($headimgurl)) {
                        $filename = './application/upload/' . md5($headimgurl) . '.gif';
                        file_put_contents($filename, http::get($headimgurl));
                        if (file_exists($filename)) {
                            $this->model('upload')->insert([
                                'name' => $headimgurl,
                                'type' => 'gif',
                                'path' => $filename,
                                'time' => $_SERVER['REQUEST_TIME'],
                                'size' => filesize($filename)
                            ]);
                            $fileid = $this->model('upload')->lastInsertId();
                            $data['gravatar'] = $fileid;
                        }
                    }

                    if ($this->model('user')->where('telephone=?', [$telephone])->limit(1)->update($data)) {
                        $userHelper = new \application\helper\user();
                        $user = $this->model('user')->table('upload', 'left join', 'upload.id=user.gravatar')->where('telephone=?', [$telephone])->find([
                            'user.*',
                            'upload.path as gravatar',
                        ]);
                        if ($user['close'] == 1) {
                            return new json(json::PARAMETER_ERROR, '账号已封');
                        }
                        $userHelper->saveUserSession($user);
                        $userHelper->protectedUser($user);
                        return new json(json::OK, NULL, $user);
                    }
                } else {
                    return new json(json::PARAMETER_ERROR, '已经绑定过QQ账号');
                }
            }
        }
        return new json(json::PARAMETER_ERROR, '验证码错误');
    }

    function loginQQ()
    {
        if (!empty($this->_response)) {
            return $this->_response;
        }

        $client = $this->data('client');
        if (!in_array($client, ['ios', 'android', 'web'], true)) {
            return new json(json::PARAMETER_ERROR, 'client参数错误');
        }

        $openid = $this->data('openid');
        if (empty($openid))
            return new json(json::PARAMETER_ERROR, 'openid不能为空');
        $user = $this->model('user')->table('upload', 'left join', 'upload.id=user.gravatar')->where('qq_openid_' . $client . '=?', [$openid])->find([
            'user.*',
            'upload.path as gravatar'
        ]);
        if (!empty($user)) {
            if (empty($user['telephone'])) {
                return new json(json::OK, NULL, 2);
            } else {
                if ($user['close'] == 1) {
                    return new json(json::PARAMETER_ERROR, '账号已封');
                }
                $userHelper = new \application\helper\user();
                $userHelper->saveUserSession($user);
                $userHelper->protectedUser($user);
                return new json(json::OK, NULL, $user);
            }
        } else {
            return new json(json::OK, NULL, 2);
        }
    }

    function loginWechat3()
    {
        if (!empty($this->_response)) {
            return $this->_response;
        }
        $client = $this->data('client');
        if (!in_array($client, ['web', 'ios', 'android'], true)) {
            return new json(json::PARAMETER_ERROR, 'client参数错误');
        }

        $telephone = $this->data('telephone');
        $code = $this->data('code');
        $password = $this->data('password');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');
        if (empty($code))
            return new json(json::PARAMETER_ERROR, '请输入验证码');
        if (empty($password))
            return new json(json::PARAMETER_ERROR, '请输入密码');
        if (strlen($password) < 6)
            return new json(json::PARAMETER_ERROR, '密码不得低于6位');

        $openid = $this->data('openid');
        if (empty($openid))
            return new json(json::PARAMETER_ERROR);

        $nickname = $this->data('nickname');
        $headimgurl = $this->data('headimgurl');
        if ($this->model('smslog')->check($telephone, $code)) {
            if (!empty($this->model('user')->where('telephone=?', [$telephone])->find())) {
                return new json(json::PARAMETER_ERROR, '手机号已经注册');
            }
            $userHelper = new \application\helper\user();
            $user = $this->model('user')->where('wx_openid_' . $client . '=?', [$openid])->table('upload', 'left join', 'user.gravatar=upload.id')->find([
                'user.*',
                'upload.path as gravatar'
            ]);
            if (empty($user)) {
                $user = $userHelper->createUserWithTelephone($telephone, $password);
                $user['wx_openid_' . $client] = $openid;
                if (!empty($nickname)) {
                    $user['name'] = $nickname;
                    $user['wx_name'] = $nickname;
                }
                if (!empty($headimgurl)) {
                    $filename = './application/upload/' . md5($headimgurl) . '.png';
                    file_put_contents($filename, http::get($headimgurl));
                    if (file_exists($filename)) {
                        $this->model('upload')->insert([
                            'name' => $headimgurl,
                            'type' => 'png',
                            'path' => $filename,
                            'time' => $_SERVER['REQUEST_TIME'],
                            'size' => filesize($filename)
                        ]);
                        $fileid = $this->model('upload')->lastInsertId();
                        $user['gravatar'] = $fileid;
                    }
                }
                if ($this->model('user')->insert($user)) {
                    $user['id'] = $this->model('user')->lastInsertId();
                    $userHelper->saveUserSession($user);
                    $userHelper->protectedUser($user);
                    return new json(json::OK, NULL, $user);
                }
            } else {
                $salt = random::word(6);
                if ($this->model('user')->where('wx_openid_' . $client . '=?', [$openid])->limit(1)->update([
                    'telephone' => $telephone,
                    'password' => $userHelper->encrypt($password, $salt),
                    'salt' => $salt,
                ])
                ) {
                    if ($user['close'] == 1) {
                        return new json(json::PARAMETER_ERROR, '账号已封');
                    }
                    $userHelper->saveUserSession($user);
                    $userHelper->protectedUser($user);
                    return new json(json::OK, NULL, $user);
                } else {
                    return new json(json::PARAMETER_ERROR);
                }
            }
        }
    }

    /**
     * 绑定手机号和微信号
     * @return \application\message\json
     */
    function loginWechat2()
    {
        if (!empty($this->_response)) {
            return $this->_response;
        }
        $client = $this->data('client');
        if (!in_array($client, ['web', 'ios', 'android'], true)) {
            return new json(json::PARAMETER_ERROR, 'client参数错误');
        }

        $telephone = $this->data('telephone');
        $code = $this->data('code');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');
        if (empty($code))
            return new json(json::PARAMETER_ERROR, '请输入验证码');

        $openid = $this->data('openid');
        if (empty($openid))
            return new json(json::PARAMETER_ERROR);

        $nickname = $this->data('nickname');
        $headimgurl = $this->data('headimgurl');

        if ($this->model('smslog')->check($telephone, $code)) {
            $user = $this->model('user')->where('telephone=?', [$telephone])->find();
            if (empty($user)) {
                return new json(json::OK, NULL, 3);
            } else {
                if (empty($user['wx_openid_' . $client])) {
                    $data = [
                        'wx_openid_' . $client => $openid,
                        'wx_name' => $nickname,
                    ];
                    if (empty($user['name']) && !empty($nickname)) {
                        $data['name'] = $nickname;
                    }
                    if (empty($user['gravatar']) && !empty($headimgurl)) {
                        $filename = './application/upload/' . md5($headimgurl) . '.png';
                        file_put_contents($filename, http::get($headimgurl));
                        if (file_exists($filename)) {
                            $this->model('upload')->insert([
                                'name' => $headimgurl,
                                'type' => 'png',
                                'path' => $filename,
                                'time' => $_SERVER['REQUEST_TIME'],
                                'size' => filesize($filename)
                            ]);
                            $fileid = $this->model('upload')->lastInsertId();
                            $data['gravatar'] = $fileid;
                        }
                    }

                    if ($this->model('user')->where('telephone=?', [$telephone])->limit(1)->update($data)) {
                        $userHelper = new \application\helper\user();
                        $user = $this->model('user')->table('upload', 'left join', 'upload.id=user.gravatar')->where('telephone=?', [$telephone])->find([
                            'user.*',
                            'upload.path as gravatar',
                        ]);
                        if ($user['close'] == 1) {
                            return new json(json::PARAMETER_ERROR, '账号已封');
                        }
                        $userHelper->saveUserSession($user);
                        $userHelper->protectedUser($user);
                        return new json(json::OK, NULL, $user);
                    }
                } else {
                    return new json(json::PARAMETER_ERROR, '已绑定过微信账号');
                }
            }
        }
        return new json(json::PARAMETER_ERROR, '验证码错误');
    }

    /**
     * 检查微信号是否已经登陆过
     */
    function loginWechat()
    {
        if (!empty($this->_response)) {
            return $this->_response;
        }

        $client = $this->data('client');
        if (!in_array($client, ['web', 'ios', 'android'], true)) {
            return new json(json::PARAMETER_ERROR, 'client参数错误');
        }

        $openid = $this->data('openid');
        if (empty($openid)) {
            return new json(json::PARAMETER_ERROR, 'openid不能为空');
        }

        $userHelper = new \application\helper\user();
        $user = $this->model('user')->table('upload', 'left join', 'user.gravatar=upload.id')->where('wx_openid_' . $client . '=?', [$openid])->find([
            'user.*',
            'upload.path as gravatar'
        ]);
        if (empty($user)) {
            return new json(json::OK, NULL, 2);
        } else {
            if (empty($user['telephone'])) {
                return new json(json::OK, NULL, 2);
            } else {
                if ($user['close'] == 1) {
                    return new json(json::PARAMETER_ERROR, '账号已封');
                }
                $userHelper->saveUserSession($user);
                $userHelper->protectedUser($user);
                return new json(json::OK, NULL, $user);
            }
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

        $description = $this->data('description', NULL, 'htmlspecialchars');
        if ($description === NULL) {
            $user = $this->model('user')->where('id=?', [$uid])->find();
            return new json(json::OK, NULL, isset($user['description']) ? $user['description'] : '');
        }
        $this->model('user')->where('id=?', [$uid])->update('description', $description);
        return new json(json::OK);
    }

    /**
     * 设置支付密码
     */
    function pay_password()
    {
        if (!empty($this->_response))
            return $this->_response;
        $pay_password = $this->data('pay_password', NULL);
        if (empty($pay_password))
            return new json(json::PARAMETER_ERROR, '支付密码不得为空');

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $user = $this->model('user')->where('id=?', [$uid])->find();
        if (empty($user)) {
            return new json(json::PARAMETER_ERROR, '系统错误');
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
     * 验证用户的支付密码是否正确
     */
    function auth_pay_password()
    {
        if (!empty($this->_response))
            return $this->_response;

        $pay_password = $this->data('pay_password');
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

    /**
     * 设置用户昵称
     * @return \application\message\json
     */
    function name()
    {
        if (!empty($this->_response))
            return $this->_response;

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $name = $this->data('name', '');

        $this->model('user')->where('id=?', [$uid])->update('name', $name);
        return new json(json::OK);
    }

    /**
     * 设置用户头像
     */
    function gravatar()
    {
        if (!empty($this->_response))
            return $this->_response;

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $gravatar = $this->data('gravatar', NULL);
        if (empty($gravatar))
            return new json(json::PARAMETER_ERROR, '头像id为空');

        if ($this->model('user')->where('id=?', [$uid])->limit(1)->update('gravatar', $gravatar)) {
            return new json(json::OK);
        } else {
            return new json(json::PARAMETER_ERROR, '更改头像失败');
        }
    }

    /**
     * 更改用户登录密码，（带手机号码验证）
     */
    function changepassword()
    {
        if (!empty($this->_response))
            return $this->_response;

        $telephone = $this->data('telephone', NULL, 'telephone');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');

        $password = $this->data('password');
        if (strlen($password) < 6)
            return new json(json::PARAMETER_ERROR, '密码太短');

        $smscode = $this->data('smscode', NULL);
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
     * 更改用户登录密码（无手机号码验证）
     */
    function setpassword()
    {
        if (!empty($this->_response))
            return $this->_response;

        $telephone = $this->data('telephone', NULL, 'telephone');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');

        $password = $this->data('password');
        if (strlen($password) < 6)
            return new json(json::PARAMETER_ERROR, '密码太短');

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

    /**
     * 设置或者更改绑定的手机号
     */
    function telephone()
    {
        if (!empty($this->_response))
            return $this->_response;

        $debug = $this->data('debug', 0, 'intval');
        $sms_code = $this->data('sms_code', NULL);

        if (empty($sms_code) && !$debug)
            return new json(json::PARAMETER_ERROR, '验证码错误');

        $telephone = $this->data('telephone', NULL, 'telephone');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');

        if (!empty($this->model('user')->where('telephone=?', [$telephone])->find())) {
            return new json(json::PARAMETER_ERROR, '手机号码已经注册');
        }

        if ($this->model('smslog')->check($telephone, $sms_code) || $debug) {
            $userHelper = new \application\helper\user();
            $uid = $userHelper->isLogin();
            if ($uid === NULL)
                return new json(json::NOT_LOGIN, '尚未登陆');

            if ($this->model('user')->where('id=?', [$uid])->update('telephone', $telephone)) {
                return new json(json::OK);
            } else {
                return new json(json::PARAMETER_ERROR);
            }
        } else {
            return new json(json::PARAMETER_ERROR, '验证码错误');
        }
    }

    /**
     * 向用户发送验证码
     */
    function code()
    {
        if (!empty($this->_response))
            return $this->_response;
        $telephone = $this->data('telephone', NULL, 'telephone');
        if (empty($telephone)) {
            return new json(json::PARAMETER_ERROR, '手机号码错误');
        }
        $checkTelephone = $this->data('checkTelephone');
        if ($checkTelephone !== NULL) {
            if ($checkTelephone) {
                if (!empty($this->model('user')->where('telephone=?', [$telephone])->find())) {
                    return new json(json::PARAMETER_ERROR, '手机号码已注册');
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
        if (!empty($this->_response))
            return $this->_response;

        $sms_code = $this->data('sms_code', NULL);
        if (empty($sms_code))
            return new json(json::PARAMETER_ERROR, '验证码错误');

        $telephone = $this->data('telephone', NULL, 'telephone');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号错误');

        $checkTelephone = $this->data('checkTelephone');
        if ($checkTelephone !== NULL) {
            if ($checkTelephone) {
                if (!empty($this->model('user')->where('telephone=?', [$telephone])->find())) {
                    return new json(json::PARAMETER_ERROR, '手机号码已注册');
                }
            } else {
                if (empty($this->model('user')->where('telephone=?', [$telephone])->find())) {
                    return new json(json::PARAMETER_ERROR, '手机号码尚未注册');
                }
            }
        }

        if ($this->model('smslog')->check($telephone, $sms_code)) {
            return new json(json::OK);
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

        $debug = $this->data('debug', 0);
        $sms_code = $this->data('sms_code', NULL);
        if (empty($sms_code) && !$debug)
            return new json(json::PARAMETER_ERROR, '验证码错误');
        $telephone = $this->data('telephone', NULL, 'telephone');
        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');
        $password = $this->data('password');
        if (strlen($password) < 6)
            return new json(json::PARAMETER_ERROR, '密码长度太短');

        $name = $this->data('name', '');

        if ($this->model('smslog')->check($telephone, $sms_code) || $debug) {
            if ($this->model('user')->telephoneExist($telephone))
                return new json(json::PARAMETER_ERROR, '手机号码已注册');
            $userHelper = new \application\helper\user();
            $user = $userHelper->createUserWithTelephone($telephone, $password);
            $user['name'] = $name;
            if ($this->model('user')->insert($user)) {
                $uid = $this->model('user')->lastInsertId();
                $user = $this->model('user')->where('id=?', [$uid])->find();
                $userHelper->saveUserSession($user);
                $userHelper->protectedUser($user);
                return new json(json::OK, NULL, $user);
            }
            return new json(json::PARAMETER_ERROR, '注册失败');
        } else {
            return new json(json::PARAMETER_ERROR, '验证码错误');
        }
    }

    /**
     * 使用用户名和密码登陆
     */
    function login()
    {
        if (!empty($this->_response))
            return $this->_response;
        $telephone = $this->data('telephone', NULL, 'telephone');

        $password = $this->data('password');

        if (empty($telephone))
            return new json(json::PARAMETER_ERROR, '手机号码错误');
        $userData = $this->model('user')->getByTelephone($telephone);
        if (empty($userData))
            return new json(json::PARAMETER_ERROR, '用户尚未注册');
        $userHelper = new \application\helper\user();
        if ($userHelper->auth($password, $userData['password'], $userData['salt'])) {
            if ($userData['close'] == 1) {
                return new json(json::PARAMETER_ERROR, '账号已封');
            }
            $userHelper->saveUserSession($userData);
            $userHelper->protectedUser($userData);
            return new json(json::OK, NULL, $userData);
        }
        return new json(json::PARAMETER_ERROR, '密码错误');
    }

    /**
     * 用户关系绑定
     */
    function setOid()
    {
        if (!empty($this->_response))
            return $this->_response;

        $invit = $this->data('invit');
        if (empty($invit))
            return new json(json::PARAMETER_ERROR);
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $invit_user = $this->model('user')->where('invit=?', [$invit])->find();
        if (!empty($invit_user)) {
            if ($invit_user['vip'] == 0)
                return new json(json::PARAMETER_ERROR, '邀请码错误');
            if ($invit_user['master'] == 1) {
                $o_master = $invit_user['id'];
            } else {
                $o_master = $invit_user['o_master'];
            }

            $user = $this->model('user')->where('id=?', [$uid])->find();
            if (empty($user['oid'])) {
                if ($this->model('user')->where('id=?', [$uid])->update([
                    'oid' => $invit_user['id'],
                    'o_master' => $o_master,
                    'invittime' => $_SERVER['REQUEST_TIME']
                ])
                ) {
                    return new json(json::OK);
                } else {
                    return new json(json::PARAMETER_ERROR);
                }
            }
            return new json(json::PARAMETER_ERROR, '不要重复绑定邀请人');
        }
        return new json(json::PARAMETER_ERROR, '邀请码错误');
    }

    function profit()
    {
        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);
        $result = [];

        //昨日收益
        $y_profit_time_start = strtotime(date('Y-m-d', time() - 24 * 3600));
        $y_profit_time_end = $y_profit_time_start + 24 * 3600;
        $y_profit = $this->model('swift')
            ->where('source in (?)', [2, 3, 4, 5, 6, 7])
            ->where('time > ? and time < ?', [$y_profit_time_start, $y_profit_time_end])
            ->where('uid=?', [$uid])
            ->find('sum(money) as y_profit');
        $result['y_profit'] = empty($y_profit['y_profit']) ? 0 : $y_profit['y_profit'];

        //用户余额
        $user = $this->model('user')->where('id=?', [$uid])->find();
        $result['money'] = empty($user['money']) ? 0 : $user['money'];

        //累计收入
        $profit = $this->model('swift')
            ->where('uid=?', [$uid])
            ->where('type=?', [0])
            ->where('source in (?)', [2, 3, 4, 5, 6, 7])
            ->find('sum(money) as profit');
        $result['profit'] = empty($profit['profit']) ? 0 : $profit['profit'];

        //提现中
        $drawaling = $this->model('drawal')
            ->where('uid=?', [$uid])
            ->where('pass=?', [0])
            ->find('sum(money) as drawaling');
        $result['drawaling'] = empty($drawaling['drawaling']) ? 0 : $drawaling['drawaling'];

        //已提现
        $drawaled = $this->model('drawal')
            ->where('uid=?', [$uid])
            ->where('pass=?', [1])
            ->find('sum(money) as drawaled');
        $result['drawaled'] = empty($drawaled['drawaled']) ? 0 : $drawaled['drawaled'];

        //产品推广
        $product = $this->model('swift')
            ->where('uid=?', [$uid])
            ->where('source in (?)', [2, 3])
            ->find('sum(money) as product');
        $result['product'] = empty($product['product']) ? 0 : $product['product'];

        //平台推广
        $pintai = $this->model('swift')
            ->where('uid=?', [$uid])
            ->where('source in (?)', [5, 6])
            ->find('sum(money) as pintai');
        $result['pintai'] = empty($pintai['pintai']) ? 0 : $pintai['pintai'];

        //团队管理
        $team = $this->model('swift')
            ->where('uid=?', [$uid])
            ->where('source in (?)', [4, 7])
            ->find('sum(money) as team');
        $result['team'] = empty($team['team']) ? 0 : $team['team'];

        //7天收益
        $result['profit7'] = [];
        for ($i = 7; $i > 0; $i--) {
            $profit_time_start = strtotime(date('Y-m-d', time() - 24 * 3600 * $i));
            $profit_time_end = $profit_time_start + 24 * 3600;
            $profit = $this->model('swift')
                ->where('source in (?)', [2, 3, 4, 5, 6, 7])
                ->where('time > ? and time < ?', [$profit_time_start, $profit_time_end])
                ->where('uid=?', [$uid])
                ->find('sum(money) as profit');
            $key = date('m-d', $profit_time_start);
            $result['profit7'][$key] = empty($profit['profit']) ? 0 : $profit['profit'];
        }

        return new json(json::OK, NULL, $result);
    }

    function team()
    {
        if (!empty($this->_response))
            return $this->_response;

        $userHelper = new \application\helper\user();
        $uid = $userHelper->isLogin();
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        $name = $this->data('name', '');
        $whole = $this->data('whole', 0, 'intval');//是否是全部  否则是直属团队
        $vip = $this->data('vip', NULL);
        $sort = $this->data('sort', 'invittime');

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

    function teaminfo()
    {
        if (!empty($this->_response))
            return $this->_response;

        $id = $this->data('id');
        if (!empty($id)) {
            $return = [];

            $time = strtotime(date('Y-m-d'));
            $dayteam = $this->model('user')->where('oid=? and invittime>?', [$id, $time])->find('count(*)');
            $dayteam = isset($dayteam['count(*)']) && !empty($dayteam['count(*)']) ? $dayteam['count(*)'] : 0;
            $return['dayteam'] = $dayteam;

            $time = $_SERVER['REQUEST_TIME'] - 24 * 3600 * 7;
            $weekteam = $this->model('user')->where('oid=? and invittime>?', [$id, $time])->find('count(*)');
            $weekteam = isset($weekteam['count(*)']) && !empty($weekteam['count(*)']) ? $weekteam['count(*)'] : 0;
            $return['weekteam'] = $weekteam;

            $time = $_SERVER['REQUEST_TIME'] - 24 * 3600 * 30;
            $monthteam = $this->model('user')->where('oid=? and invittime>?', [$id, $time])->find('count(*)');
            $monthteam = isset($monthteam['count(*)']) && !empty($monthteam['count(*)']) ? $monthteam['count(*)'] : 0;
            $return['monthteam'] = $monthteam;

            $team = $this->model('user')->where('oid=?', [$id])->find('count(*)');
            $team = isset($team['count(*)']) && !empty($team['count(*)']) ? $team['count(*)'] : 0;
            $return['team'] = $team;

            $profit = $this->model('swift')->where('source in (?)', [2, 3, 4, 5, 6, 7])->where('uid=?', [$id])->find('sum(money)');
            $profit = isset($profit['sum(money)']) && !empty($profit['sum(money)']) ? $profit['sum(money)'] : 0;
            $return['profit'] = $profit;

            $time = strtotime(date('Y-m-d'));
            $dayprofit = $this->model('swift')->where('source in (?)', [2, 3, 4, 5, 6, 7])->where('uid=? and time>?', [$id, $time])->find('sum(money)');
            $dayprofit = isset($dayprofit['sum(money)']) && !empty($dayprofit['sum(money)']) ? $dayprofit['sum(money)'] : 0;
            $return['dayprofit'] = $dayprofit;

            $time = $_SERVER['REQUEST_TIME'] - 24 * 3600 * 7;
            $weekprofit = $this->model('swift')->where('source in (?)', [2, 3, 4, 5, 6, 7])->where('uid=? and time>?', [$id, $time])->find('sum(money)');
            $weekprofit = isset($weekprofit['sum(money)']) && !empty($weekprofit['sum(money)']) ? $weekprofit['sum(money)'] : 0;
            $return['weekprofit'] = $weekprofit;

            $time = $_SERVER['REQUEST_TIME'] - 24 * 3600 * 30;
            $monthprofit = $this->model('swift')->where('source in (?)', [2, 3, 4, 5, 6, 7])->where('uid=? and time>?', [$id, $time])->find('sum(money)');
            $monthprofit = isset($monthprofit['sum(money)']) && !empty($monthprofit['sum(money)']) ? $monthprofit['sum(money)'] : 0;
            $return['monthprofit'] = $monthprofit;

            $user = $this->model('user')->table('upload', 'left join', 'upload.id=user.gravatar')->where('user.id=?', [$id])->find([
                'user.name',
                'upload.path as gravatar',
                'user.vip'
            ]);

            $user['detail'] = $return;

            return new json(json::OK, NULL, $user);
        }
        return new json(json::PARAMETER_ERROR);
    }

    /**
     * 接口不存在错误
     * @return \application\message\json
     */
    function __404()
    {
        $response = [
            'code' => 404,
            'result' => '没有找到要调用的接口',
        ];
        return new json($response);
    }

    function getoid()
    {
        if (!empty($this->_response))
             return $this->_response;

            $invit = $this->data('invit');

        if ($invit) {

            $user = $this->model("user")
                ->table('upload', 'left join', 'user.gravatar=upload.id')
                ->where("invit=?", [$invit])
                ->find([
                    'user.name',
                    'user.invit',
                    'user.id',
                    'upload.path'
                ]);

            if ($user) {
                return new json(json::OK, NULL, $user);
            }
        }
        return new json(json::PARAMETER_ERROR, '没有找到对应导师');
    }


}