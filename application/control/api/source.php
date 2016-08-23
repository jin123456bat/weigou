<?php
namespace application\control\api;

use application\helper as helper;
use application\message\json;


class source extends common
{
    private $_response;

    function __construct()
    {
        parent::__construct();
        $this->_response = $this->init();
    }


    function guide()
    {


        if (!empty($this->_response)) {

            //  return $this->_response;
        }

        $userHelper = new helper\user();
        $uid = $userHelper->isLogin();
        $uid = 1946;
        if (empty($uid))
            return new json(json::NOT_LOGIN);

        //uid存在 获取他的source
        //获取他第一级的sourceid

        $u_source = $this->model("source")->where("id=(select source from user where id=?)", [$uid])->find(["u_source", "id"]);

        if (empty($u_source['u_source'])) {
            $source = $u_source['id'];
        } else {
            $source = $u_source['u_source'];
        }
        $imgs = $this->model("sourceimg")
            ->table("upload","left join","upload.id=sourceimg.upload_id")
            ->where("sourceimg.is_del=0 and sourceimg.source_id=?", [$source])->select();
        return new json(json::OK, NULL, $imgs);

    }
}