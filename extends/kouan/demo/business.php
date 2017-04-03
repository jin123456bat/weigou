<?php
/*
 * 电商平台发送商品订单数据到通关服务平台
 */

include '../index.php';


$business = new Business();
/*
 * 该功能这个head固定为IMPORTORDER
 */
$head = new head('IMPORTORDER');
$business->setHead($head);

/*
 * 设置报文内容
 */
$orderInfo = new orderInfo();

/**
 * 发送方的备案编号
 */
$companyCode = COMPANYCODE;

/**
 * 订单编号
 */
$businessNo = '1234567789';

/**
 * 业务类型  这个字段在head中
 */
$businessType = $head->getBusinessType();

/**
 * 申报类型  固定为1
 */
$declareType = '1';

$jkfSign = new jkfSign($companyCode, $businessNo, $businessType, $declareType);
$orderInfo->add($jkfSign);

/*
 * 创建一个进口信息头
 */
$jkfOrderImportHead = new jkfOrderImportHead();
/*
 * 电商企业编号
 */
$jkfOrderImportHead->eCommerceCode = ECOMMERCECODE;
/*
 * 电商企业名称
 */
$jkfOrderImportHead->eCommerceName = ECOMMERCENAME;
/*
 * 进出口标志
 * I:进口 E:出口
 */
$jkfOrderImportHead->ieFlag = 'I';
/*
 * 支付方式
 * 01:银行卡支付
 * 02:余额支付
 * 03:其他
 */
 $jkfOrderImportHead->payType = '03';
 /*
  * 支付公司编码
  */
 $jkfOrderImportHead->payCompanyCode = 'alipay';
/*
 * 支付单号
 * 支付平台返回的支付单号
 */
 $jkfOrderImportHead->payNumber = 'idontknow';
 /*
  * 电商平台的订单号
  */
 $jkfOrderImportHead->orderNo = 'idontknow';
 /*
  * 订单总金额
  * 税款，货款，运费加一起
  */
 $jkfOrderImportHead->orderTotalAmount = 10000;
 /*
  * 商家向用户征收的税款,免税0
  */
 $jkfOrderImportHead->orderTaxAmount = 0;
 /*
  * 订单货款
  */
 $jkfOrderImportHead->orderGoodsAmount = 10000;
 /*
  * 运费，免运费0
  */
 $jkfOrderImportHead->feeAmount = 0;
 /*
  * 企业备案名称
  */
 $jkfOrderImportHead->companyName = COMPANYNAME;
 /*
  * 企业备案编码，这个跟sign中的编码是一个编码吗？先按一个来处理吧
  */
 $jkfOrderImportHead->companyCode = COMPANYCODE;
 /*
  * 成交时间
  */
 $jkfOrderImportHead->tradeTime = '2014-02-18 15:58:11';
 /*
  * 成交币制
  */
 $jkfOrderImportHead->currCode = MoneyType::人民币;
 /*
  * 成交总金额
  */
 $jkfOrderImportHead->totalAmount = '10000';
 /*
  * 收件人邮箱  非必需
  */
 $jkfOrderImportHead->consigneeEmail = '326550324@qq.com';
 /*
  * 收件人电话
  */
 $jkfOrderImportHead->consigneeTel = '18548143019';
 /*
  * 收件人姓名
  */
 $jkfOrderImportHead->consignee = '金程晨';
 /*
  * 收件人地址 选填
  */
 $jkfOrderImportHead->consigneeAddress = '杭州';
 /*
  * 独立包装件数
  */
 $jkfOrderImportHead->totalCount = '1';
 /*
  * 物流方式  非必需
  * 1	邮政小包
  * 2	快件
  * 3	EMS
  */
 $jkfOrderImportHead->postMode = '3';
 /*
  * 发件人国别
  */
 $jkfOrderImportHead->senderCountry = Country::中国;
 /*
  * 发件人姓名
  */
 $jkfOrderImportHead->senderName = '金程晨';
 /*
  * 购买者id
  */
 $jkfOrderImportHead->purchaserId = '18548143019';
 /*
  * 物流企业名称
  */
 $jkfOrderImportHead->logisCompanyName = '顺丰';
 /*
  * 物流企业编码
  */
 $jkfOrderImportHead->logisCompanyCode = '顺丰的编码';
 /*
  * 邮编  非必需
  */
 $jkfOrderImportHead->zipCode = '310000';
 /*
  * 备注  非必需
  */
 $jkfOrderImportHead->note = '这里是备注啊';
 /*
  * 运单号  分开隔开
  */
 $jkfOrderImportHead->wayBills = '1;2;3';
 /*
  * 汇率 人名币 1
  */
 $jkfOrderImportHead->rate = 1;
 
