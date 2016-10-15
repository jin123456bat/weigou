<?php
namespace application\model;
use system\core\model;
class messageModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	/**
	 * 添加消息
	 * @param unknown $uid
	 * @param unknown $title
	 * @param unknown $content
	 * @return \system\core\Ambigous
	 */
	function create($uid,$title,$content)
	{
		return $this->insert([
			'uid' => $uid,
			'title' => $title,
			'content'=> $content,
			'isread' => 0,
			'readtime' => 0,
			'createtime' => $_SERVER['REQUEST_TIME'],
			'isdelete' => 0,
			'deletetime'=>0,
			'readnum'=>0,
		]);
	}
	
	/**
	 * 阅读消息
	 * @param unknown $id
	 * @return \system\core\Ambigous
	 */
	function read($id)
	{
		$this->where('id=?',[$id])->limit(1)->update([
			'isread' => 1,
			'readtime'=>$_SERVER['REQUEST_TIME']
		]);
		$this->where('id=?',[$id])->limit(1)->increase('readnum',1);
		return true;
	}
	
	/**
	 * 删除消息
	 * @param unknown $id
	 * @return \system\core\Ambigous
	 */
	function remove($id)
	{
		return $this->where('id=?',[$id])->update([
			'isdelete' => 1,
			'deletetime'=>$_SERVER['REQUEST_TIME']
		]);
	}
	
	function fetch(array $filter = [])
	{
		if (isset($filter['uid']))
		{
			$this->where('uid=?',[$filter['uid']]);
		}
		if(isset($filter['isdelete']))
		{
			$this->where('isdelete=?',[$filter['isdelete']]);
		}
		if(isset($filter['isread']))
		{
			$this->where('isread=?',[$filter['isread']]);
		}
		return parent::fetch($filter);
	}
}