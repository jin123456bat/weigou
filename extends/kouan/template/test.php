<?php
/*
 * 测试报文
 */
return <<<DATA
<?xml version="1.0" encoding="UTF-8" ?>
<mo version="1.0.0">
  <head>
    <businessType>IMPORTORDER</businessType>
  </head>
  <body>
<orderInfoList>
<orderInfo>
    <jkfSign>
      <companyCode>3301968800</companyCode>
      <businessNo>order001</businessNo>
      <businessType>IMPORTORDER</businessType>
      <declareType>1</declareType>
      <note>进口订单备注</note>
    </jkfSign>
    <jkfOrderImportHead>
      <eCommerceCode>33019688oo</eCommerceCode>
      <eCommerceName>亚马逊</eCommerceName>
      <ieFlag>I</ieFlag>
      <payType>支付类型</payType>
      <payCompanyCode>3301968pay</payCompanyCode>
      <payNumber>zhifu001</payNumber>
      <orderTotalAmount>100.0</orderTotalAmount>
      <orderNo>order00001</orderNo>
      <orderTaxAmount>10.0</orderTaxAmount>
      <orderGoodsAmount>90.0</orderGoodsAmount>
      <feeAmount>5345.0</feeAmount>
      <companyName> </companyName>
      <companyCode>3301968833</companyCode>
      <tradeTime>2014-02-17 15:23:13</tradeTime>
      <currCode>502</currCode>
      <totalAmount>100.0</totalAmount>
      <consigneeEmail>loujh@sina.com</consigneeEmail>
      <consigneeTel>13242345433</consigneeTel>
      <consignee>loujianhua</consignee>
      <consigneeAddress>浙江杭州聚龙大厦</consigneeAddress>
      <totalCount>5</totalCount>
      <postMode>1</postMode>
      <senderCountry>34233</senderCountry>
      <senderName>yangtest</senderName>
      <purchaserId>中国买家a</purchaserId>
      <logisCompanyName>邮政速递</logisCompanyName>
      <logisCompanyCode>3301980101</logisCompanyCode>
      <zipCode>322001</zipCode>
      <note>备注信息</note>
      <wayBills>001;002;003</wayBills>
<rate>6.34</rate>
    </jkfOrderImportHead>
    <jkfOrderDetailList>
      <jkfOrderDetail>
        <goodsOrder>1</goodsOrder>
        <goodsName>物品名称1</goodsName>
        <goodsModel>规格型号1</goodsModel>
        <codeTs>0100000001</codeTs>
        <grossWeight>34.94</grossWeight>
        <unitPrice>3.3</unitPrice>
        <goodsUnit>035</goodsUnit>
        <originCountry>00342</originCountry>
        <goodsCount>343.0</goodsCount>
      </jkfOrderDetail>
      <jkfOrderDetail>
        <goodsOrder>2</goodsOrder>
        <goodsName>物品名称2</goodsName>
        <goodsModel>规格型号2</goodsModel>
        <codeTs>0100000002</codeTs>
        <grossWeight>54.94</grossWeight>
        <unitPrice>3.44</unitPrice>
        <goodsUnit>034</goodsUnit>
        <originCountry>00342</originCountry>
      </jkfOrderDetail>
    </jkfOrderDetailList>
    <jkfGoodsPurchaser>
      <id>中国买家a</id>
      <name>tetsname</name>
      <email>tetsname@sina.com</email>
      <telNumber>24233322323</telNumber>
      <paperType>01</paperType>
	  <address>浙江杭州杭大路9999号</address>
      <paperNumber>23458-9503285382434</paperNumber>
</jkfGoodsPurchaser>
</orderInfo>
</orderInfoList>
  </body>
</mo>
DATA;
?>