$orderInfo->add($jkfOrderImportHead);
/*
 * 创建一个订单信息
 */ 
$jkfOrderDetail = new jkfOrderDetail();
/**
 * 商品序号 不大于50
 * @var unknown
 */
$jkfOrderDetail->goodsOrder = 49;

/**
 * 物品名称
 * @var unknown
 */
$jkfOrderDetail->goodsName = '足球';

/**
 * 规格型号  非必需
 * @var unknown
 */
$jkfOrderDetail->goodsModel;

/**
 * 行邮税号
 * 必须已备案，且与 参数说明文档中的行邮税号 中的税号一致，必须申报完税价格表中带有明确税率的税号
 * 参照表
 * PS:特么的这个太复杂了  这个代码是体育用品 测试用的 这个编号应该是商品的类别
 * @var unknown
 */
$jkfOrderDetail->codeTs = '25000001';

/**
 * 毛重 非必需  小数点后4位
 * @var unknown
 */
$jkfOrderDetail->grossWeight = 10.2345;

/**
 * 申报单价 商品实际支付的金额
 * @var unknown
 */
$jkfOrderDetail->unitPrice = 10000;

/**
 * 申报计量单位
 * 参照表
 * @var unknown
 */
$jkfOrderDetail->goodsUnit = MeasurementUnit::个;

/**
 * 申报数量
 * @var unknown
 */
$jkfOrderDetail->goodsCount = 1;

/**
 * 产销国 参数可参考列表  142代表中国
 * 参照表
 * @var unknown
 */
$jkfOrderDetail->originCountry = Country::中国;
/*
 * 将订单信息加入列表并且加入到orderinfo中
 */
$jkfOrderDetailList = new jkfOrderDetailList();
$jkfOrderDetailList->add($jkfOrderDetail);
$jkfOrderDetailList->add($jkfOrderDetail);

$orderInfo->add($jkfOrderDetailList);

$jkfGoodsPurchaser = new jkfGoodsPurchaser();
/*
 * 购买人id
 */
$jkfGoodsPurchaser->id = '18548143019';
/*
 * 购买人名字
 */
 $jkfGoodsPurchaser->name = '金程晨';
 /*
  * 购买人email 非必需
  */
 $jkfGoodsPurchaser->email = '326550324@qq.com';
 /*
  * 购买人电话
  */
 $jkfGoodsPurchaser->telNumber = '18548143019';
 /*
  * 购买人地址 非必需
  */
 $jkfGoodsPurchaser->address = '杭州';
 /*
  * 购买人证件类型 非必需
  *	01:身份证（试点期间只能是身份证）
  * 02:护照
  * 03:其他
  */
 $jkfGoodsPurchaser->paperType = '01';
 /*
  * 证件号码 非必需
  */
 $jkfGoodsPurchaser->paperNumber = '150203199010190332';
 $orderInfo->add($jkfGoodsPurchaser);
 
$orderInfoList = new orderInfoList();
$orderInfoList->add($orderInfo);

$body = new body();
$body->add($orderInfoList);

$business->setBody($body);

$sender = new Sender();
$result = $sender->business($business);