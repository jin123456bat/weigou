<?php
namespace application\model;

use system\core\model;

class student_infoModel extends model
{
    function __construct($table)
    {
        parent::__construct($table);
    }

    function datatables($post)
    {
        $this->table('user', 'left join', 'user.id = student_info.uid');

        $parameter = [];
        foreach ($post['columns'] as $index => $columns) {
            if (!empty($columns['name'])) {
                $parameter[] = $columns['name'] . (empty($columns['data']) ? '' : (' as ' . $columns['data']));
                foreach ($post['order'] as $order) {
                    if ($order['column'] == $index) {
                        $this->orderby($columns['name'], $order['dir']);
                    }
                }
            }
        }
        if (isset($post['action']) && $post['action'] === 'filter') {
            if (!empty($post['name'])) {
                $this->where('student_info.name like ?', ['%' . $post['name'] . '%']);
            }
            if (!empty($post['school'])) {
                $this->where('student_info.school like ?', ['%' . $post['school'] . '%']);
            }
            if (!empty($post['card'])) {
                $this->where('student_info.card like ?', ['%' . $post['card'] . "%"]);
            }
            if (!empty($post['phone'])) {
                $this->where('user.telephone like ?', ['%' . $post['phone'] . "%"]);
            }
            if (!empty($post['createtime_from'])) {
                $this->where('student_info.created >= ?', [$post['createtime_from']]);
            }
            if (!empty($post['createtime_to'])) {
                $this->where('student_info.created <= ?', [$post['createtime_to']]);
            }

            if (!empty($post['vip']) || $post['vip']==0) {
                $this->where('user.vip =?', [$post['vip']]);
            }
            if (!empty($post['pass'])) {
                $this->where('user.school =?', [$post['pass']]);
            }

        }
        return $this->select($parameter);
    }

    function count()
    {
        $result = $this->find('count(*)');
        foreach ($result as $value) {
            return $value;
        }
        return NULL;
    }
}