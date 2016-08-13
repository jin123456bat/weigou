<?php
/**
 * @author 程晨
 *
 */
class jkfGoodsPurchaser extends Data implements ArrayAccess
{
	/**
	 * 购买人ID
	 * @var unknown
	 */
	private $id;
	
	/**
	 * 购买人名称
	 * @var unknown
	 */
	private $name;
	
	/**
	 * 购买人邮箱 非必需
	 * @var unknown
	 */
	private $email;
	
	/**
	 * 联系电话
	 * @var unknown
	 */
	private $telNumber;
	
	/**
	 * 地址 非必需
	 * @var unknown
	 */
	private $address;
	
	/**
	 * 证件类型  非必需 
	 * 01:身份证（试点期间只能是身份证）
	 * 02:护照
	 * 03:其他
	 * @var unknown
	 */
	private $paperType;
	
	/**
	 * 证件号码 非必需
	 * @var unknown
	 */
	private $paperNumber;
	
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