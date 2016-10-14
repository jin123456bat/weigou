<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;

class category extends ajax
{
    function save()
    {
        $id = $this->post('id');
        $data = [];
        if ($this->post('name') !== NULL)
            $data['name'] = $this->post('name', '');
        if ($this->post('description') !== NULL)
            $data['description'] = $this->post('description', '');
        if ($this->post('sort') !== NULL)
            $data['sort'] = $this->post('sort', 1);
        if ($this->post('logo') !== NULL)
            $data['logo'] = $this->post('logo');
        if ($this->post('alias') !== NULL)
            $data['alias'] = $this->post('alias');

        if (!empty($this->post('cid'))) {
            $data['cid'] = $this->post('cid');
        } else {
            $data['cid'] = NULL;
        }

        $data['modifytime'] = $_SERVER['REQUEST_TIME'];
        $admin = $this->session->id;
        if ($this->model('category')->where('id=?', [$id])->update($data)) {
            $this->model("admin_log")->insertlog($admin, '保存分类信息成功', 1);
            return new json(json::OK);
        }
        $this->model("admin_log")->insertlog($admin, '保存分类信息失败（参数错误）');
        return new json(json::PARAMETER_ERROR);
    }


    function remove()
    {
        $admin = $this->session->id;
        $id = $this->post('id', 0, 'intval');
        if ($this->model('category')->where('id=?', [$id])->update([
            'isdelete' => 1,
            'deletetime' => $_SERVER['REQUEST_TIME']
        ])
        ) {
            $this->model("admin_log")->insertlog($admin, '删除分类信息成功', 1);
            return new json(json::OK);
        }
        $this->model("admin_log")->insertlog($admin, '删除分类信息失败（参数错误）');
        return new json(json::PARAMETER_ERROR);
    }

    function create()
    {
        $admin = $this->session->id;
        $name = $this->post('name', '');
        $description = $this->post('description', '');
        $sort = $this->post('sort', 1);
        $logo = $this->post('logo');
        $cid = $this->post('cid');
        $alias = $this->post('alias');
        if (empty($cid)) {
            $cid = NULL;
        }
        if (empty($logo)) {
            $logo = NULL;
        }
        if (empty($alias)) {
            $alias = NULL;
        }

        if ($this->model('category')->insert([
            'id' => NULL,
            'name' => $name,
            'alias' => $alias,
            'logo' => $logo,
            'sort' => $sort,
            'description' => $description,
            'isdelete' => 0,
            'deletetime' => 0,
            'createtime' => $_SERVER['REQUEST_TIME'],
            'modifytime' => $_SERVER['REQUEST_TIME'],
            'cid' => $cid
        ])
        ) {
            $id = $this->model('category')->lastInsertId();
            $data = $this->model('category')->table('category as c_category', 'left join', 'c_category.id=category.cid')->where('category.id=?', [$id])->table('upload', 'left join', 'upload.id=category.logo')->find('
				category.id,
				category.name,
				category.alias,
				category.sort,
				category.description,
				upload.path as logo,
				c_category.name as c_name,
				category.cid
			');
            $this->model("admin_log")->insertlog($admin, '新增分类信息成功', 1);
            return new json(json::OK, NULL, $data);
        }
        $this->model("admin_log")->insertlog($admin, '新增分类信息失败（参数错误）');
        return new json(json::PARAMETER_ERROR);
    }
}