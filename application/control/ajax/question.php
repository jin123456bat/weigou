<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class question extends ajax
{
	function remove()
	{
        $admin=$this->session->id;
		$id = $this->post('id');
		if($this->model('question')->where('id=?',[$id])->update([
			'isdelete' => 1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
            $this->model("admin_log")->insertlog($admin, '删除问题成功,id：' . $id, 1);
			return new json(json::OK);
		}
	}
	
	function remove_category()
	{
        $admin=$this->session->id;
		$id = $this->post('id');
		if($this->model('question_category')->where('id=?',[$id])->update([
			'isdelete' => 1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
            $this->model("admin_log")->insertlog($admin, '删除分类成功,分类id：' . $id, 1);
			return new json(json::OK);
		}
        $this->model("admin_log")->insertlog($admin, '删除分类失败（请求参数错误）' );
		return new json(json::PARAMETER_ERROR);
	}
}