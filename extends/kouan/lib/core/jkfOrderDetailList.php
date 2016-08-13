<?php
class jkfOrderDetailList extends Data
{
	private $orderList;
	
	public function init()
	{
		$this->setData(sprintf(loadTemplate(__CLASS__),$this->orderList));
	}
	
	public function add(jkfOrderDetail $jkfOrderDetail)
	{
		$this->orderList .= $jkfOrderDetail->getData();
	}
	
	public function getData()
	{
		$this->init();
		return parent::getData();
	}
}