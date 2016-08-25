<?php
namespace application\control\ajax;

use system\core\ajax;
use application\message\json;

class page extends ajax
{
    function remove()
    {
        $id = $this->post('id');
        if ($this->model('page')->where('id=?', [$id])->limit(1)->update([
            'isdelete' => 1,
            'deletetime' => $_SERVER['REQUEST_TIME']
        ])
        ) {
            return new json(json::OK);
        }
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