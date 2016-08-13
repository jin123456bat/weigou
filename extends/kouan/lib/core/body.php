<?php
class body extends Data
{
	private $body;
	
	function __construct()
	{
		
	}
	
	public function add(Data $obj)
	{
		if($obj instanceof orderInfoList || $obj instanceof productRecordList || $obj instanceof goodsDeclareModuleList)
		{
			$this->body .= $obj->getData();
		}
	}
	
	public function init()
	{
		$this->setData(sprintf(loadTemplate(__CLASS__),$this->body));
	}
	
	public function getData()
	{
		$this->init();
		return parent::getData();
	}
}