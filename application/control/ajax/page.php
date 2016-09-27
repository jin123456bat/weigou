<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;

class page extends ajax
{
    function remove()
    {
        $admin=$this->session->id;
        $id = $this->post('id');
        if ($this->model('page')->where('id=?', [$id])->limit(1)->update([
            'isdelete' => 1,
            'deletetime' => $_SERVER['REQUEST_TIME']
        ])
        ) {
            $this->model("admin_log")->insertlog($admin, '页面删除成功,id：' . $id, 1);
            return new json(json::OK);
        }
        $this->model("admin_log")->insertlog($admin, '页面删除失败（请求参数错误）' );
        return new json(json::PARAMETER_ERROR);
    }

    function removecenter()
    {
        $id = $this->post('id');
        if ($this->model('center_list')->where('id=?', [$id])->limit(1)->update([
            'is_del' => 1

        ])
        ) {
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR);
    }
}