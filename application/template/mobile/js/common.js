/** helper methods
=====================================================================================*/
function base64(e){
	  var i=0,o,c;
	  //支持btoa的浏览器用btoa
	  if(window.btoa)o=btoa(unescape(encodeURIComponent(e)));
	  else {
	    //创建XMLDOM对象
	    xml=new ActiveXObject("Microsoft.XMLDOM");
	    //创建节点
	    x=xml.createElement("x");
	    //放入16进制数据
	    x.text=encodeURIComponent(e).replace(/%..|./g,function(e){
	      var c=e.charCodeAt(0);
	      if(c!=37)e="0"+c.toString(16);
	      return e.slice(-2);
	    });
	    //输出字节流对象
	    x.dataType="bin.hex";
	    e=x.nodeTypedValue;
	    //清空数据
	    x.text="";
	    //设置数据类型
	    x.dataType="bin.base64";
	    //写入数据流对象
	    x.nodeTypedValue=e;
	    //输出字符串
	    o=x.text;
	  };
	  return o;
	}


function getCookie(name) {
    var strCookie = document.cookie;
    var arrCookie = strCookie.split("; ");
    var value;
    for (var i = 0; i < arrCookie.length; i++) {
        var arr = arrCookie[i].split("=");
        if (name == arr[0]) {
            value = arr[1];
            break;
        }
    }
    value = decodeURIComponent(value);
    return value;
}

function getUrlProperty(src) {
	   var url =src || location.search ; 
	   var theRequest = new Object();
	   if (url.indexOf("?") != -1) {
	      var str = url.substr(1);
	      strs = str.split("&");
	      for(var i = 0; i < strs.length; i ++) {
	         theRequest[strs[i].split("=")[0]]=decodeURIComponent(strs[i].split("=")[1]);
	      }
	   }
   return theRequest;
}

function xiuGetCookie(cookieName) {
	var doc_cookie = document.cookie, name = doc_cookie.indexOf(cookieName + "="), cookie;
	if (name !== -1) {
	    name += cookieName.length + 1;
	    cookie = doc_cookie.indexOf(";", name);
	    return unescape(doc_cookie.substring(name, (cookie === -1 ? doc_cookie.length : cookie)))
	}
	return
}


// 登录判断

function isLogin() {
    return xiuGetCookie('xiu.login.activity') && xiuGetCookie('xiu.login.tokenId');
}

function doLogin() {
	// alert('登录中...');
	var redirectUrl = location.href;
    var now = new Date() * 1;
    redirectUrl = encodeURIComponent(redirectUrl + '&t=' + now);
    // alert(redirectUrl);
    location.href = 'http://weixin.xiu.com/weixinlogin/toLogin?targetUrl=' + redirectUrl;
}

function setOpenid(jumpLink) {
	var followUrl = location.search;
    var now = new Date() * 1;
    if(followUrl ==""){
    	redirectUrl = encodeURIComponent(jumpLink + followUrl + '?t=' + now);
    }else{
    	redirectUrl = encodeURIComponent(jumpLink + followUrl + '&t=' + now);
    }
    console.log(redirectUrl);
    // 获取openid，下面的接口会把openid做为参数添加在targetUrl里
    location.href = 'http://weixin.xiu.com/weixininfo/toLogin?targetUrl=' + redirectUrl;
}

//create uuid mathod
// On creation of a UUID object, set it's initial value
function UUID(){
    this.id = this.createUUID();
}
 
// When asked what this Object is, lie and return it's value
UUID.prototype.valueOf = function(){ return this.id; };
UUID.prototype.toString = function(){ return this.id; };
 
//
// INSTANCE SPECIFIC METHODS
//
UUID.prototype.createUUID = function(){
    //
    // Loose interpretation of the specification DCE 1.1: Remote Procedure Call
    // since JavaScript doesn't allow access to internal systems, the last 48 bits 
    // of the node section is made up using a series of random numbers (6 octets long).
    //  
    var dg = new Date(1582, 10, 15, 0, 0, 0, 0);
    var dc = new Date();
    var t = dc.getTime() - dg.getTime();
    var tl = UUID.getIntegerBits(t,0,31);
    var tm = UUID.getIntegerBits(t,32,47);
    var thv = UUID.getIntegerBits(t,48,59) + '1'; // version 1, security version is 2
    var csar = UUID.getIntegerBits(UUID.rand(4095),0,7);
    var csl = UUID.getIntegerBits(UUID.rand(4095),0,7);
    // since detection of anything about the machine/browser is far to buggy, 
    // include some more random numbers here
    // if NIC or an IP can be obtained reliably, that should be put in
    // here instead.
    var n = UUID.getIntegerBits(UUID.rand(8191),0,7) + 
            UUID.getIntegerBits(UUID.rand(8191),8,15) + 
            UUID.getIntegerBits(UUID.rand(8191),0,7) + 
            UUID.getIntegerBits(UUID.rand(8191),8,15) + 
            UUID.getIntegerBits(UUID.rand(8191),0,15); // this last number is two octets long
    return tl + tm  + thv  + csar + csl + n; 
};
 
//Pull out only certain bits from a very large integer, used to get the time
//code information for the first part of a UUID. Will return zero's if there 
//aren't enough bits to shift where it needs to.
UUID.getIntegerBits = function(val,start,end){
 var base16 = UUID.returnBase(val,16);
 var quadArray = new Array();
 var quadString = '';
 var i = 0;
 for(i=0;i<base16.length;i++){
     quadArray.push(base16.substring(i,i+1));    
 }
 for(i=Math.floor(start/4);i<=Math.floor(end/4);i++){
     if(!quadArray[i] || quadArray[i] == '') quadString += '0';
     else quadString += quadArray[i];
 }
 return quadString;
};
 
//Replaced from the original function to leverage the built in methods in
//JavaScript. Thanks to Robert Kieffer for pointing this one out
UUID.returnBase = function(number, base){
 return (number).toString(base).toUpperCase();
};
 
//pick a random number within a range of numbers
//int b rand(int a); where 0 <= b <= a
UUID.rand = function(max){
 return Math.floor(Math.random() * (max + 1));
};
