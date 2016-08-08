<?php
namespace application\control\api;
use application\message\json;
class college extends common
{
	private $_response;
	
	function __construct()
	{
		parent::__construct();
		$this->_response = $this->init();
	}
	
	/**
	 * 教师列表
	 */
	function teacherList()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		$start = $this->data('start',0);
		$length = $this->data('length',10);
		
		$teacher = $this->model('teacher')->table('user','left join','user.id=teacher.uid')->table('upload','left join','upload.id=user.gravatar')->limit($start,$length)->orderby('teacher.sort','asc')->select([
			'user.id',
			'user.name',
			'user.description',
			'upload.path as gravatar',
		]);
		
		$total = $this->model('teacher')->select('count(*)');
		
		$teacherReturnModel = [
			'current' => count($teacher),
			'start' => $start,
			'length' => $length,
			'total' => isset($total[0]['count(*)'])?$total[0]['count(*)']:0,
			'data' => $teacher,
		];
		
		return new json(json::OK,NULL,$teacherReturnModel);
	}
	
	/**
	 * 教师的所有课程
	 */
	function teacher()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		$id = $this->data('id');
		
		$teacher = $this->model('user')->table('upload','left join','upload.id=user.gravatar')->where('user.id=?',[$id])->find([
			'user.id',
			'user.name',
			'user.description',
			'upload.path as gravatar',
		]);
		
		
		$browse = $this->model('college_user')
		->table('college','left join','college.id=college_user.college_id')
		->where('college.uid=?',[$id])
		->find('count(*)');
		
		
		$teacher['browse'] = isset($browse['count(*)'])?$browse['count(*)']:0;
		
		$college = $this->model('college')
		->table('upload as upload1','left join','upload1.id=college.logo1')
		->table('upload as upload2','left join','upload2.id=college.logo2')
		->where('college.uid=?',[$id])
		->where('college.isdelete=?',[0])
		->orderby('college.sort','asc')
		->select([
			'college.id',
			'college.title',
			'upload1.path as logo1',
			'upload2.path as logo2',
			'college.isgood',
			'left(college.description,10) as description',
			'(select count(*) from college_user where college_user.college_id=college.id) as browse'
		]);
		
		$teacher['college'] = $college;
		return new json(json::OK,NULL,$teacher);
	}
	
	/**
	 * 获取课程列表
	 * @return \application\message\json
	 */
	function lists()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		$start = $this->data('start',0);
		$length = $this->data('length',9);
	
		$filter = [
			'isdelete' => 0,
			'start' => $start,
			'length' => $length,
			'sort' => [['isgood','desc'],['sort','asc']],
			'parameter' => [
				'college.id',
				'college.title',
				'upload1.path as logo1',
				'upload2.path as logo2',
				'user.name as username',
				'college.createtime',
				'college.isgood',
				'left(college.description,10) as description',
				'(select count(*) from college_user where college_user.college_id=college.id) as browse'
			],
		];
		$college = $this->model('college')->fetchAll($filter);
		
		
		$init_num = $this->model('system')->get('initnum','college');
		foreach ($college as &$c)
		{
			$c['browse'] += $init_num;
		}
		
		$filter['parameter'] = 'count(*)';
		unset($filter['start']);
		unset($filter['length']);
		$total = $this->model('college')->fetchAll($filter);	
		
		$collegeReturnModel = [
			'current' => count($college),
			'start' => $start,
			'length' => $length,
			'total' => isset($total[0]['count(*)'])?$total[0]['count(*)']:0,
			'data' => $college,
		];
		return new json(json::OK,NULL,$collegeReturnModel);
	}
	
	/**
	 * 获取课程信息
	 */
	function detail()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		$id = $this->data('id');
		
		
		$collegeHelper = new \application\helper\college();
		
		//返回课程信息
		$college = $this->model('college')
		->table('user','left join','user.id=college.uid')
		->table('upload as upload1','left join','upload1.id=college.logo1')
		->table('upload as upload2','left join','upload2.id=college.logo2')
		->table('upload as upload3','left join','user.gravatar=upload3.id')
		->table('upload as upload4','left join','college.video=upload4.id')
		->where('college.id=? and college.isdelete=?',[$id,0])
		->find([
			'college.id',
			'college.title',
			'college.content',
			'upload1.path as logo1',
			'upload2.path as logo2',
			'upload4.path as video',
			'college.isgood',
			'college.uid',
			'user.name','user.description',
			'upload3.path as gravatar',
			'college.description',
		]);
		if (!empty($college))
		{
			$college['browse'] = $collegeHelper->getBrowse($id);
			
			//更新浏览量
			$collegeHelper->createLog($id);
			
			return new json(json::OK,NULL,$college);
		}
		return new json(json::PARAMETER_ERROR,'课程不存在');
	}
}