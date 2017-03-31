
;(function(){try{var _zzpostData = new Object();
//商品编号
var _zzcurUrl = document.location.href;
var _zzre = /([\w-]+)\.html/gi;
var pid = _zzre.exec(_zzcurUrl)[1];
if(pid.lastIndexOf("-") != -1){
pid = pid.substring(0,pid.lastIndexOf("-"));
}
_zzpostData["productId"] = pid.toUpperCase();
//siteuuid
_zzpostData["siteUUID"] = _zzsiteid;
//targetUrl
_zzpostData["targetUrl"] = _zzcurUrl.replace(/[?#].*/gi,"");
try{
//title
_zzpostData["title"] = $(".f_psys_tlt").text().replace(/\s/gi,"").replace(/【[^】]+】/,"");
if($("#styleImg").length > 0){
	//defaultImage
	_zzpostData["defaultImage"] = $("#styleImg")[0].src;
	//thumbnail
	_zzpostData["thumbnail"] = $("#imgMove_change img")[0].src;
}
if(jQuery(".g_show_pic img").length > 0){
	//defaultImage
	_zzpostData["defaultImage"] = jQuery(".g_show_pic img")[0].src;
	//thumbnail
	_zzpostData["thumbnail"] = jQuery(".g_show_pic dd img")[0].src;
}
//salePrice
var price = jQuery(".u_psys_pri").text().replace(/,/gi,"");
if(/\d+/gi.test(price)){
	_zzpostData["salePrice"] = /(\d+)/.exec(price)[1];
}

//marketPrice
var marketPrice = jQuery(".u_psys_linepri").text().replace(/,/gi,"");
if(/\d+/gi.test(marketPrice)){
	_zzpostData["marketPrice"] = /(\d+)/.exec(marketPrice)[1];
}

//originalBrand
_zzpostData["originalBrand"] = $(".f_psys_inf inf_txtcode i").text();
//originalCategory
_zzpostData["originalCategory"] = $(".navTit_span").text().replace(/\s/ig,"").replace("首页>","");	
//description
_zzpostData["description"] = "";
_zzpostData['valid']='true';
if("已售罄" == $("#f_psys_btn_lst input:eq(0)").val()){	
	_zzpostData['valid']='false';
}

}catch(e){
	_zzpostData['valid']='false';
	_zzpostData['salePrice']='1.1';
	_zzpostData['defaultImage']='http://imu.zbird.cn/267/64/26764_399!small.jpg';
	_zzpostData['siteUUID']=_zzsiteid;
	_zzpostData["title"] = "该商品已下架";
}
zz_trace.getTrace(_zzpostData);}catch(ex){
var _zzpostData=new Object();
_zzpostData['valid']='false';
_zzpostData['salePrice']='-1';
_zzpostData['defaultImage']='http://www.zbird.com/weddings/rdq08-3068513.html';
_zzpostData['siteUUID']='iaeCw0MWxZ7';
_zzpostData['title']='11DWWY6utd5K----218.72.49.198';
_zzpostData['targetUrl']=document.location.href;
if(typeof(zz_trace)!='undefined') zz_trace.getTrace(_zzpostData);}}());
