<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class college extends ajax
{
	function create()
	{
        $admin=$this->session->id;
		$data = $this->post();
		$data['logo1'] = empty(intval($data['logo1']))?NULL:intval($data['logo1']);
		$data['logo2'] = empty(intval($data['logo2']))?NULL:intval($data['logo2']);
		$data['video'] = empty(intval($data['video']))?NULL:intval($data['video']);
		$data['createtime'] = $_SERVER['REQUEST_TIME'];
		$data['isdelete'] = 0;
		$data['deletetime'] = 0;
		$data['modifytime'] = $_SERVER['REQUEST_TIME'];
		if (empty($data['uid'])) {
            $this->model("admin_log")->insertlog($admin, '添加课程失败（请选择导师）');
            return new json(json::PARAMETER_ERROR, '请选择导师');
        }
		if($this->model('college')->insert($data))
		{
            $this->model("admin_log")->insertlog($admin, '添加课程成功', 1);
			return new json(json::OK);
		}
        $this->model("admin_log")->insertlog($admin, '添加课程失败（请求参数错误）');
		return new json(json::PARAMETER_ERROR);
	}
	
	function save()
	{
        $admin = $this->session->id;
		$data = $this->post();
		$data['logo1'] = empty(intval($data['logo1']))?NULL:intval($data['logo1']);
		$data['logo2'] = empty(intval($data['logo2']))?NULL:intval($data['logo2']);
		$data['video'] = empty(intval($data['video']))?NULL:intval($data['video']);
		$id = $data['id'];
		unset($data['id']);
		if (empty(intval($data['uid'])))
		{
            $this->model("admin_log")->insertlog($admin, '添加课程失败（请选择导师）');
			return new json(json::PARAMETER_ERROR,'请选择导师');
		}
		$data['modifytime'] = $_SERVER['REQUEST_TIME'];
		if($this->model('college')->where('id=?',[$id])->limit(1)->update($data))
		{
            $this->model("admin_log")->insertlog($admin, '添加课程成功', 1);
			return new json(json::OK);
		}
        $this->model("admin_log")->insertlog($admin, '添加课程失败（请求参数错误）');
		return new json(json::PARAMETER_ERROR);
	}
	
	function remove()
	{
        $admin = $this->session->id;
		$id = $this->post('id');
		if($this->model('college')->where('id=?',[$id])->limit(1)->update([
			'isdelete' => 1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
            $this->model("admin_log")->insertlog($admin, '删除课程成功，课程id：'.$id, 1);
			return new json(json::OK);
		}
        $this->model("admin_log")->insertlog($admin, '删除课程失败（请求参数错误）');
		return new json(json::PARAMETER_ERROR);
	}
	
	function good()
	{
        $admin=$this->session->id;
		$id = $this->post('id');
		if($this->model('college')->where('id=?',[$id])->limit(1)->update(['isgood'=>1]))
		{
            $this->model("admin_log")->insertlog($admin, '课程置顶失败，id：' . $id, 1);
			return new json(json::OK);
		}
        $this->model("admin_log")->insertlog($admin, '课程置顶失败（请求参数错误）');
		return new json(json::PARAMETER_ERROR);
	}
	
	function bad()
	{
		$id = $this->post('id');
		if($this->model('college')->where('id=?',[$id])->limit(1)->update(['isgood'=>0]))
		{
			return new json(json::OK);
		}
		return new json(json::PARAMETER_ERROR);
	}
	
	/**
	 * 课程列表
	 */
	function lists()
	{
		$start = $this->get('start',0);
		$length = $this->get('length',9);
		
		$title = $this->get('title','');
		$type = $this->get('type','isgood');
		if (empty($type))
			$type = 'isgood';
		$time = $this->get('time','','strtotime');
		$uid = $this->get('uid','');
		
		$filter = [
			'title' => $title,
			'createtime' => [$time,$time+24*3600],
			'uid' => $uid,
			
			'isdelete' => 0,
			'start' => $start,
			'length' => $length,
			'sort' => [$type,'desc'],
			'parameter' => [
				'college.id',
				'college.title',
				'upload1.path as logo1',
				'upload2.path as logo2',
				'user.name as username',
				'college.createtime',
				'college.isgood',
				'left(college.description,10) as description',
				'(select count(*) from college_user where college_user.college_id=college.id) as browse'
			],
		];
		
		$college = $this->model('college')->fetchAll($filter);
		
		$filter['parameter'] = 'count(*)';
		unset($filter['start']);
		unset($filter['length']);
		$total = $this->model('college')->fetchAll($filter);
		
		$collegeReturnModel = [
			'current' => count($college),
			'start' => $start,
			'length' => $length,
			'total' => isset($total[0]['count(*)'])?$total[0]['count(*)']:0,
			'data' => $college,
		];
		return new json(json::OK,NULL,$collegeReturnModel);
	}
	
	function read()
	{
		$id = $this->post('id');
		
		if (!empty($id))
		{
			$college = $this->model('college')->where('id=?',[$id])->find();
			if (!empty($college))
			{
				$text = str_replace("\u200d", '', str_replace("&nbsp;", '', strip_tags($college['content'])));
				$text = str_replace(' ', '', $text);
				$text = str_split_unicode($text,1000);
				
				
				$file = [];
				$voice = new \application\helper\voice();
				foreach ($text as $value)
				{
					if (!empty($value))
					{
						$tempFile = $voice->textToVoice($value);
						if ($tempFile !== false)
						{
							$file[] = $tempFile;
						}
					}
				}
				return new json(json::OK,NULL,$file);
			}
		}
		return new json(json::PARAMETER_ERROR);
	}
}