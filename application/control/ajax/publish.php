<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;

class publish extends ajax
{
    function create()
    {
        $admin = $this->session->id;
        $name = $this->post('name');
        $password = $this->post('password');
        if (empty($name) || empty($password)){
            $this->model("admin_log")->insertlog($admin, '商户管理，增加供应商供应商失败（信息不完整），商户名：' . $name);
            return new json(json::PARAMETER_ERROR, '请填写完整信息');
        }

        if (!empty($this->model('publish')->where('name=?', [$name])->find())) {
            $this->model("admin_log")->insertlog($admin, '商户管理，增加供应商供应商失败（用户名已经存在），商户名：' . $name);
            return new json(json::PARAMETER_ERROR, '用户名已经存在');
        }

        if ($this->model('publish')->insert([
            'name' => $name,
            'password' => md5($password),
            'isdelete' => 0,
            'deletetime' => $_SERVER['REQUEST_TIME']
        ])
        ) {
            $this->model("admin_log")->insertlog($admin, '商户管理，增加供应商供应商成功，商户名：' . $name,1);
            return new json(json::OK);
        }
        $this->model("admin_log")->insertlog($admin, '商户管理，增加供应商供应商失败，商户名：' . $name);
        return new json(json::PARAMETER_ERROR);
    }

    function remove()
    {
        $admin = $this->session->id;

        $id = $this->post('id');
        if ($this->model('publish')->where('id=?', [$id])->update([
            'isdelete' => 1,
            'deletetime' => $_SERVER['REQUEST_TIME']
        ])
        ) {
            $this->model("admin_log")->insertlog($admin, '商户管理，供应商删除成功，商户id：' . $id,1);

            return new json(json::OK);
        }
        $this->model("admin_log")->insertlog($admin, '商户管理，供应商删除失败，商户id：' . $id);
        return new json(json::PARAMETER_ERROR);
    }
}