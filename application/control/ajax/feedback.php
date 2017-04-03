<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class feedback extends ajax
{
	function remove()
	{
        $admin=$this->session->id;
		$id = $this->post('id',0,'intval');
		if (!empty($id))
		{
			if($this->model('feedback')->where('id=?',[$id])->limit(1)->update([
				'isdelete' => 1,
				'deletetime' => $_SERVER['REQUEST_TIME']
			]))
			{
                $this->model("admin_log")->insertlog($admin, '删除建议成功,id:' . $id, 1);
				return new json(json::OK);
			}
		}
        $this->model("admin_log")->insertlog($admin, '删除建议失败');
		return new json(json::PARAMETER_ERROR);
	}
}