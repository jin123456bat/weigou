<?php
class goodsDeclareModule extends Data
{
	private $goodsDeclareModule;
	
	function __construct()
	{
		
	}
	
	function add(Data $obj)
	{
		if($obj instanceof jkfSign 
			|| $obj instanceof goodsDeclare
			|| $obj instanceof goodsDeclareDetails
		)
		$this->goodsDeclareModule .= $obj->getData();
	}
	
	public function init()
	{
		$this->setData(sprintf(loadTemplate(__CLASS__),$this->goodsDeclareModule));
	}
	
	function getData()
	{
		$this->init();
		return parent::getData();
	}
}