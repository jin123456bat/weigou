<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;

class system extends ajax
{
    function set()
    {
        $admin = $this->session->id;
        $name = $this->post('name');
        $type = $this->post('type');
        $prototype = $this->post('prototype');
        $value = $this->post('value');
        if ($this->model('system')->where('name=? and type=?', [$name, $type])->limit(1)->update($prototype, $value)) {
            $this->model("admin_log")->insertlog($admin, '修改系统配置成功,名称：' . $name . ",value:" . $value, 1);
            return new json(json::OK);
        }
        $this->model("admin_log")->insertlog($admin, '修改系统配置成功,名称：' . $name . ",value:" . $value, 1);
        return new json(json::OK);
    }
}