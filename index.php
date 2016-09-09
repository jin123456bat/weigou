<?php

//xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);

use system\core\webApplication;

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    $data = '';
    $data .= '[' . date('Y-m-d H:i:s') . ']';
    $data .= '(' . $errno . ')errstr:' . $errstr . "\n";
    $data .= 'errfile:' . $errfile . " on line (" . $errline . ")\n";
    file_put_contents('./error.log', $data, FILE_APPEND);
});

/* 定义根目录 */
defined('ROOT') or define('ROOT', str_replace('\\', '/', __DIR__));
/* 导入global向导 */

require_once ROOT . '/global.php';

$config = config('system');
(new webApplication($config))->run();


