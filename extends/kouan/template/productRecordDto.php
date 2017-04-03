<?php
return <<<DATA
<productRecordDto>
  <companyCode>%s</companyCode>
          <companyName>%s</companyName>
          <postTaxNo>%s</postTaxNo>
          <goodsType>%s</goodsType>
          <goodsName>%s</goodsName>
          <barCode>%s</barCode>
  <brand>%s</brand>
          <goodsModel>%s</goodsModel>
          <mainElement>%s</mainElement>
          <purpose>%s</purpose>
          <standards>%s</standards>
          <productionEnterprise>%s</productionEnterprise>
          <productionCountry>%s</productionCountry>
          <licenceKey>%s</licenceKey>
          <categoryCode>%s</categoryCode>
          <materialAddress>%s</materialAddress>
          <declareTimeStr>%s</declareTimeStr>
        </productRecordDto>

DATA;
?>