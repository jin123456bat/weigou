<?php
/**
 * Created by PhpStorm.
 * User: copy
 * Date: 16-8-8
 * Time: 下午2:01
 */
$array = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
for($a='A';$a<='R';$a++){
    $index = array_search($a, $array);
    $sum += ($index + 1) * pow(26, 0);
    echo $sum."<br />";
}
exit;
$a = array('apple'=>1, 'banana'=>2, 'orange'=>3);
var_dump($a);

array_splice($a, 2, 0, array("dfa"=>'23'));
var_dump($a);exit;

$url = 'http://127.0.0.1/index.php?m=api&c=category&a=product';
$partner = 'ios';
$key = "ios";
$posts = array(
    "partner" => $partner
);
$fields = array(
    "id"=>"17",
    "telephone"=>'13067778316',
    'password'=>'123456',
    "orderno"=>'16090916193319463200',
    "start"=>0,
    "length" => 10,
    "address" => 483,
    "clear" => 0,
    "prepay" => 1,
    "product" => json_encode(array([
        "id"=>68,
        "num"=>10,
        "content"=>'',
        "bind"=>1
    ],[
        "id" => 1518,
        "num" => 10,
        "content" => '',
        "bind" => 2
    ]
    , [
            "id" => 1518,
            "num" => 10,
            "content" => '',
            "bind" => 3
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