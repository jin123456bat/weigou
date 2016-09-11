<?php
/**
 * Created by PhpStorm.
 * User: copy
 * Date: 16-8-8
 * Time: 下午2:01
 */


$url = 'http://test.twillg.com/index.php?m=api&c=cart&a=lists';
$partner = 'ios';
$key = "ios";
$posts = array(
    "partner" => $partner
);
$fields = array(
    "address" => 483,
    "clear" => 1,
    "prepay" => 0,
    "product" => json_encode(array([
        "id"=>61,
        "num"=>1,
        "content"=>'',
        "bind"=>1
    ],[
        "id" => 60,
        "num" => 2,
        "content" => '紫色',
        "bind" => 2
    ]))


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