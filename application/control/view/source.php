<?php
namespace application\control\view;

use system\core\view;

class source extends view
{
    function __construct()
    {
        parent::__construct();
        if ($this->get('a') == 'index') {
            if ($this->session->role == 'source') {
                $uid = $this->session->id;
            }
            if (!isset($uid) || empty($uid)) {
                $this->setViewname('login');
            } else {
                $source = $this->model('source')->where('id=?', [$uid])->find();
                $this->assign('source', $source);
            }
        }
    }

    function enter()
    {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $ip = ip();
        if (!empty($user_agent)) {
            $id = $this->get('id', NULL, 'intval');
            if (!empty($id)) {
                $this->session->user_source = $id;
            }
            if (!empty($id) && !isset($_COOKIE['source_time'])) {
                $this->cookie->setExpire(315360000);
                $this->cookie->setHttpOnly(true);

                $this->cookie->source_time = $_SERVER['REQUEST_TIME'];
                $this->session->user_source = $id;
                $data = [
                    'source' => $id,
                    'ip' => $ip,
                    'time' => $_SERVER['REQUEST_TIME'],
                    'user_agent' => $user_agent,
                ];
                $this->model('source_log')->insert($data);
            }
        }
        if ($id == '36') {//这个用户想跳到别的分类页面
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('', 'mobile', 'category', array('user_source' => $id)));
        } else {
            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('', 'mobile', 'index', array('user_source' => $id)));
        }
    }

    function eqcode()
    {
        $id = $this->get('id');
        if (!empty($id)) {
            $content = $this->http->url('', 'source', 'enter', ['id' => $id]);


            $image = new \system\core\image();
            $size = empty($this->get->size) ? 4 : intval($this->get->size);
            $blank = empty($this->get->blank) ? 2 : intval($this->get->blank);

            $logo = $this->model('system')->get('logo', 'system');
            $logo = is_file($logo) ? $logo : NULL;

            $file = $image->QRCode($content, $logo, 'M', $size, $blank);
            $this->response->addHeader('Cache-Control', 'cache-directive');
            $this->response->addHeader('cache-directive', 'public');
            $this->response->addHeader('Content-Type', 'image/png');
            if ($this->get->download == 'true') {
                $this->response->addHeader('Content-Disposition', 'attachment; filename="eqcode.png"');
            }
            return file_get_contents($file);
        }
    }

    function index()
    {

        $data = $this->model('source')
            ->table('user', 'left join', 'source.uid=user.id')
            ->where('source.id=?', [$this->session->id])
            ->find([
                'source.id',
                'source.uid',
                'user.money',    //余额
                'IFNULL((select sum(a.money)-(select IFNULL(sum(b.money),"0.00") from swift as b where b.uid=user.id and b.type=1 and b.source = 8) from swift as a where a.uid=user.id and a.type=0 and a.source in (2,3,4,5,6,7)),"0.00") as get_money', //收益
                '(select count(*) from source as bsource where bsource.u_source=source.id and bsource.isdelete=0) as u_source',        //子渠道数
                '(select count(*) from user as buser where buser.source=source.id or buser.o_master=source.uid) as user_count',  //用户数量
                'source.type'
            ]);
        $data['get_money'] = sprintf("%.2f", $data['get_money']);
        $this->assign('data', $data);
        $this->assign('_id', $this->session->id);

        return $this;
    }

    function source_user()
    {
        $data = $this->model('source')->where('source.id=?', [$this->session->id])->find(['type']);
        $this->assign('data', $data);
        return $this;
    }

    function source_order()
    {
        $data = $this->model('source')->where('source.id=?', [$this->session->id])->find(['type']);
        $this->assign('data', $data);
        return $this;
    }

    function source_viporder()
    {
        $data = $this->model('source')->where('source.id=?', [$this->session->id])->find(['type']);
        $this->assign('data', $data);
        return $this;
    }

    function source_under()
    {
        $data = $this->model('source')->where('source.id=?', [$this->session->id])->find(['type']);
        $this->assign('data', $data);
        return $this;
    }

    function source_source()
    {

        $data = $this->model('source')->where('source.id=?', [$this->session->id])->find(['type']);
        $this->assign('data', $data);
        $power = $this->model('source')->where('id=?', [$this->session->id])->find();

         if (!empty($power['u_source']) || $power['type'] == 1) {
             $this->setViewname('nopower');
         }

        $source = $this->model('source')->where('isdelete=? and u_source=?', [0, $this->session->id])->select();
        $this->assign('power', $power);
        $this->assign('source', $source);
        $this->assign('ctype', $power['type']);
        return $this;
    }

}
