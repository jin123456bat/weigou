<?php
namespace application\control\form;
use system\core\control;
use system\core\form;
class page extends control
{
	function createPage()
	{
		$form = new form(config('form'));
		if($form->auth())
		{
			$title = $this->post('title','');
			$author = $this->post('author','');
			$content = $this->post('content','');
			
			if($this->model('page')->insert([
				'title' => $title,
				'author' => $author,
				'content' => $content,
				'isdelete' => 0,
				'deletetime' => 0,
				'createtime' => $_SERVER['REQUEST_TIME'],
				'modifytime' => $_SERVER['REQUEST_TIME'],
			]))
			{
				$this->response->setCode(302);
				$this->response->addHeader('Location',$this->http->url('view','admin','page'));
			}
		}
	}
	
	function save()
	{
		$form = new form(config('form'));
		if($form->auth())
		{
			$title = $this->post('title','');
			$author = $this->post('author','');
			$content = $this->post('content','');
			$id = $this->post('id',NULL);
			
			if ($this->model('page')->where('id=?',[$id])->update([
				'title' => $title,
				'author' => $author,
				'content' => $content,
				'modifytime' => $_SERVER['REQUEST_TIME']
			]))
			{
				$this->response->setCode(302);
				$this->response->addHeader('Location',$this->http->url('view','admin','page'));
			}
		}
	}
}