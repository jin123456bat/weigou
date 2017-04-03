<?php
/**
 * @author jin12
 *
 */
class goodsDeclareDetail extends Data
{
	/**
	 * 商品序号
	 * @var unknown
	 */
	private $goodsOrder;
	
	/**
	 * 行邮税号
	 * @var unknown
	 */
	private $codeTs;
	
	/**
	 * 商品货号
	 * @var unknown
	 */
	private $goodsItemNo;
	
	/**
	 * 物品名称
	 * @var unknown
	 */
	private $goodsName;
	
	/**
	 * 物品规格型号
	 * @var unknown
	 */
	private $goodsModel;
	
	/**
	 * 原产国
	 * @var unknown
	 */
	private $originCountry;
	
	/**
	 * 成交币制
	 * @var unknown
	 */
	private $tradeCurr;
	
	/**
	 * 成交总价
	 * @var unknown
	 */
	private $tradeTotal;
	
	/**
	 * 申报单价
	 * @var unknown
	 */
	private $declPrice;
	
	/**
	 * 申报总价
	 * @var unknown
	 */
	private $declTotalPrice;
	
	/**
	 * 用途
	 * @var unknown
	 */
	private $useTo;
	
	/**
	 * 申报数量
	 * @var unknown
	 */
	private $declareCount;
	
	/**
	 * 申报计量单位
	 * @var unknown
	 */
	private $goodsUnit;
	
	/**
	 * 商品毛重
	 * @var unknown
	 */
	private $goodsGrossWeight;
	
	/**
	 * 第一单位
	 * @var unknown
	 */
	private $firstUnit;
	
	/**
	 * 第一数量
	 * @var unknown
	 */
	private $firstCount;
	
	/**
	 * 第二单位
	 * @var unknown
	 */
	private $secondUnit;
	
	/**
	 * 第二数量
	 * @var unknown
	 */
	private $secondCount;
	
	/**
	 * 产品国检备案编号
	 * @var unknown
	 */
	private $productRecordNo;
	
	/**
	 * 产品网址
	 * @var unknown
	 */
	private $webSite;
	
	/**
	 * 构造
	 */
	function __construct()
	{
		//$this->init();
	}
	
	public function offsetSet($offset, $value)
	{
		$this->$offset = $value;
	}
	
	public function offsetExists($offset)
	{
		return $this->$offset !== NULL;
	}
	
	public function offsetUnset($offset)
	{
		$this->$offset = NULL;
	}
	
	public function offsetGet($offset)
	{
		return $this->$offset;
	}
	
	public function __get($name)
	{
		return $this->$name;
	}
	
	public function __set($name,$value)
	{
		$this->$name = $value;
	}
	
	public function init()
	{
		$this->setData(vsprintf(loadTemplate(__CLASS__), $this));
	}
	
	public function getData()
	{
		$this->init();
		return parent::getData();
	}
}