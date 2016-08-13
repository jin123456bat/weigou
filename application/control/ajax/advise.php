<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class advise extends ajax
{
	function create()
	{
		$title = $this->post('title');
		if (!empty($title))
		{
			$num = $this->model('advise')->where('isdelete=?',[0])->find('count(*)');
			if($this->model('advise')->insert([
				'title' => $title,
				'sort' => isset($num['count(*)']) && !empty($num['count(*)'])?$num['count(*)']:0,
				'isdelete' => 0,
				'deletetime' => 0,
				'createtime' => $_SERVER['REQUEST_TIME'],
				'num' => 0,
			]))
			{
				return new json(json::OK,NULL,[
					'id' => $this->model('advise')->lastInsertId(),
					'title' => $title,
					'sort' => isset($num['count(*)']) && !empty($num['count(*)'])?$num['count(*)']:0,
				]);
			}
			return new json(json::PARAMETER_ERROR);
		}
		return new json(json::PARAMETER_ERROR,'title不能为空');
	}
	
	function remove()
	{
		$adminHelper = new \application\helper\admin();
		if (empty($adminHelper->getAdminId()))
			return new json(json::NOT_LOGIN);
		
		$id = $this->post('id');
		if (empty($id))
			return new json(json::PARAMETER_ERROR);
		if($this->model('advise')->where('id=?',[$id])->update([
			'isdelete'=>1,
			'deletetime'=>$_SERVER['REQUEST_TIME']
		]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	function clear()
	{
		$adminHelper = new \application\helper\admin();
		if (empty($adminHelper->getAdminId()))
			return new json(json::NOT_LOGIN);
		
		$id = $this->post('id');
		if (empty($id))
			return new json(json::PARAMETER_ERROR);
		$this->model('advise_user')->where('aid=?',[$id])->delete();
		return new json(json::OK);
	}
	
	function moveup()
	{
		$id = $this->post('id');
		if (!empty($id))
		{
			$advise = $this->model('advise')->orderby('sort','asc')->where('isdelete=?',[0])->select();
			foreach($advise as $index => $a)
			{
				if($a['id'] == $id && isset($advise[$index-1]))
				{
					$temp = $advise[$index];
					$advise[$index] = $advise[$index-1];
					$advise[$index-1] = $temp;
					break;
				}
			}
			foreach ($advise as $index => $a)
			{
				$this->model('advise')->where('id=?',[$a['id']])->limit(1)->update('sort',$index);
			}
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	function movedown()
	{
		$id = $this->post('id');
		if (!empty($id))
		{
			$advise = $this->model('advise')->orderby('sort','asc')->where('isdelete=?',[0])->select();
			foreach($advise as $index => $a)
			{
				if($a['id'] == $id && isset($advise[$index+1]))
				{
					$temp = $advise[$index];
					$advise[$index] = $advise[$index+1];
					$advise[$index+1] = $temp;
					break;
				}
			}
			foreach ($advise as $index => $a)
			{
				$this->model('advise')->where('id=?',[$a['id']])->limit(1)->update('sort',$index);
			}
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	function submit()
	{
		$id = $this->post('id');
		$cid = $this->post('cid');
		if (!empty($id) && !empty($cid))
		{
			$ip = ip();
			$userHelper = new \application\helper\user();
			$uid = $userHelper->isLogin();
				
			if(empty($this->model('advise_user')->where('ip=? and cid=?',[$ip,$cid])->find()))
			{
				if (!empty($uid))
				{
					if(!empty($this->model('advise_user')->where('uid=? and cid=?',[$uid,$cid])->find()))
					{
						return new json(json::PARAMETER_ERROR,'每个用户只能投一次');
					}
				}
				if($this->model('advise_user')->insert([
					'ip' => $ip,
					'uid' => $uid,
					'cid'=> $cid,
					'aid' => $id,
					'createtime' => $_SERVER['REQUEST_TIME']
				]))
				{
					return new json(json::OK);
				}
				return new json(json::PARAMETER_ERROR);
			}
			return new json(json::PARAMETER_ERROR,'每个用户只能投一次');
		}
		return new json(json::PARAMETER_ERROR);
	}
}