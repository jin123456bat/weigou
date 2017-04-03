<?php
/**
 * @author jin12
 *
 */
class goodsDeclare extends Data
{
	/**
	 * 账册编号
	 * @var unknown
	 */
	private $accountBookNo;
	
	/**
	 * 进出口标志
	 * @var unknown
	 */
	private $ieFlag;
	
	/**
	 * 预录入号码
	 * @var unknown
	 */
	private $preEntryNumber;
	
	/**
	 * 进口类型
	 * @var unknown
	 */
	private $importType;
	
	/**
	 * 进出口日期
	 * @var unknown
	 */
	private $inOutDateStr;
	
	/**
	 * 进出口岸代码
	 */
	private $iePort;
	
	/**
	 * 抵运港
	 * @var unknown
	 */
	private $destinationPort;
	
	/**运输工具名称
	 * @var unknown
	 */
	private $trafName;
	
	/**
	 * 运输工具号
	 * @var unknown
	 */
	private $voyageNo;
	
	/**
	 * 运输方式代码
	 * @var unknown
	 */
	private $trafMode;
	
	/**
	 * 申报单位类别
	 * @var unknown
	 */
	private $declareCompanyType;
	
	/**
	 * 申报单位代码
	 * @var unknown
	 */
	private $declareCompanyCode;
	
	/**
	 * 申报单位名称
	 * @var unknown
	 */
	private $declareCompanyName;
	
	/**
	 * 电商企业代码
	 * @var unknown
	 */
	private $eCommerceCode;
	
	/**
	 * 电商企业名称
	 * @var unknown
	 */
	private $eCommerceName;
	
	/**
	 * 运单号
	 * @var unknown
	 */
	private $orderNo;
	
	/**
	 * 分运单号
	 * @var unknown
	 */
	private $wayBill;
	
	/**
	 * 起运国
	 * @var unknown
	 */
	private $tradeCountry;
	
	/**
	 * 件数
	 * @var unknown
	 */
	private $packNo;
	
	/**
	 * 毛重
	 * @var unknown
	 */
	private $grossWeight;
	
	/**
	 * 净重
	 * @var unknown
	 */
	private $netWeight;
	
	/**
	 * 包装种类
	 * @var unknown
	 */
	private $warpType;
	
	/**
	 * 备注
	 * @var unknown
	 */
	private $remark;
	
	/**
	 * 申报口岸代码
	 * @var unknown
	 */
	private $declPort;
	
	/**
	 * 录入人
	 * @var unknown
	 */
	private $enteringPerson;
	
	/**
	 * 录入单位名称
	 * @var unknown
	 */
	private $enteringCompanyName;
	
	/**
	 * 报关员代码
	 * @var unknown
	 */
	private $declarantNo;
	
	/**
	 * 码头货场代码
	 * @var unknown
	 */
	private $customsField;
	
	/**
	 * 发件人
	 * @var unknown
	 */
	private $senderName;
	
	/**
	 * 收件人
	 * @var unknown
	 */
	private $consignee;
	
	/**
	 * 发件人国别
	 * @var unknown
	 */
	private $senderCountry;
	
	/**
	 * 发件人城市
	 * @var unknown
	 */
	private $senderCity;
	
	/**
	 * 支付人证件类型
	 * @var unknown
	 */
	private $paperType;
	
	/**
	 * 支付人证件号
	 * @var unknown
	 */
	private $paperNumber;
	
	/**
	 * 价值
	 * @var unknown
	 */
	private $worth;
	
	/**
	 * 币制
	 * @var unknown
	 */
	private $currCode;
	
	/**
	 * 主要货物名称
	 * @var unknown
	 */
	private $mainGName;
	
	/**
	 * 区内企业编码
	 * @var unknown
	 */
	private $internalAreaCompanyNo;
	
	/**
	 * 区内企业编码
	 * @var unknown
	 */
	private $internalAreaCompanyName;
	
	/**
	 * 申请单编号
	 * @var unknown
	 */
	private $applicationFormNo;
	
	/**
	 * 是否授权
	 * @var unknown
	 */
	private $isAuthorize;
	
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