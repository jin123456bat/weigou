var collection = function(){
	var prototype = [];//属性表
	var drawTimes = 0;//每执行一次drawCollectionTables都会自动+1
	var hasRadioPrototype = false;//是否有可选属性
	var RadioPrototypeNum = 0;
	var container = $('#prototype_collection tbody');
	
	return {
		clearInput:function(){
			$('input[name=prototype_name]').val('');
			$('#prototype_text').find('input').val('');
			$('#prototype_radio').find('input[name=prototype_value]').tagsinput('removeAll');
		},
		getPrototypeTables:function(){
			return prototype;
		},
		setCollectionDefaultValue:function(prototype,price,v1price,v2price,stock,sku,logo,logo_id,available){
			container.find('input[name=price][data-prototype="'+prototype+'"]').val(price);
			container.find('input[name=v1price][data-prototype="'+prototype+'"]').val(v1price);
			container.find('input[name=v2price][data-prototype="'+prototype+'"]').val(v2price);
			container.find('input[name=stock][data-prototype="'+prototype+'"]').val(stock);
			container.find('input[name=sku][data-prototype="'+prototype+'"]').val(sku);
			container.find('input[name=logo][data-prototype="'+prototype+'"]').val(logo_id).parent().find('img').attr('src',logo);
			if(available==0 ||available=='0')
			{
				container.find('input[name=available][data-prototype='+prototype+']').removeAttr('checked');
			}
		},
		getCollectionTables:function(){
			var collection = [];
			var total_stock = 0;
			$.each(container.find('tr'),function(index,value){
				var price = $(value).find('input[name=price]').val();
				var v1price = $(value).find('input[name=v1price]').val();
				var v2price = $(value).find('input[name=v2price]').val();
				var stock = $(value).find('input[name=stock]').val();
				var sku  = $(value).find('input[name=sku]').val();
				var logo = $(value).find('input[name=logo]').val();
				var available = $(value).find('input[name=available]:checked').length;
				var content = $(value).find('input[name=price]').attr('data-prototype');
				
				total_stock += parseInt(stock);
				
				var temp = {
					content:content,
					price:price,
					v1price:v1price,
					v2price:v2price,
					stock:stock,
					sku:sku,
					available:available,
					logo:logo,
				};
				collection.push(temp);
			});
			
			if(collection.length > 0 && $('input[name=auto_stock]:checked').length)
			{
				$('input[name=stock]:first').val(total_stock);
			}
			
			return collection;
		},
		addPrototype:function(name,type,value)
		{
			//已经存在的属性不在添加
			for(var i=0;i<prototype.length;i++)
			{
				if(prototype[i].name==name)
					return false;
			}
			
			var temp = {
				name:name,
				type:type,
				value:value,
			}
			
			if(type=='radio')
			{
				hasRadioPrototype = true;
				RadioPrototypeNum++;
			}
			prototype.push(temp);
			this.showBindTable();
			this.clearInput();
		},

		getBind:function()
		{
			var bind = [];
			var tr = $('#BindTable tbody tr').each(function(index,value){
				var data = {
					content:$(value).find('select[name=content]').val(),
					num:$(value).find('input[name=num]').val(),
					inprice:$(value).find('input[name=inprice]').val(),
					price:$(value).find('input[name=price]').val(),
					v1price:$(value).find('input[name=v1price]').val(),
					v2price:$(value).find('input[name=v2price]').val(),
                    unit: $(value).find('input[name=unit]').val(),
                    sort: $(value).find('input[name=sort]').val(),
				}
				bind.push(data);
			});
			return bind;
		},

		setBind:function(content,num,unit,inprice,price,v1price,v2price,st){
			$('#BindTable .createBindBtn').trigger('click');

			$('#BindTable tbody tr').each(function(index,value){
				
				if ($(value).find('input[name=num]').val() == '' && index != 0)
				{
					$(value).find('select[name=content]').val(content);
					$(value).find('input[name=num]').val(num);
                    $(value).find('input[name=unit]').val(unit);
                    $(value).find('input[name=sort]').val(st);
					$(value).find('input[name=inprice]').val(inprice);
					$(value).find('input[name=price]').val(price);
					$(value).find('input[name=v1price]').val(v1price);
					$(value).find('input[name=v2price]').val(v2price);
					return false;
				}
			});
		},

		showBindTable:function()
		{
			if (hasRadioPrototype)
			{
				$('.radioPrototypeInBind').removeClass('display-none');
				$('.radioPrototypeInBind select').empty();
			}
			else
			{
				$('.radioPrototypeInBind').addClass('display-none');
			}

			for(var i=0;i<prototype.length;i++)
			{
				if (prototype[i].type=='radio')
				{
					var option = $('.radioPrototypeInBind select option');
					var radioPrototypeValue = prototype[i].value.split(',');
					if (option.length==0)
					{
						for(var j=0;j<radioPrototypeValue.length;j++)
						{
							var str = radioPrototypeValue[j];
							$('.radioPrototypeInBind select').append('<option value="'+str+'">'+str+'</option>');
						}
					}
					else
					{
						for(var m=0;m<radioPrototypeValue.length;m++)
						{
							$('.radioPrototypeInBind select').empty();
							for(var z=0;z<radioPrototypeValue.length;z++)
							{
								var option_t = option.clone();
								for(var t = 0;t<option_t.length;t++)
								{
									$(option_t[t]).html($(option_t[t]).html()+','+radioPrototypeValue[z]);
									$(option_t[t]).val($(option_t[t]).val()+','+radioPrototypeValue[z]);
								}
								$('.radioPrototypeInBind select').append(option_t);
							}
							
						}
						
					}
				}
			}
			//$('.radioPrototypeInBind select').append('<option value="'+str+'">'+str+'</option>');

		},
		
		removePrototype:function(name)
		{
			hasRadioPrototype = false;
			for(var i=0;i<prototype.length;i++)
			{
				if(prototype[i].name == name)
				{
					prototype.splice(i,1);
					continue;
				}
				if (prototype[i].type=='radio')
				{
					hasRadioPrototype = true;
				};
			}

			this.showBindTable();
			this.drawPrototypeTables();
			this.drawCollectionTables();
		},
		
		setPrice:function(name,price){
		},
		setStock:function(){
		},
		setSku:function(){
		},
		
		drawPrototypeTables:function(){
			$('#prototype_container').empty();
			for(var i=0;i<prototype.length;i++)
			{
				var name = prototype[i].name;
				if(prototype[i].type=='text')
				{
					var type = '固定';
					var value = prototype[i].value;
				}
				else
				{
					var type = '可选';
					var value = '';
					var temp = prototype[i].value.split(',');
					for(var index=0;index<temp.length;index++)
					{
						value += '<button onClick="return false;" class="btn btn-xs btn-circle yellow disabled">'+temp[index]+'</button>';
					}
				}
				
				
				var tpl = $('<tr><td>'+name+'</td><td>'+type+'</td><td>'+value+'</td><td><button class="btn btn-xs btn-circle prototype_remove">删除</button></td></tr>');
				
				$('#prototype_container').append(tpl);
			}
		},
		
		drawCollectionTables:function()
		{
			drawTimes++;
			
			container.empty();
			
			for(var i=0;i<prototype.length;i++)
			{
				if(prototype[i].type=='radio')
				{
					var radioPrototypeValue = prototype[i].value.split(',');
					if(container.find('tr').length==0)
					{
						for(var index=0;index < radioPrototypeValue.length; index++)
						{
							var tpl = $('<tr><td>'+radioPrototypeValue[index]+'</td><td><input data-prototype="'+radioPrototypeValue[index]+'" name="price" type="text"></td><td><input data-prototype="'+radioPrototypeValue[index]+'" name="v1price" type="text"></td><td><input data-prototype="'+radioPrototypeValue[index]+'" name="v2price" type="text"></td><td><input data-prototype="'+radioPrototypeValue[index]+'" name=stock type="text"></td><td><input data-prototype="'+radioPrototypeValue[index]+'" name=sku type="text"></td><td><input data-prototype="'+radioPrototypeValue[index]+'" type="hidden" name="logo"><img class="upload" src="" width="50" height="50"></td><td><input data-prototype="'+radioPrototypeValue[index]+'" type="checkbox" name="available" checked="checked"></td></tr>');
							container.append(tpl);
						}
					}
					else
					{
						$('<th></th>').insertAfter($('#prototype_collection thead th:first'));
						var tr = container.find('tr');
						container.find('tr').remove();
						var num = tr.length;
						for(var index=0;index < radioPrototypeValue.length; index++)
						{
							var tclone = tr.clone();
							
							$.each(tclone.find('input'),function(input_index,value){
								$(value).attr('data-prototype',$(value).data('prototype')+','+radioPrototypeValue[index]);
							});
							
							$(tclone[0]).prepend('<td rowspan="'+num+'">'+radioPrototypeValue[index]+'</td>');
							container.append(tclone);
						}
					}
				}
			}
		}
	};
}();