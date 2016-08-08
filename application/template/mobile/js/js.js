/**
 * Created by zxk on 2016/3/2.
 */
function toggleAttr(obj,attr,a,b){
    var attrValue = $(obj).attr(attr);
    if(attrValue == a){
        $(obj).attr(attr,b);
    }else{
        $(obj).attr(attr,a);
    }
}
//提示框
function alert_pations(callback){
    $(".alert_added_box").show();
    $(".alert_added_box").fadeOut(3000,callback);
}

function msg(a,callback)
{
	$('.alert_added_box .alert_added').html(a);
	alert_pations(callback);
}

function getParameter(name)
	{
		var url = window.location.href;
		var num = url.indexOf('?');
		var url = url.substr(num+1);
		content = url.split('&');
		for(var i=0;i<content.length;i++)
		{
			var result = content[i].split('=');
			if(result[0] == name)
			{
				return result[1];
			}
		}
		return null;
	}