<?php 
/**
 * 电商平台发送商品订单数据到通关服务平台
 * @author 程晨
 *
 */
class Business extends Data
{
	private $head;
	
	private $body;
	
	private $businessType;
	
	function __construct()
	{
	}
	
	/**
	 * 根据设定的head和body开始构造xml字符串,系统会自动调用
	 */
	public function init()
	{
		$this->setData(sprintf(loadTemplate(__CLASS__),$this->head,$this->body));
	}
	
	public function setHead(head $head)
	{
		$this->head = $head->getData();
		$this->businessType = $head->getBusinessType();
	}
	
	public function setBody(body $body)
	{
		$this->body = $body->getData();
	}
	
	/**
	 * 获得businessType
	 */
	public function getType()
	{
		return $this->businessType;
	}
	
	/* 返回字符串数据
	 * @see Data::getData()
	 */
	public function getData()
	{
		$this->init();
		return parent::getData();
	}
	
	/**
	 * 返回标准xml数据
	 * @return string
	 */
	public function getXML()
	{
		$obj = new DOMDocument('1.0', 'UTF-8');
		$obj->loadXML($this->getData());
		return $obj->saveXML();
	}
}
?>