<?php
/**
 * @author 程晨
 *
 */
class jkfOrderDetail extends Data implements ArrayAccess
{
	/**
	 * 商品序号 不大于50
	 * @var unknown
	 */
	private $goodsOrder;
	
	/**
	 * 物品名称
	 * @var unknown
	 */
	private $goodsName;
	
	/**
	 * 规格型号  非必需
	 * @var unknown
	 */
	private $goodsModel;
	
	/**
	 * 行邮税号
	 * 必须已备案，且与 参数说明文档中的行邮税号 中的税号一致，必须申报完税价格表中带有明确税率的税号
	 * @var unknown
	 */
	private $codeTs;
	
	/**
	 * 毛重 非必需  小数点后4位
	 * @var unknown
	 */
	private $grossWeight;
	
	/**
	 * 申报单价 商品实际支付的金额
	 * @var unknown
	 */
	private $unitPrice;
	
	/**
	 * 申报计量单位
	 * @var unknown
	 */
	private $goodsUnit;
	
	/**
	 * 申报数量
	 * @var unknown
	 */
	private $goodsCount;
	
	/**
	 * 产销国
	 * @var unknown
	 */
	private $originCountry;
	
	
	function __construct()
	{
		
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