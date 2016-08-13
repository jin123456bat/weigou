<?php
class orderInfo extends Data
{
	private $orderInfo;
	
	function __construct()
	{
		
	}
	
	function add(Data $obj)
	{
		if($obj instanceof jkfSign 
			|| $obj instanceof jkfOrderImportHead
			|| $obj instanceof jkfOrderDetailList
			|| $obj instanceof jkfGoodsPurchaser
		)
		$this->orderInfo .= $obj->getData();
	}
	
	public function init()
	{
		$this->setData(sprintf(loadTemplate(__CLASS__),$this->orderInfo));
	}
	
	function getData()
	{
		$this->init();
		return parent::getData();
	}
}