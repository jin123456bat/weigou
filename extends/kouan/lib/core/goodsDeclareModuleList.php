<?php
class goodsDeclareModuleList extends Data
{
	private $goodsDeclareModuleList;
	
	public function init()
	{
		$this->setData(sprintf(loadTemplate(__CLASS__),$this->goodsDeclareModuleList));
	}
	
	public function add(goodsDeclareModule $goodsDeclareModule)
	{
		$this->goodsDeclareModuleList .= $goodsDeclareModule->getData();
	}
	
	public function getData()
	{
		$this->init();
		return parent::getData();
	}
}