<?php
namespace application\control\form;
use system\core\control;
use system\core\form;
class question extends control
{
	function create()
	{
		$form = new form(config('form'));
		if ($form->auth())
		{
			$title = $this->post('title','');
			if (!empty($title))
			{
				$sort = $this->post('sort',0,'intval');
				$answer = $this->post('answer','','trim');
				$cid = $this->post('cid',0,'intval');
				if($this->model('question')->insert([
					'title' => $title,
					'sort' => $sort,
					'answer' => $answer,
					'isdelete' => 0,
					'deletetime' => 0,
					'createtime' => $_SERVER['REQUEST_TIME'],
					'modifytime' => $_SERVER['REQUEST_TIME'],
					'cid' => $cid,
				]))
				{
					$this->response->setCode(302);
					$this->response->addHeader('Location',$this->http->url('','admin','question'));
				}
			}
			else
			{
				$this->response->setCode(302);
				$this->response->addHeader('Location',$this->http->url('','admin','question'));
			}
		}
	}
	
	function save()
	{
		$form = new form(config('form'));
		if ($form->auth())
		{
			$id = $this->post('id');
			if (!empty($id))
			{
				$title = $this->post('title','');
				$sort = $this->post('sort',0,'intval');
				$cid = $this->post('cid',0,'intval');
				$answer = $this->post('answer','','trim');
				if($this->model('question')->where('id=?',[$id])->update([
					'title' => $title,
					'sort' => $sort,
					'answer' => $answer,
					'cid' => $cid,
					'modifytime' => $_SERVER['REQUEST_TIME'],
				]))
				{
					$this->response->setCode(302);
					$this->response->addHeader('Location',$this->http->url('','admin','question'));
				}
			}
		}
	}
	
	function create_category()
	{
		$form = new form(config('form'));
		if ($form->auth())
		{
			$id = $this->post('id',0,'intval');
			$title = $this->post('title','','trim');
			$sort = $this->post('sort',0,'intval');
			if (empty($id))
			{
				if($this->model('question_category')->insert([
					'title' => $title,
					'isdelete' => 0,
					'deletetime' => 0,
					'sort' => 0,
					'createtime' => $_SERVER['REQUEST_TIME']
				]))
				{
					$this->response->setCode(302);
					$this->response->addHeader('Location',$this->http->url('','admin','question'));
				}
			}
			else
			{
				if($this->model('question_category')->where('id=?',[$id])->update([
					'title' => $title,
					'sort' => $sort,
				]))
				{
					$this->response->setCode(302);
					$this->response->addHeader('Location',$this->http->url('','admin','question'));
				}
			}
		}
	}
}