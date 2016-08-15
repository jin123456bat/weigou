<?php
namespace system\core;

use system\core\inter\config;

/**
 * 生成form表单的hidden字段
 * @author jin12
 *
 */
class form extends base
{
    private $_config;

    private $_avaliable;

    /**
     * 构造一个form表单
     * @param config $config
     */
    function __construct(config $config)
    {
        parent::__construct();
        $this->_config = $config;
    }

    /**
     * 判断当前表单提交的内容是否属实
     */
    function auth()
    {
        $result = false;
        if ($this->_config->csrf) {
            //token验证
            $result1 = ($this->post->X_CSRF_TOKEN === $this->session->x_csrf_token) || ($this->http->getHeader('HTTP_X_CSRF_TOKEN') === $this->session->x_csrf_token);

            //refer验证
            if ($this->http->referer() === NULL)
                return false;
            $url = parse_url($this->http->referer(), PHP_URL_HOST);
            $result2 = $url === $_SERVER['HTTP_HOST'];
            if ($result1 && $result2)
                $result = true;
        }
        //表单重复提交验证
        if (!$this->_config->repeat) {
            if (json_encode($_POST) !== $this->session->form_repeat) {
                $result = true;
            }
            $this->session->form_repeat = json_encode($_POST);
        }
        //表单频繁提交
        if (!empty($this->_config->frequent)) {
            if (!empty($_POST)) {
                if ($_SERVER['REQUEST_TIME'] - $this->session->form_frequent > $this->_config->frequent) {
                    $result = true;
                    $this->session->form_frequent = $_SERVER['REQUEST_TIME'];
                }
            }
        }
        return $result;
    }

    /**
     * 生成表单的隐藏字段 防止crsf攻击
     */
    function x_csrf_token()
    {
        $token = random::word(16);
        $this->session->x_csrf_token = $token;
        return $this->session->x_csrf_token;
    }
}