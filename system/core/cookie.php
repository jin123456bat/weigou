<?php
namespace system\core;

use system\core\inter\config;

class cookie
{
    static private $_instance;

    private $_config;

    static function getInstance()
    {
        if (empty(self::$_instance))
            self::$_instance = new self(config('cookie'));
        return self::$_instance;
    }

    function __construct(config $config)
    {
        $this->_config = $config;
    }

    function __set($name, $value)
    {
        if (!is_object($value)) {
            setcookie($name, $value, $this->_config->expire + $_SERVER['REQUEST_TIME'], $this->_config->path, $this->_config->domain, $this->_config->secure, $this->_config->httponly);
        }
    }

    function __get($name)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : NULL;
    }

    function __isset($name)
    {
        return isset($_COOKIE[$name]) && !empty($_COOKIE[$name]);
    }

    function __unset($name)
    {
        setcookie($name, '', $_SERVER['REQUEST_TIME'] - 1);
    }

    function __call($function_name, $args)
    {
        if (substr($function_name, 0, 3) == 'set') {
            $name = strtolower(substr($function_name, 3));
            $this->_config->$name = $args[0];
        }
    }
}