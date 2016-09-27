<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;

class carousel extends ajax
{
    function save()
    {
        $admin=$this->session->id;
        $id = $this->post('id');
        if ($this->model('carousel')->where('id=?', [$id])->update([
            'title' => $this->post('title', ''),
            'sort' => $this->post('sort', 0),
            'linktype' => $this->post('linktype', 'none'),
            'url' => $this->post('url', ''),
            'modifytime' => $_SERVER['REQUEST_TIME'],
            'logo' => empty($this->post('logo', NULL, 'intval')) ? NULL : $this->post('logo', NULL, 'intval'),
        ])
        ) {
            $this->model("admin_log")->insertlog($admin, '修改首页轮薄图成功,id：' . $id, 1);
            return new json(json::OK);
        }
        $this->model("admin_log")->insertlog($admin, '修改首页轮薄图失败（请求参数不正确）' , 1);
        return new json(json::PARAMETER_ERROR);
    }

    function save1()
    {
        $id = $this->post('id');
        if ($this->model('sourceimg')->where('id=?', [$id])->update([
            'upload_id' => empty($this->post('logo', NULL, 'intval')) ? NULL : $this->post('logo', NULL, 'intval'),
        ])
        ) {
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR);
    }

    function create()
    {
        $admin=$this->session->id;
        $total = $this->model('carousel')->where('isdelete=?', [0])->select('count(*)');
        if ($this->model('carousel')->insert([
            'title' => '',
            'logo' => NULL,
            'sort' => $total[0]['count(*)'] + 1,
            'createtime' => $_SERVER['REQUEST_TIME'],
            'deletetime' => 0,
            'isdelete' => 0,
            'modifytime' => $_SERVER['REQUEST_TIME'],
            'linktype' => 'none',
            'url' => '',
            'position' => $this->post('position', 'index'),
        ])
        ) {
            $cid = $this->model('carousel')->lastInsertId();
            $this->model("admin_log")->insertlog($admin, '新增首页轮薄图成功,id：' . $cid, 1);
            return new json(json::OK, NULL, $cid);
        }
        $this->model("admin_log")->insertlog($admin, '新增首页轮薄图失败' );
        return new json(json::PARAMETER_ERROR);
    }

    function create1()
    {
        $id = $this->post('source');

        //查询是否有多条
        $count = $this->model("sourceimg")->where("is_del=0 and source_id=?", [$id])->find("count(1) as c");

        if ($count['c'] >= 1) {
            return new json(json::PARAMETER_ERROR, '只能设置一张引导图');
        }
        //增加一条记录
        $this->model("sourceimg")->insert([
            "source_id" => $id,
            "is_del" => 0
        ]);

        return new json(json::OK, null, $this->model("sourceimg")->lastInsertId());
    }

    function remove()
    {
        $admin=$this->session->id;
        $id = $this->post('id');
        if ($this->model('carousel')->where('id=?', [$id])->update([
            'isdelete' => 1,
            'deletetime' => $_SERVER['REQUEST_TIME']
        ])
        ) {
            $this->model("admin_log")->insertlog($admin, '删除首页轮薄图成功,id：' . $id, 1);
            return new json(json::OK);
        }
        $this->model("admin_log")->insertlog($admin, '删除首页轮薄图失败（请求参数不正确）');
        return new json(json::PARAMETER_ERROR);
    }

    function remove1()
    {
        $id = $this->post('id');
        if ($this->model('sourceimg')->where('id=?', [$id])->update([
            'is_del' => 1
        ])
        ) {
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR);
    }
}