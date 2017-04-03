<?php
class goodsDeclareDetails extends Data
{
	private $goodsDeclareDetails;
	
	public function init()
	{
		$this->setData(sprintf(loadTemplate(__CLASS__),$this->goodsDeclareDetails));
	}
	
	public function add(goodsDeclareDetail $goodsDeclareDetail)
	{
		$this->goodsDeclareDetails .= $goodsDeclareDetail->getData();
	}
	
	public function getData()
	{
		$this->init();
		return parent::getData();
	}
}