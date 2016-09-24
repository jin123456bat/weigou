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


window.confirm = function(msg,callback)
{
	var tpl = $('<div id="delete_confirm" class="g_tips_mask" style="display:none;"><div class="gtm_details"><div class="cc_info">'+msg+'</div><div class="cc_btn"><div class="confirm dcc">确定</div><div class="cancel dcc">取消</div></div></div></div>');
	$('body').append(tpl);
	tpl.show();
	tpl.find('.confirm').on('click',function(){
		if(typeof callback=='function')
		{
			callback(true);
		}
		tpl.remove();
		return false;
	});
	tpl.find('.cancel').on('click',function(){
		if(typeof callback=='function')
		{
			callback(false);
		}
		tpl.remove();
		return false;
	});
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