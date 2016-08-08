<?php
class productRecordList extends Data
{
	private $productRecordList;

	function __construct()
	{

	}

	public function add(productRecord $productRecord)
	{
		$this->productRecordList .= $productRecord->getData();
	}

	public function init()
	{
		$this->setData(sprintf(loadTemplate(__CLASS__),$this->productRecordList));
	}

	public function getData()
	{
		$this->init();
		return parent::getData();
	}
}