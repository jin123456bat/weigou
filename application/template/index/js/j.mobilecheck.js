var browser={
versions:function(){
var u = navigator.userAgent, app = navigator.appVersion;
return {
trident: u.indexOf('Trident') > -1, //IE内核
presto: u.indexOf('Presto') > -1, //opera内核
webKit: u.indexOf('AppleWebKit') > -1, //苹果、谷歌内核
gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, //火狐内核
mobile: !!u.match(/AppleWebKit.*Mobile/) || !!u.match(/Windows Phone/) || !!u.match(/Android/) || !!u.match(/MQQBrowser/),
ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或者uc浏览器
iPhone: u.indexOf('iPhone') > -1 || u.indexOf('Mac') > -1, //是否为iPhone或者QQHD浏览器
iMac: u.indexOf('Macintosh') > -1, //是否为iMac
iPad: u.indexOf('iPad') > -1, //是否iPad
iTouch: u.indexOf('iTouch') > -1, //是否iTouch
iPod: u.indexOf('iPod') > -1, //是否iPod
wphone: u.indexOf('Windows Phone') > -1, //是否wphone
SymbianOS: u.indexOf('SymbianOS') > -1, //是否SymbianOS
BlackBerry: u.indexOf('BlackBerry') > -1, //是否BlackBerry
webApp: u.indexOf('Safari') == -1 //是否web应该程序，没有头部与底部
};
}()
}
var url=window.location.href;
if(/^http:\/\/(www\.zbird\.com|bbs\.zbird\.com)(\/|\/auth\/login\/.*|\/diamond\/.*|\/weddings\/.*|\/zuanshi-duijie\/.*|\/accessory\/.*|\/e-education\/.*|\/faq\/.*|\/news\/.*|\/baike\/.*)|$/g.test(url)){
//if(/^http:\/\/(www2\.bridd\.com|bbs\.zbird\.com)(\/|\/auth\/login\/.*|\/diamond\/.*|\/weddings\/.*|\/zuanshi-duijie\/.*|\/accessory\/.*|\/e-education\/.*|\/faq\/.*|\/news\/.*|\/baike\/.*)|$/g.test(url)){

	getpost() //移动端页面跳转标记
    username=getCookie('zbird_pcagent');
    if(/^http:\/\/(www\.zbird\.com)(\/auth\/login\/.*)$/g.test(url)){
//	if(/^http:\/\/(www2\.bridd\.com)(\/auth\/login\/.*)(\/)$/g.test(url)){
    	username=1;
    }
    if (username==null || username==""){
        if( browser.versions.android || (browser.versions.iPhone && !browser.versions.iPad && !browser.versions.iMac) || browser.versions.BlackBerry || browser.versions.SymbianOS || browser.versions.wphone || (browser.versions.iPod && !browser.versions.iMac) || (browser.versions.iTouch && !browser.versions.iMac)){ 
        	url = url.replace(/(tianjin|xian|nanjing|chongqing|mianyang|zhengzhou|hefei|shanghai|hangzhou|qingdao|ningbo|wuhan|guangzhou|beijing|chengdu)\.zbird/, "m$1.zbird");
            url = url.replace("www.zbird.com","m.zbird.com");
			//url = url.replace("www2.bridd.com","m2.bridd.com");
        	url = url.replace(/\/news\/\D.*/,"/news");
        	url = url.replace(/\/faq-\d.*\/([^w].*|)/,"/faq");
        	url = url.replace("/faqw","/faq/");
        	url = url.replace(/\/news\/\D.*/,"/news");
        	url = url.replace("/e-education/w","/xuetang/");
        	url = url.replace("/e-education","/xuetang");
        	url = url.replace("/education","/xuetang");
        	url = url.replace("zuanshi-duijie","duijie");
        	window.location.href=url;
        }
    } else {
            if (username == 2) {
                    setCookie('zbird_pcagent',2,-1800)
            } else {
                    setCookie('zbird_pcagent',1,1800)
            }
    }
}

function getpost()
{
	var post = location.search.substring(1)
	var pos = post.indexOf('zbird_pcagent');
	if (pos == 2){
		setCookie('zbird_pcagent',2,1800);
	}else if (pos != -1){
		setCookie('zbird_pcagent',1,1800);
	}else{
		setCookie('zbird_pcagent',1,-1800);
	}
}

function getCookie(c_name)
{
	if (document.cookie.length>0)
	{
		c_start=document.cookie.indexOf(c_name + "=")
		if (c_start!=-1)
		{
			c_start=c_start + c_name.length+1 
			c_end=document.cookie.indexOf(";",c_start)
			if (c_end==-1) c_end=document.cookie.length
				return unescape(document.cookie.substring(c_start,c_end))
		} 
	}
	return ""
}

function setCookie(c_name,value,sec)
{
	var exdate=new Date()
	exdate.setSeconds(exdate.getSeconds()+sec)
	document.cookie=c_name+ "=" +escape(value)+
	((sec==null) ? "" : ";expires="+exdate.toGMTString())
}
	
