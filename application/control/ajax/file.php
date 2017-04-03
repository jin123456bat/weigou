<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
class file extends ajax
{
	function remove()
	{
        $admin=$this->session->id;
		$id = $this->post('id');
		if (!empty($id))
		{
			$file = $this->model('upload')->where('id=?',[$id])->find();
			if (!empty($file))
			{
				unlink($file['path']);
				$this->model('upload')->where('id=?',[$id])->delete();
                $this->model("admin_log")->insertlog($admin, '删除文件成功,图片id:'.$id,1);
				return new json(json::OK);
			}
            $this->model("admin_log")->insertlog($admin, '删除文件失败（手机号码还没注册）');
			return new json(json::PARAMETER_ERROR);
		}
        $this->model("admin_log")->insertlog($admin, '删除文件失败（手机号码还没注册）');
		return new json(json::PARAMETER_ERROR);
	}
}