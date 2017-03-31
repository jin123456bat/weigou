<?php
namespace application\control\ajax;
use system\core\control;
use application\message\json;
use application\helper\admin;

class brand extends control
{
	function create()
	{
		$brand = new \application\entity\brand();
		$brand->setData($_POST);
		if ($brand->validate())
		{
			if($brand->save())
			{
				return new json(json::OK);
			}
			else
			{
				return new json(json::PARAMETER_ERROR,'添加失败');
			}
		}
		else
		{
			return new json(json::PARAMETER_ERROR,'添加失败',$brand->getErrors());
		}
	}
	
	function save()
	{
		$brand = new \application\entity\brand();
		$brand->setData($_POST);
		$brand->addData('modifytime', date('Y-m-d H:i:s'));
		if ($brand->validate())
		{
			if($brand->save())
			{
				return new json(json::OK);
			}
			else
			{
				return new json(json::PARAMETER_ERROR,'保存失败');
			}
		}
		else
		{
			return new json(json::PARAMETER_ERROR,'保存失败',$brand->getErrors());
		}
	}
	
	function remove()
	{
		$id = $this->post('id',0,'intval');
		if($this->model('brand')->where('id=?',[$id])->limit(1)->delete())
		{
			return new json(json::OK);
		}
		else
		{
			return new json(json::PARAMETER_ERROR);
		}
	}
	
	function __access()
	{
		$adminHelper = new \application\helper\admin();
		return array(
			array(
				'allow',
				'actions' => ['remove','create'],
				'express' => empty($adminHelper->getAdminId()),
				'message' => new json(json::NOT_LOGIN,'请重新登录'),
				'httpCode' => 200,
			),
			array(
				'allow',
				'actions' => '*',
			)
		);
	}
}