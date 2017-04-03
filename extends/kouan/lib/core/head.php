<?php

class head extends Data
{
	/**
	 * 业务类型
	 * @var unknown
	 */
	private $businessType = '';
	
	function __construct($businessType)
	{
		$this->businessType = $businessType;
		$this->init();
	}
	
	/**
	 * 获得业务类型
	 * @return unknown
	 */
	function getBusinessType()
	{
		return $this->businessType;
	}
	
	public function init()
	{
		$this->setData(sprintf(loadTemplate(__CLASS__),$this->businessType));
	}
}