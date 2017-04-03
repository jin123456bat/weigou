<?php
/**
 * Created by PhpStorm.
 * User: copy
 * Date: 16-9-23
 * Time: 下午5:20
 */

namespace application\model;

use system\core\model;

class admin_logModel extends model
{
    function __construct($table)
    {
        parent::__construct($table);
    }

    function insertlog($uid,$content, $success=0){

        $this->insert(['admin_id'=>$uid,'content'=>$content,'success'=>$success]);
    }
}