<?php
class productRecord extends Data
{
	private $productRecord;

	function __construct()
	{

	}

	public function add(Data $obj)
	{
		if ($obj instanceof jkfSign
			|| $obj instanceof productRecordDto)
		$this->productRecord .= $obj->getData();
	}

	public function init()
	{
		$this->setData(sprintf(loadTemplate(__CLASS__),$this->productRecord));
	}

	public function getData()
	{
		$this->init();
		return parent::getData();
	}
}