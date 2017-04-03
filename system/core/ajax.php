<?php
namespace system\core;

use application\message\json;

class ajax extends control
{
    /**
     * 响应内容
     * @var unknown
     */
    private $_response;

    /**
     *
     */
    function __construct()
    {
        parent::__construct();
        $this->init();
    }

    function init()
    {
        if (!$this->http->isAjax()) {
            header('Content-Type: application/json');
            exit(json_encode([
                'code' => 401,
                'result' => '非法请求',
            ], JSON_UNESCAPED_UNICODE));
        }

        $form = new form(config('form'));
        if (!$form->auth()) {
            header('Content-Type: application/json');
            exit(json_encode([
                'code' => 402,
                'result' => '请刷新重试',
            ], JSON_UNESCAPED_UNICODE));
        }
    }

    function __call($name, $args)
    {
        return new json(404, '请求的地址不存在');
    }
}