<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;

class store extends ajax
{
    function create()
    {
        $name = $this->post('name');
        $radio = $this->post('radio');
        $result = $this->model('store')->where('name=? and isdelete=?', [$name, 0])->find();
        if (!empty($result))
            return new json(json::PARAMETER_ERROR, '仓库已经存在');
        if ($this->model('store')->insert([
            'name' => $name,
            'createtime' => $_SERVER['REQUEST_TIME'],
            'modifytime' => $_SERVER['REQUEST_TIME'],
            'isdelete' => 0,
            'deletetime' => 0,
            'is_auto' => $radio
        ])
        ) {
            $data = [
                'id' => $this->model('store')->lastInsertId(),
                'name' => $name,
            ];
            return new json(json::OK, NULL, $data);
        }
        return new json(json::PARAMETER_ERROR);
    }

    function remove()
    {
        $id = $this->post('id');
        if ($this->model('store')->where('id=?', [$id])->delete()) {
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR);

        /* $id = $this->post('id');
        if($this->model('store')->where('id=?',[$id])->update(['isdelete'=>1,'deletetime'=>$_SERVER['REQUEST_TIME']]))
        {
            return new json(json::OK);
        }
        return new json(json::PARAMETER_ERROR); */
    }

    function save()
    {
        $name = $this->post('name');
        $id = $this->post('id');
        $radio = $this->post("radio");
        if ($this->model('store')->where('id=?', [$id])->update(['name' => $name, 'is_auto' => $radio, 'modifytime' => $_SERVER['REQUEST_TIME']])) {
            return new json(json::OK);
        }
        return new json(json::OK);
    }
}