<?php
/**
 * @author 程晨
 *
 */
class jkfOrderImportHead extends Data implements ArrayAccess
{
	/**
	 * 电商企业编号
	 * 电商平台下的商家备案编号
	 * @var unknown
	 */
	private $eCommerceCode;
	
	/**
	 * 电商企业名称
	 * 电商平台下的商家备案名称
	 * @var unknown
	 */
	private $eCommerceName;
	
	/**
	 * 进出口标志
	 * I:进口 E:出口
	 * @var unknown
	 */
	private $ieFlag;
	
	/**
	 * 支付类型
	 * 01:银行卡支付
	 * 02:余额支付
	 * 03:其他
	 * @var unknown
	 */
	private $payType;
	
	/**
	 * 支付公司编码
	 * 支付平台在跨境平台备案编号
	 * @var unknown
	 */
	private $payCompanyCode;
	
	/**
	 * 支付单号
	 * 支付成功后，支付平台反馈给电商平台的支付单号
	 * @var unknown
	 */
	private $payNumber;
	
	/**
	 * 订单总金额
	 * 货款+订单税款+运费
	 * @var unknown
	 */
	private $orderTotalAmount;
	
	/**
	 * 订单编号
	 * 电商平台订单号
	 * @var unknown
	 */
	private $orderNo;
	
	/**
	 * 订单税款
	 * 交易过程中商家向用户征收的税款，免税模式填写0
	 * @var unknown
	 */
	private $orderTaxAmount;
	
	/**
	 * 订单货款
	 * @var unknown
	 */
	private $orderGoodsAmount;
	
	/**
	 * 运费
	 * 非必需
	 * 交易过程中商家向用户征收的运费，免邮模式填写0
	 * @var unknown
	 */
	private $feeAmount;
	
	/**
	 * 企业备案名称
	 * 企业在跨境电商通关服务平台的备案名称
	 * @var unknown
	 */
	private $companyName;
	
	/**
	 * 企业备案编码
	 * 企业在跨境电商通关服务的备案编号
	 * @var unknown
	 */
	private $companyCode;
	
	/**
	 * 成交时间
	 * 格式:2014-02-18 15:58:11
	 * @var unknown
	 */
	private $tradeTime;
	
	/**
	 * 成交币制
	 * @var unknown
	 */
	private $currCode;
	
	/**
	 * 成交总价
	 * @var unknown
	 */
	private $totalAmount;
	
	/**
	 * 收件人邮箱
	 * 非必需
	 * @var unknown
	 */
	private $consigneeEmail;
	
	/**
	 * 收件人电话
	 * @var unknown
	 */
	private $consigneeTel;
	
	/**
	 * 收件人
	 * @var unknown
	 */
	private $consignee;
	
	/**
	 * 收件人地址
	 * @var unknown
	 */
	private $consigneeAddress;
	
	/**
	 * 总件数
	 * 包裹中独立包装的物品总数，不考虑物品计量单位
	 * @var unknown
	 */
	private $totalCount;
	
	/**
	 * 发货方式（物流方式） 
	 * 非必需
	 * 
	 * @var unknown
	 */
	private $postMode;
	
	/**
	 * 发件人国别
	 * @var unknown
	 */
	private $senderCountry;
	
	/**
	 * 发件人名称
	 * @var unknown
	 */
	private $senderName;
	
	/**
	 * 购买人ID
	 * @var unknown
	 */
	private $purchaserId;
	
	/**
	 * 物流企业名称
	 * @var unknown
	 */
	private $logisCompanyName;
	
	/**
	 * 物流企业编码
	 * @var unknown
	 */
	private $logisCompanyCode;
	
	/**
	 * 邮编
	 * 非必需
	 * @var unknown
	 */
	private $zipCode;
	
	/**
	 * 备注信息
	 * 非必需
	 * @var unknown
	 */
	private $note;
	
	/**
	 * 运单号列表
	 * 非必需
	 * 单号之间分号隔开
	 * @var unknown
	 */
	private $wayBills;
	
	/**
	 * 汇率，人民币填写1
	 * 非必需
	 * @var unknown
	 */
	private $rate;
	
	/**
	 * 个人委托协议内容
	 * @var unknown
	 */
	private $userProcotol;
	
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