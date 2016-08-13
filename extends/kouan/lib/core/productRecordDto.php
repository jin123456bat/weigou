<?php
/**
 * @author jin12
 *
 */
class productRecordDto extends Data
{
	/**
	 * 电商平台编号
	 * @var unknown
	 */
	private $companyCode;
	/**
	 * 电商平台名称
	 * @var unknown
	 */
	private $companyName;
	/**
	 * 行邮税号
	 * @var unknown
	 */
	private $postTaxNo;
	
	/**
	 * 商品类别
	 * @var unknown
	 */
	private $goodsType;
	/**
	 * 商品名称
	 * @var unknown
	 */
	private $goodsName;
	/**
	 * 条形码
	 * @var unknown
	 */
	private $barCode;
	/**
	 * 品牌
	 * @var unknown
	 */
	private $brand;
	/**
	 * 规格型号
	 * @var unknown
	 */
	private $goodsModel;
	/**
	 * 主要成份
	 * @var unknown
	 */
	private $mainElement;
	/**
	 * 用途
	 * @var unknown
	 */
	private $purpose;
	/**
	 * 适用标准
	 * @var unknown
	 */
	private $standards;
	/**
	 * 生产企业
	 * @var unknown
	 */
	private $productionEnterprise;
	/**
	 * 生产国
	 * @var unknown
	 */
	private $productionCountry;
	/**
	 * 许可证号
	 * @var unknown
	 */
	private $licenceKey;
	/**
	 * 类目编码
	 * @var unknown
	 */
	private $categoryCode;
	/**
	 * 材料地址
	 * @var unknown
	 */
	private $materialAddress;
	/**
	 * 申请时间
	 * @var unknown
	 */
	private $declareTimeStr;
	
	function __construct($companyCode,$companyName,$postTaxNo,$goodsType,$goodsName,$barCode,$brand,$goodsModel,$mainElement,$purpose,$standards,$productionEnterprise,$productionCountry,$licenceKey,$categoryCode,$materialAddress,$declareTimeStr)
	{
		$this->companyCode = $companyCode;
		$this->companyName = $companyName;
		$this->postTaxNo = $postTaxNo;
		$this->goodsType = $goodsType;
		$this->goodsName = $goodsName;
		$this->barCode = $barCode;
		$this->goodsModel = $goodsModel;
		$this->mainElement = $mainElement;
		$this->purpose = $purpose;
		$this->standards = $standards;
		$this->productionEnterprise = $productionEnterprise;
		$this->productionCountry = $productionCountry;
		$this->licenceKey = $licenceKey;
		$this->categoryCode = $categoryCode;
		$this->materialAddress = $materialAddress;
		$this->declareTimeStr = $declareTimeStr;
		$this->init();
	}
	
	public function init()
	{
		$this->setData(sprintf(loadTemplate(__CLASS__),$this->companyCode,$this->companyName,$this->postTaxNo,$this->goodsType,$this->goodsName,$this->barCode,$this->brand,$this->goodsModel,$this->mainElement,$this->purpose,$this->standards,$this->productionEnterprise,$this->productionCountry,$this->licenceKey,$this->categoryCode,$this->materialAddress,$this->declareTimeStr));
	}
}