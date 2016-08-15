<?php
namespace system\core;

use system\core\inter\config;

class api extends base
{
    private $_config;

    private $_partner;

    private $_key;

    function __construct(config $config)
    {
        parent::__construct();
        $this->_config = $config;
    }

    /**
     * 获取传递过来的data中的参数
     * @param unknown $name
     * @param string $default
     * @param string $filter
     * @return string
     */
    function data($name, $default = NULL, $filter = NULL)
    {
        $data = $this->post('data');
        if (is_callable($filter)) {
            return isset($data[$name]) ? $filter($data[$name]) : $default;
        }
        return isset($data[$name]) ? $data[$name] : $default;
    }

    /**
     * 更改api配置
     * @param config $config
     */
    function config(config $config)
    {
        $this->_config = $config;
    }

    /**
     * 对于请求的验证
     * @return 1:partner参数不存在  0签名成功  2签名失败
     */
    function auth()
    {
        if (empty($this->post('data')))
            return 1;
        $partner = $this->post('partner', NULL);
        if (empty($partner))
            return 2;
        $key = $this->_config->_user[$partner];
        if (empty($key))
            return 2;
        if (in_array($this->post->sign, $this->sign($this->http->url(), $this->post('data'), $partner, $key), true)) {
            return 0;
        }
        return 3;
    }

    /**
     * 签名验证
     * @param unknown $url
     * @param unknown $data
     * @param unknown $partner
     * @param unknown $key
     * @return string
     */
    private function sign($url, $data, $partner, $key)
    {
        if (is_callable(array($this->_config, 'sign'))) {
            return $this->_config->sign($url, $data, $partner, $key);
        }
        return md5($url . json_encode($_POST) . $key);
    }
}