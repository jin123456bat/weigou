<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class teacher extends ajax
{
	/**
	 * 添加导师
	 */
	function create()
	{
        $admin=$this->session->id;
		$uid = $this->post('uid');
		if (!empty($uid))
		{
			if(!empty($this->model('teacher')->where('uid=?',[$uid])->find()))
			{
                $this->model("admin_log")->insertlog($admin, '设置轮值导师失败（导师已经存在）');
				return new json(json::PARAMETER_ERROR,'导师已经存在');
			}
			
			$user = $this->model('user')->where('id=?',[$uid])->find();
			if ($user['master'] != 1)
			{
                $this->model("admin_log")->insertlog($admin, '设置轮值导师失败（该用户还未成为导师）');
				return new json(json::PARAMETER_ERROR,'该用户还未成为导师');
			}
			
			$count = $this->model('teacher')->select('count(*)');
			
			if($this->model('teacher')->insert([
				'uid' => $uid,
				'sort' => $count[0]['count(*)'],
				'turn' => 0,
			]))
			{
				$data = [
					'name' => $user['name'],
					'sort' => $count[0]['count(*)'],
					'uid' => $uid,
				];
                $this->model("admin_log")->insertlog($admin, '设置轮值导师成功，导师id：'.$uid,1);
				return new json(json::OK,NULL,$data);
			}
            $this->model("admin_log")->insertlog($admin, '设置轮值导师失败（添加到数据库失败）');
			return new json(json::PARAMETER_ERROR,'添加到数据库失败');
		}
        $this->model("admin_log")->insertlog($admin, '设置轮值导师失败（请求参数错误）');
		return new json(json::PARAMETER_ERROR);
	}
	
	function remove()
	{
        $admin=$this->session->id;
		$uid = $this->post('uid');
		if($this->model('teacher')->where('uid=?',[$uid])->delete())
		{
            $this->model("admin_log")->insertlog($admin, '删除轮值导师成功，导师id：' . $uid, 1);
			return new json(json::OK);
		}
        $this->model("admin_log")->insertlog($admin, '删除轮值导师失败（请求参数错误）');
		return new json(json::PARAMETER_ERROR);
	}
	
	function moveup()
	{
		$uid = $this->post('uid');
		$teacher = $this->model('teacher')->orderby('teacher.sort','asc')->select('uid');
		foreach ($teacher as $index => &$t)
		{
			if ($uid==$t['uid'] && isset($teacher[$index-1]))
			{
				$temp = $teacher[$index];
				$teacher[$index] = $teacher[$index-1];
				$teacher[$index-1] = $temp;
				break;
			}
		}
		foreach ($teacher as $index => &$t)
		{
			$this->model('teacher')->where('uid=?',[$t['uid']])->limit(1)->update('sort',$index);
		}
		return new json(json::OK);
	}
	
	function movedown()
	{
		$uid = $this->post('uid');
		$teacher = $this->model('teacher')->orderby('teacher.sort','asc')->select('uid');
		foreach ($teacher as $index => &$t)
		{
			if ($uid==$t['uid'] && isset($teacher[$index+1]))
			{
				$temp = $teacher[$index];
				$teacher[$index] = $teacher[$index+1];
				$teacher[$index+1] = $temp;
				break;
			}
		}
		
		foreach ($teacher as $index => &$t)
		{
			$this->model('teacher')->where('uid=?',[$t['uid']])->limit(1)->update('sort',$index);
		}
		return new json(json::OK);
	}
	
	function turn()
	{
        $admin=$this->session->id;
		$uid = $this->post('uid');
		if (!empty($uid))
		{
			$teacher = $this->model('teacher')->where('uid=?',[$uid])->find();
			if(isset($teacher['turn']))
			{
				if ($teacher['turn'] == 1)
				{
					$num = $this->model('teacher')->where('turn=?',[1])->find('count(*)');
					$num = isset($num['count(*)']) && !empty($num['count(*)'])?$num['count(*)']:0;
					if ($num!=1)
					{
						if($this->model('teacher')->where('uid=?',[$uid])->limit(1)->update('turn',0))
						{
                            $this->model("admin_log")->insertlog($admin, '设置轮值导师成功，导师id：' . $uid, 1);
							return new json(json::OK);
						}
					}
                    $this->model("admin_log")->insertlog($admin, '设置轮值导师失败（必须拥有一个轮询导师）');
					return new json(json::PARAMETER_ERROR,'必须拥有一个轮询导师');
				}
				else
				{
					if($this->model('teacher')->where('uid=?',[$uid])->limit(1)->update('turn',1))
					{
                        $this->model("admin_log")->insertlog($admin, '设置轮值导师成功，导师id：' . $uid, 1);
						return new json(json::OK);
					}
				}
                $this->model("admin_log")->insertlog($admin, '设置轮值导师失败（设置失败）');
				return new json(json::PARAMETER_ERROR,'设置失败');
			}
		}
	}
}