<?php
namespace system\core;

/**
 * http管理
 *
 * @author 程晨
 */
class http
{
    static private $_instance = NULL;

    private function __construct()
    {

    }

    static public function getInstance()
    {
        if (self::$_instance === NULL)
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * 获取当前url或者根据设置的控制器名称，方法名称，以及其他参数生成一个url
     *
     * @param string $c
     *            控制器名称
     * @param string $a
     *            方法名
     * @param string $array
     *            get参数
     * @param string $query
     *            #后面的内容
     * @return string
     */
    function url($m = NULL, $c = NULL, $a = NULL, $array = array(), $query = NULL)
    {
        $protocal = (isset($_SERVER['https']) ? 'https://' : 'http://');
        $port = isset($_SERVER['https']) ? (($this->port() == 443) ? '' : ':' . $this->port()) : (($this->port() == 80) ? '' : ':' . $this->port());
        if ($c === NULL && $a === NULL && $m === NULL) {
            return $protocal . $this->host() . $port . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        } else {
            $config = config('system', true);
            switch ($config['pathmode']) {
                case 'pathinfo':
                    $parameter = empty($m) ? '' : ('/' . urlencode($m)) . empty($c) ? '' : ('/' . urlencode($c)) . empty($a) ? '' : ('/' . urlencode($a));
                    foreach ($array as $key => $value) {
                        $parameter .= '/' . $key . '/' . urlencode($value);
                    }
                default:
                    $temp = [];
                    if (!empty($m))
                        $temp['m'] = $m;
                    if (!empty($c))
                        $temp['c'] = $c;
                    if (!empty($a))
                        $temp['a'] = $a;
                    $parameter = http_build_query(array_merge($temp, $array));
            }
            $query = ($query === NULL) ? '' : '#' . $query;
            return $protocal . $this->host() . $port . $_SERVER['PHP_SELF'] . (empty($parameter) ? '' : '?') . $parameter . $query;
        }
    }

    /**
     * 当前请求方式
     *
     * @return GET|POST|DELETE|PUT
     */
    function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * 判断是否是websocket连接
     * @return boolean
     */
    function isWebsocket()
    {
        return isset($_SERVER['HTTP_UPGRADE']) && isset($_SERVER['HTTP_CONNECTION']) && strtolower($_SERVER['HTTP_CONNECTION']) == 'upgrade' && strtolower($_SERVER['HTTP_UPGRADE']) == 'websocket';
    }

    /**
     * 请求时间
     *
     * @return string unix时间戳
     */
    public static function time()
    {
        return $_SERVER['REQUEST_TIME'];
    }

    /**
     * 主机名
     *
     * @return string
     */
    function host()
    {
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * 主机ip
     *
     * @return string IPv4地址
     */
    function ip($host = NULL)
    {
        $host = ($host === NULL) ? $this->host() : $host;
        return gethostbyname($host);
    }

    /**
     * 端口号
     *
     * @return string
     */
    function port()
    {
        return $_SERVER['SERVER_PORT'];
    }

    /**
     * 页面跳转
     * @param unknown $url
     */
    function jump($url, $code = 302)
    {
        header('Location: ' . $url, $code, true);
    }

    /**
     * 协议
     *
     * @return HTTP /1.1
     */
    function isHttps()
    {
        return ($this->port() == 443 || isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ? true : false;
    }

    /**
     * 上一个页面地址
     *
     * @return string|null
     */
    function referer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL;
    }

    /**
     * 获取自定义的header
     */
    function getHeader($name)
    {
        return isset($_SERVER[$name]) ? $_SERVER[$name] : NULL;
    }

    /**
     * 判断是ajax请求
     * @return boolean
     */
    function isAjax()
    {
        return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest";
    }

    /**
     * 判断是否是手机浏览
     * @return boolean
     */
    function isMobile()
    {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA'])) {
            //找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        //判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array(
                'nokia',
                'sony',
                'ericsson',
                'mot',
                'samsung',
                'htc',
                'sgh',
                'lg',
                'sharp',
                'sie-',
                'philips',
                'panasonic',
                'alcatel',
                'lenovo',
                'iphone',
                'ipod',
                'blackberry',
                'meizu',
                'android',
                'netfront',
                'symbian',
                'ucweb',
                'windowsce',
                'palm',
                'operamini',
                'operamobi',
                'openwave',
                'nexusone',
                'cldc',
                'midp',
                'wap',
                'mobile'
            );
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        //协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }
    }

    /**
     * 手机类型
     * @return ios|android|other
     */
    function mobileType()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')) {
            return "ios";
        } else if (strpos($_SERVER['HTTP_USER_AGENT'], 'Android')) {
            return "android";
        } else {
            return "other";
        }
    }

    /**
     * 浏览器信息
     *
     * @return mixed
     */
    function agent()
    {
        return get_browser(NULL, true);
    }

    /**
     * 脚本路径
     * @return unknown
     */
    function path()
    {
        return pathinfo($_SERVER['PHP_SELF'], PATHINFO_DIRNAME);
    }

    static function get($url)
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            return curl_exec($curl);
        }
        return file_get_contents($url);
    }

    static function post($url, $data)
    {
        if (!function_exists('curl_init')) {
            if (is_array($data)) {
                $data = http_build_query($data);
            } else if (is_object($data)) {
                $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            }

            $context = array(
                'http' => array(
                    'method' => "POST",
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n" . "Content-length:" . strlen($data) . "\r\n",
                    'content' => $data
                )
            );
            $context = stream_context_create($context);
            return file_get_contents($url, NULL, $context);
        } else {
            if (is_array($data)) {
                foreach ($data as $index => &$file) {
                    if (isset($file[0]) && $file[0] == '@') {
                        //mb php5.5不支持@
                        $file = new \CURLFile(substr($file, 1));
                    }
                }
            }
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $result = curl_exec($curl);
            return $result;
        }
    }
}