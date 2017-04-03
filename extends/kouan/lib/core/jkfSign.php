<?php

class jkfSign extends Data
{

	/**
	 * 发送方备案编号
	 * 发送方海关十位数编码
	 */
	private $companyCode = '';
	
	/**
	 * 业务编号
	 */
	private $businessNo = '';
	
	/**
	 * 业务类型
	 */
	private $businessType = '';
	
	/**
	 * 申报类型 固定1
	 */
	private $declareType = '';
	
	/**
	 * 备注 optional
	 * @var unknown
	 */
	private $note = '';

	function __construct($companyCode,$businessNo,$businessType,$declareType,$note = NULL)
	{
		$this->companyCode = $companyCode;
		$this->businessNo = $businessNo;
		$this->businessType = $businessType;
		$this->declareType = $declareType;
		$this->note = $note;
		$this->init();
	}
	
	public function init()
	{
		$this->setData(sprintf(loadTemplate(__CLASS__),$this->companyCode,$this->businessNo,$this->businessType,$this->declareType,$this->note));
	}
}