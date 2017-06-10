/*获取当前时间戳*/
var timestamp = function(){
	"use strict";
	
	return Date.parse( new Date())/1000;
};

/*时间戳转时间*/
var unixtotime = function(unixTime, isFull, timeZone) {
	if (typeof (timeZone) == 'number')
	{
		unixTime = parseInt(unixTime) + parseInt(timeZone) * 60 * 60;
	}
	var time = new Date(unixTime * 1000);
	var ymdhis = "";
	ymdhis += time.getUTCFullYear() + "-";
	ymdhis += (time.getUTCMonth()+1) + "-";
	ymdhis += time.getUTCDate();
	if (isFull === true)
	{
		ymdhis += " " + lpad(time.getUTCHours(),2) + ":";
		ymdhis += lpad(time.getUTCMinutes(),2) + ":";
		ymdhis += lpad(time.getUTCSeconds(),2);
	}
	return ymdhis;
}

$('img').on('error',function(){
	$(this).prop('src','https://placeholdit.imgix.net/~text?txtsize=18&txt=%E6%AD%A4%E5%A4%84%E6%97%A0%E5%9B%BE&w=60&h=60');
})

setInterval(function(){
	var datetimeController = new Date();
	var date = datetimeController.getFullYear()+'-'+lpad(datetimeController.getMonth()+1,2)+'-'+lpad(datetimeController.getDate(),2);
	$('.date').text(date);

	var time = lpad(datetimeController.getHours(),2)+':'+lpad(datetimeController.getMinutes(),2);
	$('.time').text(time);

	var week = datetimeController.getDay();
	var weekArray = ['日','一','二','三','四','五','六'];
	var week = '星期'+weekArray[week];
	$('.week').text(week);
},1000);


//兼容锚点
var getHref = function(){
	pos = window.location.href.indexOf('#');
	if(pos!=-1)
	{
		return window.location.href.substring(pos+1);
	}
}
if(getHref())
{
	$('a[href=#'+getHref()+']').trigger('click');
}

function loadScript(url){ 
	var ga = document.createElement('script'); 
    ga.type = 'text/javascript'; 
    ga.async = true; 
    ga.src = url; 
    var s = document.getElementsByTagName('script')[0]; 
    s.parentNode.insertBefore(ga, s); 
} 

var post = function(url,data){
	var form = $('<form action="'+url+'" method="post" targets="_blank"><button type="submit"></button></form>');
	if(data)
	{
		$.each(data,function(index,value){
			if('object' == typeof value)
			{
				$.each(value,function(key,val){
					let tpl = '<input type="hidden" name="'+index+'[]" value="'+val+'">';
					form.append(tpl);
				});
			}
			else
			{
				let tpl = '<input type="hidden" name="'+index+'" value="'+value+'">';
				form.append(tpl);
			}
		});
	}
	$(document.body).append(form);
	form.submit();
}