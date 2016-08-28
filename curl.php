<?php
/**
 * Created by PhpStorm.
 * User: copy
 * Date: 16-8-8
 * Time: 下午2:01
 */

/*

for ($i = 0; $i <= 10000000; $i++) {
    $a = $i * $i;
}
*/


//$XHPROF_ROOT = "/tools/xhprof/";
//include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
//include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";

//$xhprof_runs = new XHProfRuns_Default();
//$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_testing");

//echo "http://localhost/xhprof/xhprof_html/index.php?run={$run_id}&source=xhprof_testing\n";


$url = 'http://127.0.0.1/index.php?m=api&c=product&a=detail';
$partner = 'ios';
$key = "ios";
$posts = array(

    'partner' => $partner

);
$fields = array(
    "telephone" => '13373902670',
    'password' => '123456',
    //'address' => 579,
    //'prepay' => 0,
    //'tid' => 37
    'id' => '60',
    //'invit' => 'Hqhpln',
    //'position' => 'index'
    // 'orderno' => '16081715511619461182'

);
//排序
asort($fields);
//数组拼接成字符串
$str = "";

foreach ($fields as $k => $v) {
    $str .= $k . "=" . $v . "&";
    $posts["data[$k]"] = $v;
}
$str = rtrim($str, "&");
$str .= $partner . $key;
$str = strtoupper(md5($str));

//$posts['data'] = $data;
$posts['sign'] = $str;


//拼接上partner和key参数
//md5 转大写
//生成参数


//$post_data = implode('&',$fields);

//open connection
$ch = curl_init();
//set the url, number of POST vars, POST data
curl_setopt($ch, CURLOPT_URL, $url);

curl_setopt($ch, CURLOPT_POST, true);


curl_setopt($ch, CURLOPT_POSTFIELDS, $posts); // 在HTTP中的“POST”操作。如果要传送一个文件，需要一个@开头的文件名

ob_start();
curl_exec($ch);
$result = ob_get_contents();
ob_end_clean();

echo $result;

//close connection
curl_close($ch);
exit;
/*
$url = 'http://apis.haoservice.com/efficient/education';

$posts=array(
    "key"=>'5c45c8c5e41d46dcb4c2fab69629151c',
    'idcard'=>'330881199311270534',
    'realname'=>'余康明');
$ch = curl_init();
//set the url, number of POST vars, POST data
curl_setopt($ch, CURLOPT_URL, $url);

curl_setopt($ch, CURLOPT_POST, true);


curl_setopt($ch, CURLOPT_POSTFIELDS, $posts); // 在HTTP中的“POST”操作。如果要传送一个文件，需要一个@开头的文件名

ob_start();
curl_exec($ch);
$result = ob_get_contents();
ob_end_clean();

echo $result;

//close connection
curl_close($ch);
*/


/*
$numbers = range(1, 50);
//shuffle 将数组顺序随即打乱
shuffle($numbers);
//array_slice 取该数组中的某一段
$num = 6;
$result = array_slice($numbers, 0, $num);
print_r($result);

*/
$tmp = array();
while (count($tmp) < 5) {
    $tmp[] = mt_rand(1, 20);
    $tmp = array_unique($tmp);
}


//$handle = kadm5_init_with_password("afs-1", "GONICUS.LOCAL", "admin/admin", "password");

//kadm5_chpass_principal($handle, "burbach@GONICUS.LOCAL", "newpassword");
//
//kadm5_destroy($handle);

//echo sha1("123");
echo $hash = password_hash("123456", PASSWORD_BCRYPT);

//echo password_verify("123456789",$hash);
$start_time = microtime(true);

if (password_verify('123456', $hash)) {
    echo 'Password is valid!';
} else {
    echo 'Invalid password.';
}

$end_time = microtime(true);
echo "页面执行时间: " . round(($end_time - $start_time) * 1000, 1) . " 毫秒<br />";

//$xhprof_data = xhprof_disable();

//json_encode($xhprof_data);