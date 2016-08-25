<?php
namespace application\control\form;

use system\core\control;
use system\core\form;

class page extends control
{
    function createPage()
    {
        $form = new form(config('form'));
        if ($form->auth()) {
            $title = $this->post('title', '');
            $author = $this->post('author', '');
            $content = $this->post('content', '');

            if ($this->model('page')->insert([
                'title' => $title,
                'author' => $author,
                'content' => $content,
                'isdelete' => 0,
                'deletetime' => 0,
                'createtime' => $_SERVER['REQUEST_TIME'],
                'modifytime' => $_SERVER['REQUEST_TIME'],
            ])
            ) {
                $this->response->setCode(302);
                $this->response->addHeader('Location', $this->http->url('view', 'admin', 'page'));
            }
        }
    }

    function save()
    {
        $form = new form(config('form'));
        if ($form->auth()) {
            $title = $this->post('title', '');
            $author = $this->post('author', '');
            $content = $this->post('content', '');
            $id = $this->post('id', NULL);

            if ($this->model('page')->where('id=?', [$id])->update([
                'title' => $title,
                'author' => $author,
                'content' => $content,
                'modifytime' => $_SERVER['REQUEST_TIME']
            ])
            ) {
                $this->response->setCode(302);
                $this->response->addHeader('Location', $this->http->url('view', 'admin', 'page'));
            }
        }
    }

    function notesave()
    {
        $form = new form(config('form'));
        if ($form->auth()) {
            $title = $this->post('title', '');
            $content = $this->post('content', '');
            $status = $this->post('status', '0', 'intval');


            $this->model('notice')->where('id=1')->update([
                'title' => $title,
                'body' => $content,
                '`status`' => $status,

            ]);

            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'admin', 'notice'));

        }
    }

    function centersave()
    {
        $form = new form(config('form'));
        if ($form->auth()) {
            $id = $this->post('id');
            $name = $this->post('name');
            $info = $this->post('info');
            $url = $this->post('add');

            if ($id) {
                $this->model('center_list')->where('id=?', [$id])->update([
                    'name' => $name,
                    'info' => $info,
                    'url' => $url,

                ]);
            } else {
                $this->model('center_list')->insert([
                    'name' => $name,
                    'info' => $info,
                    'url' => $url,
                    'is_del'=>0,

                ]);
            }

            $this->response->setCode(302);
            $this->response->addHeader('Location', $this->http->url('view', 'admin', 'center'));

        }
    }
}