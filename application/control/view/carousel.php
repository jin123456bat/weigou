<?php
namespace application\control\view;

use system\core\view;

class carousel extends view
{
    function __construct()
    {
        $this->_csrf_token_refresh = false;
        parent::__construct();
    }

    function detail()
    {
        $id = $this->get('id');
        $carousel = $this->model('carousel')->where('carousel.id=?', [$id])->find();
        $this->assign('carousel', $carousel);

        $category = $this->model('category')->where('isdelete=?', [0])->select();
        $this->assign('category', $category);

        $product = $this->model('product')->where('isdelete=?', [0])->select();
        $this->assign('product', $product);

        $page = $this->model('page')->where('isdelete=?', [0])->select();
        $this->assign('page', $page);

        $theme = $this->model('theme')->where('isdelete=?', [0])->select();
        $this->assign('theme', $theme);

        return $this;
    }

    function detail1()
    {
        $id = $this->get('id');
        $carousel = $this->model('carousel')->where('carousel.id=?', [$id])->find();
        $img = $this->model("sourceimg")
            ->table('upload', 'left join', 'upload.id=sourceimg.upload_id')
            ->where("sourceimg.is_del=0 and sourceimg.id=?", [$id])
            ->find([
                "sourceimg.id",
                "sourceimg.source_id",
                "sourceimg.upload_id",
                "upload.path",

            ]);
        $this->assign('carousel', $img);
        return $this;
    }
}