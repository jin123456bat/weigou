<?php
class orderInfoList extends Data
{
	private $orderInfoList;
	
	function __construct()
	{
		
	}
	
	public function add(orderInfo $orderInfo)
	{
		$this->orderInfoList .= $orderInfo->getData();
	}
	
	public function init()
	{
		$this->setData(sprintf(loadTemplate(__CLASS__),$this->orderInfoList));
	}
	
	public function getData()
	{
		$this->init();
		return parent::getData();
	}
}