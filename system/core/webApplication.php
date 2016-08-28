<?php
namespace system\core;
/**
 * 应用程序类
 *
 * @author 程晨
 */
class webApplication extends base
{
    private $_config;

    function __construct($config)
    {
        $this->_config = $config;
        parent::__construct();

        date_default_timezone_set($this->_config['timezone']);

        if ($this->_config->debug) {
            $this->debug_mode();
        }
    }

    function debug_mode()
    {
        ini_set('display_errors', 'On');
    }

    /**
     * 入口点
     */
    function run()
    {

        $response = new response();
        $cacheConfig = config('cache');

        if ($cacheConfig['cache']) {
            $cache = cache::getInstance($cacheConfig);
            $content = $cache->check($this->http->url());
            if (!empty($content)) {
                $response->setBody($content);
                $response->send();
            }
        }

        try {
            $handler = $this->parseUrl();
            if (is_array($handler)) {
                list ($module, $control, $action) = $handler;
                $path = ROOT . '/application/control/' . $module . '/' . $control . '.php';
                if (file_exists($path)) {
                    include $path;
                    $class = 'application\\control\\' . $module . '\\' . $control;
                    if (class_exists($class)) {
                        $class = new $class();
                        $class->response = &$response;
                        if ((method_exists($class, $action) && is_callable(array($class, $action))) || method_exists($class, '__call')) {
                            if ($this->http->isWebsocket()) {
                                //对于websocket使用101响应
                                $response->setCode(101);
                                $response->setBody($this->__101($module, $class, $action));
                            } else {

                                $response->setCode(200);
                                $response->setBody($this->__200($module, $class, $action));


                            }
                        } else {
                            $response->setCode(404);
                            $response->setBody($this->__404($module, $control, $action));
                        }
                    } else {
                        $response->setCode(404);
                        $response->setBody($this->__404($module, $control, $action));
                    }
                } else {
                    $response->setCode(404);
                    $response->setBody($this->__404($module, $control, $action));
                }
            } else {
                include ROOT . '/application/thread/' . $handler . '.php';
                $class = 'application\\thread\\' . $handler . 'Thread';
                $class = new $class();
                $class->run();
            }
        } catch (\Exception $e) {
            $response->setCode(500);
            $response->setBody($this->__500($e));
        } finally {


            $response->send();
        }
    }

    /**
     * Url解析
     */
    function parseUrl()
    {
        switch ($this->_config['pathmode']) {
            case 'pathinfo':
                $pos = stripos($_SERVER['REQUEST_URI'], '.php/');
                if ($pos) {
                    $path = substr($_SERVER['REQUEST_URI'], $pos + 5);
                    $path = explode('/', $path);
                    $module = !isset($path[0]) || empty($path[0]) ? $this->_config['default_module'] : $path[0];
                    $control = !isset($path[1]) || empty($path[1]) ? $this->_config['default_control'] : $path[1];
                    $action = !isset($path[2]) || empty($path[2]) ? $this->_config['default_action'] : $path[2];
                    for ($i = 3; $i < count($path); $i += 2) {
                        $_GET[$path[$i]] = $path[$i + 1];
                        $_REQUEST[$path[$i]] = $path[$i + 1];
                    }
                } else {
                    $module = $this->_config['default_module'];
                    $control = $this->_config['default_control'];
                    $action = $this->_config['default_action'];
                }
                $_GET['m'] = $module;
                $_GET['c'] = $control;
                $_GET['a'] = $action;
                break;
            default:
                $module = empty($this->get->m) ? $this->_config['default_module'] : $this->get->m;
                $control = empty($this->get->c) ? $this->_config['default_control'] : $this->get->c;
                $action = empty($this->get->a) ? $this->_config['default_control'] : $this->get->a;
        }

        return array(
            $module,
            $control,
            $action
        );
    }

    /**
     * 200响应
     *
     * @param unknown $control
     * @param unknown $action
     */
    function __200($module, $control, $action)
    {

        return $control->$action();

    }

    /**
     * 基于websocekt通信协议
     */
    function __101($module, $control, $action)
    {
        return $control->$action();
    }

    /**
     * 404响应
     *
     * @param unknown $control
     * @param unknown $action
     */
    function __404($module, $control, $action)
    {
        //调用模块内的404
        $path = ROOT . '/application/control/' . $module . '/' . $control . '.php';
        if (realpath($path) && file_exists(realpath($path))) {
            include_once $path;
            $class = 'application\\control\\' . $module . '\\' . $control;
            if (class_exists($class)) {
                $control = new $class;
                if (method_exists($control, '__404') || method_exists($control, '__call')) {
                    return $control->__404();
                }
            }
        } else if ($control != $this->_config->default_control) {
            //主模块的404
            $index404 = $this->__404('view', 'index', '__404');
            if (!empty($index404))
                return $index404;
        }
        //系统404
        $view = new view(config('view', true), '404.html');
        return $view->display();
    }

    /**
     * 500响应
     *
     * @param \Exception $e
     */
    function __500(\Exception $e)
    {
        if ($this->_config->debug) {
            echo $e;
        }
        echo "服务器内部错误";
        exit();
    }
}