var brand = function(obj,args){
	var data = {};
	
	var $_case = [];
	
	var $_blur = true;
	
	var $_value = null;
	
	var url = args.ajax.url;
	$.ajax({
		method:'get',
		url:url,
		async:false,
		dataType:'json',
		success:function(response){
			data = response;
		}
	});
	
	var div = $('<div tabindex="0"></div>');
	
	var style = ['height','border','outline','display','width','cssText'];
	for(var i=0;i<style.length;i++)
	{
		var name = style[i];
		var value = obj.css(name);
		div.css(name,value);
	}
	div.css('cursor','pointer');
	div.css('overflow','hidden');
	
	//设置placeholder
	var placeholder = obj.attr('placeholder') || '请选择...';
	div.html(placeholder);
	
	//添加虚拟下拉select
	div.insertAfter(obj).on('click',function(){
		if($_blur)
		{
			focus();
		}
		else
		{
			blur();
		}
	});
	
	var pulldown = $('<div id="pulldown_brand"></div>');
	pulldown.css({
		zIndex:'1000',
		position:'initial',
		height:'auto',
		width:obj.css('width'),
		display:'none',
	});
	
	var pulldown_head = $('<div id="pulldown_brand_head"></div>');
	pulldown.append(pulldown_head);
	var pulldown_head_index = $('<div id="pulldown_brand_head_index"></div>');
	var index = $('<div class="pulldown_brand_head_index_item active" data-index="all">全部品牌</div>');
	pulldown_head_index.append(index);
	for(i=0;i<26;i++)
	{
		$_case.push(String.fromCharCode(65+i).toUpperCase());
		index = $('<div class="pulldown_brand_head_index_item" data-index="'+String.fromCharCode(65+i).toUpperCase()+'">'+String.fromCharCode(65+i).toUpperCase()+'</div>');
		pulldown_head_index.append(index);
	}
	index = $('<div class="pulldown_brand_head_index_item" data-index="other">其他</div>');
	pulldown_head_index.append(index);
	
	pulldown_head.append(pulldown_head_index);
	
	var pulldown_head_search = $('<div id="pulldown_head_search"><form><input type="text" id="pulldown_head_search_input" placeholder="品牌名称关键字查询"><input type="submit" id="pulldown_head_search_submit" value="搜索"></form></div>');
	pulldown_head.append(pulldown_head_search);
	
	var pulldown_body = $('<div id="pulldown_brand_body"></div>');
	var pulldown_body_quit = $('<div id="pulldown_brand_body_quit">取消选择</div>');
	pulldown_body.append(pulldown_body_quit);
	
	pulldown.append(pulldown_body);
	
	
	var ele = div;
	if(args.position)
	{
		ele = args.position(div);
	}
	pulldown.insertAfter(ele);
	
	var focus = function(){
		$_blur = false;
		pulldown.css('display','block');
		pulldown_head_index.find('.pulldown_brand_head_index_item:first').trigger('click');
	};
	
	var blur = function(setValue){
		$_blur = true;
		pulldown.css('display','none');
		if(setValue)
		{
			$_value = setValue;
		}
	};
	
	var setValue = function(value){
		if(value)
		{
			$.each(data,function(index,brand){
				if(brand.id === value)
				{
					div.html(brand.name_cn);
					blur(value);
					return false;
				}
			});
		}
	};
	
	var getValue = function(){
		return $_value;
	};
	
	var load = function(index){
		pulldown_body.find('.pulldown_brand_body_item').remove();
		if(index === 'all')
		{
			for(var i=0;i<data.length;i++)
			{
				var word = $.trim(data[i].name_en).substr(0,1).toUpperCase();
				if($.inArray(word,$_case)===-1)
				{
					word = '&nbsp;';
				}
				var pulldown_body_item = $('<div class="pulldown_brand_body_item" data-id="'+data[i].id+'"><div class="pulldown_brand_body_item_index">'+word+'</div><div class="pulldown_brand_body_item_body">'+data[i].name_cn+'</div></div>');
				pulldown_body_item.find('.pulldown_brand_body_item_body').css('width',div.width()-40);
				pulldown_body.append(pulldown_body_item);
			}
		}
		else if(index==='other')
		{
			for(i=0;i<data.length;i++)
			{
				var word = $.trim(data[i].name_en).substr(0,1).toUpperCase();
				if($.inArray(word,$_case)===-1)
				{
					word = '&nbsp;';
					var pulldown_body_item = $('<div class="pulldown_brand_body_item" data-id="'+data[i].id+'"><div class="pulldown_brand_body_item_index">'+word+'</div><div class="pulldown_brand_body_item_body">'+data[i].name_cn+'</div></div>');
					pulldown_body_item.find('.pulldown_brand_body_item_body').css('width',div.width()-40);
					pulldown_body.append(pulldown_body_item);
				}
			}
		}
		else
		{
			for(var i=0;i<data.length;i++)
			{
				var word = $.trim(data[i].name_en).substr(0,1).toUpperCase();
				if(index === word)
				{
					var pulldown_body_item = $('<div class="pulldown_brand_body_item" data-id="'+data[i].id+'"><div class="pulldown_brand_body_item_index">'+word+'</div><div class="pulldown_brand_body_item_body">'+data[i].name_cn+'</div></div>');
					pulldown_body_item.find('.pulldown_brand_body_item_body').css('width',div.width()-40);
					pulldown_body.append(pulldown_body_item);
				}
			}
		}
		pulldown.find('form').trigger('submit');
	};
	
	obj.css('display','none');
	
	pulldown.on('click','.pulldown_brand_head_index_item',function(){
		$(this).siblings().removeClass('active');
		$(this).addClass('active');
	}).on('click','.pulldown_brand_head_index_item',function(){
		load($(this).data('index'));
	}).on('submit','form',function(){
		var form = $(this);
		pulldown_body.find('.pulldown_brand_body_item').each(function(index,value){
			var string = $(value).find('.pulldown_brand_body_item_body').text().toLocaleLowerCase();
			var keyword = $.trim(form.find('input').val()).toLocaleLowerCase();
			if(keyword.length>0)
			{
				if(string.indexOf(keyword) === -1)
				{
					$(value).addClass('display-none');
				}
				else
				{
					$(value).removeClass('display-none');
				}
			}
		});
		return false;//搜索加载
	}).on('click','#pulldown_brand_body_quit',function(){
		$_value = null;
		div.html(placeholder);
		blur();
	}).on('click','.pulldown_brand_body_item',function(){
		div.html($(this).find('.pulldown_brand_body_item_body').text());
		blur($(this).data('id'));
	});
	
	return {
		val:function(value){
			if(value)
			{
				return setValue(value);
			}
			else
			{
				return getValue();
			}
		},
		
	};
};