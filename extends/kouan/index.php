<?php
//header('Content-Type: "text/xml;charset=utf-8"');
!defined('KOUAN_ROOT') && define('KOUAN_ROOT', dirname(__FILE__));

array_map(function($dir){
	if($dir != '.' && $dir != '..')
	{
		include KOUAN_ROOT.'/function/'.$dir;
	}
},scandir(KOUAN_ROOT.'/function/'));



spl_autoload_register(function ($name)
{
	$path = realpath(KOUAN_ROOT.'/lib/core/'.$name.'.php');
	if(!empty($path))
		include_once $path;
	$path = realpath(KOUAN_ROOT.'/lib/'.$name.'.class.php');
	if(!empty($path))
		include_once $path;
	$path = realpath(KOUAN_ROOT.'/data/'.$name.'.php');
	if(!empty($path))
		include_once $path;
});
?>