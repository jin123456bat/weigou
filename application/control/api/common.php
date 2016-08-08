<?php
namespace application\control\api;
use system\core\api;
use application\message\json;
use system\core\image;
class common extends api
{
	function __construct()
	{
		parent::__construct(config('api'));
	}
	
	/**
	 * 初始化，验证失败返回的错误信息
	 */
	function init()
	{
		switch ($this->auth())
		{
			case 1:return new json(401,'参数未找到');
			case 2:return new json(402,'partner不存在');
			case 3:return new json(403,'签名失败');
			default:
		}
	}
	
	
	/**
	 * 文件上传
	 */
	function upload()
	{
		$file = $this->file->file;
		if (is_file($file))
		{
			$width = $this->post('width',NULL);
			$height = $this->post('height',NULL);
			
			if (!empty($width) && !empty($height))
			{
				$image = new image();
				$new_file = $image->resizeImage($file, $width, $height);
				unlink($file);
				$file = $new_file;
			}
			
			$type = strtolower(pathinfo($file,PATHINFO_EXTENSION));
			$name = strip_tags(trim(str_replace('\'', '', $_FILES['file']['name'])));
			$size = $_FILES['file']['size'];
			
			$fileData = [
				'id' => NULL,
				'name' => $name,
				'type' => $type,
				'path' => $file,
				'time' => $_SERVER['REQUEST_TIME'],
				'size' => $size,
			];
			if($this->model('upload')->insert($fileData))
			{
				$fileData['id'] = $this->model('upload')->lastInsertId();
				return new json(json::OK,NULL,$fileData);
			}
			return new json(json::PARAMETER_ERROR,'文件记录失败');
		}
		return new json(json::PARAMETER_ERROR,'文件上传失败:错误代码'.$file);
	}
	
	function address()
	{
		$type = $this->get('type','province');
		switch ($type)
		{
			case 'province':
				return new json(json::OK,NULL,$this->model('province')->select());
				break;
			case 'city':
				$pid = $this->get('pid');
				if (empty($pid))
					return new json(json::PARAMETER_ERROR,'没有传入pid参数');
				return new json(json::OK,NULL,$this->model('city')->where('pid=?',[$pid])->select());
			case 'county':
				$cid = $this->get('cid');
				if (empty($cid))
					return new json(json::PARAMETER_ERROR,'没有传入cid参数');
				return new json(json::OK,NULL,$this->model('county')->where('cid=?',[$cid])->select());
		}
		return new json(json::PARAMETER_ERROR,'type参数错误');
	}
	
	/**
	 * 获取用户二维码
	 */
	function eqcode()
	{
		$userHelper = new \application\helper\user();
		$uid = $userHelper->isLogin();
		if(empty($uid))
			return new json(json::NOT_LOGIN);
	
		$content = $this->http->url('','mobile','index',['share_uid'=>$uid]);
		
		$image = new image();
		$size = empty($this->get->size)?4:intval($this->get->size);
		$blank = empty($this->get->blank)?2:intval($this->get->blank);
	
		$logo = $this->model('system')->get('logo','system');
		$logo = is_file($logo)?$logo:NULL;
	
		$file = $image->QRCode($content,$logo,'M',$size,$blank);
		$this->response->addHeader('Content-Type','image/png');
		if($this->get->download == 'true')
		{
			$this->response->addHeader('Content-Disposition','attachment; filename="eqcode.png"');
		}
		return file_get_contents($file);
	}
	
	function city()
	{
		$obj = new \stdClass();
		$obj->citylist = $this->model('province')->select('id,name as p');
		foreach ($obj->citylist as &$province)
		{
			$province['c'] = $this->model('city')->where('pid=?',[$province['id']])->select('id,name as n');
			foreach($province['c'] as &$city)
			{
				$city['a'] = $this->model('county')->where('cid=?',[$city['id']])->select('id,name as s');
			}
		}
		return new json($obj);
	}
}