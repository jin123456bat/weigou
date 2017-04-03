<?php
namespace application\control\api;
use application\message\json;
class carousel extends common
{
	private $_response;
	
	function __construct()
	{
		parent::__construct();
		$this->_response = $this->init();
	}
	
	function lists()
	{
		if (!empty($this->_response))
			return $this->_response;
		
		$position = $this->data('position');
		
		$carousel = $this->model('carousel')->table('upload','left join','upload.id=carousel.logo')->where('carousel.isdelete =? and position=?',[0,$position])->orderby('carousel.sort','asc')->select([
			'carousel.id',
			'carousel.title',
			'carousel.linktype',
			'carousel.url',
			'upload.path as logo',
		]);
		
		$total = $this->model('carousel')->where('isdelete=? and position=?',[0,$position])->select('count(*)');
		
		$carouselReturnModel = [
			'current' => count($carousel),
			'total' => isset($total[0]['count(*)'])?$total[0]['count(*)']:0,
			'start' => $this->data('start',0),
			'length' => $this->data('length',10),
			'data' => $carousel,
		];
		
		return new json(json::OK,NULL,$carouselReturnModel);
	}
}