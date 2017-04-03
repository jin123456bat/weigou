<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
use application\helper\admin;

/**
 * @author jin12
 *
 */
class product_notice_template extends ajax
{
	/**
	 * 创建短信模板
	 */
	function create()
	{
		$title = $this->post('title','','htmlspecialchars');
		$content = $this->post('content','','htmlspecialchars');
		
		$adminHelper = new admin();
		$aid = $adminHelper->getAdminId();
		if($this->model('product_notice_template')->insert([
			'aid' => $aid,
			'title' => $title,
			'content' => $content,
			'createtime' => $_SERVER['REQUEST_TIME'],
			'modifytime' => $_SERVER['REQUEST_TIME'],
			'host' => 0,
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	function save()
	{
		$id = $this->post('id',0,'intval');
		$title = $this->post('title','','htmlspecialchars');
		$content = $this->post('content','','htmlspecialchars');
		
		$adminHelper = new admin();
		$aid = $adminHelper->getAdminId();
		if($this->model('product_notice_template')->where('id=?',[$id])->update([
			'aid' => $aid,
			'title' => $title,
			'content' => $content,
			'modifytime' => $_SERVER['REQUEST_TIME'],
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	function remove()
	{
		$id = $this->post('id',0,'intval');
		if($this->model('product_notice_template')->where('id=?',[$id])->limit(1)->delete())
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	/**
	 * 设置默认短信模板
	 * @return \application\message\json
	 */
	function host()
	{
		$id = $this->post('id',0,'intval');
		$this->model('product_notice_template')->transaction();
		$this->model('product_notice_template')->where('host=?',[1])->limit(1)->update([
			'modifytime' =>$_SERVER['REQUEST_TIME'],
			'host'=>0,
		]);
		$this->model('product_notice_template')->where('id=?',[$id])->update([
			'modifytime'=>$_SERVER['REQUEST_TIME'],
			'host'=>1,
		]);
		$this->model('product_notice_template')->commit();
		return new json(json::OK);
	}
	
	function __access()
	{
		$adminHelper = new admin();
		return array(
			array(
				'deny',
				'actions' => ['host','create'],
				'message' => new json(json::NOT_LOGIN),
				'express' => empty($adminHelper->getAdminId()),
				'redict' => './index.php?c=admin&a=login',
			),
		);
	}
}