// JavaScript Document
var product = function(){
	
	var getProvince = function(){
		var selected_value = [];
		$('#province input[name=fee_province]').each(function(index,value){
			if($(value).is(':checked'))
			{
				selected_value.push($(value).val());
			}
		});
		return selected_value;
	};
	
	var getImage = function(){
		var list = '';
		var detail = [];
		$('.imageArea .image').each(function(index,value){
			if($(value).find('.img-border').hasClass('red'))
			{
				list = $(value).attr('id');
			}
			detail.push($(value).attr('id'));
		});
		return {
			list:list,
			detail:detail,
		};
	};
	
	var getPrice = function(){
		var priceTable = [];
		$('.priceTable').each(function(index,value){
			if($(value).hasClass('ignore'))
			{
				return true;
			}
			var price = [];
			$(value).find('table.priceTableBase').each(function(index,value){
				if(!$(value).hasClass('display-none') && !$(value).hasClass('ignore'))
				{
					price.push({
						num:$.trim($(value).find('input[name=num]').val()),
						inprice:$.trim($(value).find('input[name=inprice]').val()),
						oldprice:$.trim($(value).find('input[name=oldprice]').val()),
						price:$.trim($(value).find('input[name=price]').val()),
						v1price:$.trim($(value).find('input[name=v1price]').val()),
						v2price:$.trim($(value).find('input[name=v2price]').val()),
					});
				}
			});
			
			priceTable.push({
				sku:$.trim($(value).find('input[name=sku]').val()),
				publish:$(value).find('select[name=publish]').val(),
				store:$(value).find('select[name=store]').val(),
				stock:$(value).find('input[name=stock]').val(),
				price:price,
			});
		});
		return priceTable;
	};
	
	var getBase = function(){
		return {
			bcategory:$('#bcategory3').val(),
			outside:$('#outside').val(),
			name:$.trim($('input[name=name]').val()),
			short_description:$.trim($('textarea[name=short_description]').val()),
			brand:brand_selector.val(),
			fee:$('input[name=fee]').val(),
			ztax:$('select[name=ztax]').val(),
			postTaxNo:$('select[name=postTaxNo]').val(),
			province:getProvince(),
			image:getImage(),
			description:umeditor.getContent(),
			MeasurementUnit:$('select[name=measurement]').val(),
			barcode:$.trim($('input[name=barcode]').val()),
			weight:$.trim($('input[name=weight]').val()),
			price:getPrice(),
			freetax:$('input[name=freetax]:checked').length,
		};
	};
	return {
		init:function(){
			if($('body').attr('id')!==undefined && $('body').attr('id')!==null)
			{
				try{
					$.post('./index.php?m=ajax&c=product&a=find',{id:$('body').attr('id')},function(response){
						if(response.code===1)
						{
							category = response.body.bcategory;
							for(var i=0;i<category.length;i++)
							{
								$('#bcategory'+(i+1)).val(category[i]);
								$('#bcategory'+(i+1)).trigger('change');
							}
							$('#outside').val(response.body.outside).triggerHandler('change');
							
							$('input[name=name]').val(response.body.name);
							$('textarea[name=short_description]').val(response.body.short_description);
							brand_selector.val(response.body.brand);
							$('input[name=fee]').val(response.body.fee);
							$('select[name=ztax]').val(response.body.ztax);
							$('select[name=postTaxNo]').val(response.body.postTaxNo);
							$('input[name=freetax]').prop('checked',response.body.freetax==1);
							
							$('input[name=fee_province]').each(function(index,input){
								if($.inArray($(input).val(),response.body.province) !== -1)
								{
									$(input).prop('checked',true);
								}
								else
								{
									$(input).prop('checked',false);
								}
							});
							
							$.each(response.body.image,function(index,image){
								if(image.position==2)
								{
									var template = $('#image').html();
									var reg = new RegExp("\\[([^\\[\\]]*?)\\]", 'igm');
									template = $(template.replace(reg, function (node, key) {
										return image[key];
									}));
									clickReplaceImage(template);
									template.insertBefore($('.upload-image'));
								}
								else if(image.position==1)
								{
									if($('.imageArea').find('.image[id='+image.id+']').length==0)
									{
										var template = $('#image').html();
										var reg = new RegExp("\\[([^\\[\\]]*?)\\]", 'igm');
										template = $(template.replace(reg, function (node, key) {
											return image[key];
										}));
										template.find('.img-border').addClass('red');
										template.find('.listImage').addClass('display-none');
										clickReplaceImage(template);
										template.insertBefore($('.upload-image'));
									}
									else if($('.imageArea').find('.image[id='+image.id+']').length==1)
									{
										$('.imageArea').find('.image[id='+image.id+']').find('.img-border').addClass('red');
										$('.imageArea').find('.image[id='+image.id+']').find('.listImage').addClass('display-none');
									}
									else
									{
										bootbox.alert('错误!多张列表图？');
									}
								}
							});
							
							umeditor.setContent(response.body.description);
							umeditor.addListener('contentChange',function(){
								updateChange(true,'umeditor');
							});
							
							$('select[name=measurement]').val(response.body.MeasurementUnit);
							$('input[name=barcode]').val(response.body.barcode);
							$('input[name=weight]').val(response.body.weight);
							
							$.each(response.body.product_publish,function(index,product_publish){
								var target = $('.priceTable:eq('+index+')');
								target.find('input[name=sku]').val(product_publish.sku);
								target.find('select[name=publish]').val(product_publish.publish_id);
								target.find('select[name=store]').val(product_publish.store);
								target.find('input[name=stock]').val(product_publish.stock);
								
								$.each(product_publish.product_publish_price,function(no,product_publish_price){
									var tb = target.find('.priceTableBase:eq('+no+')');
									tb.find('[name=num]').val(product_publish_price.num);
									tb.find('[name=inprice]').val(product_publish_price.inprice);
									tb.find('[name=oldprice]').val(product_publish_price.oldprice);
									tb.find('[name=price]').val(product_publish_price.price);
									tb.find('[name=v1price]').val(product_publish_price.v1price);
									tb.find('[name=v2price]').val(product_publish_price.v2price);
									tb.find('.plusPriceTable').trigger('click');
								});
								
								$('.createPriceTable').trigger('click');
							});
						}
						else
						{
							bootbox.alert(response.result);
						}
						
						$('input,textarea,select').on('change',function(){
							updateChange(true);
						});
					});
				}
				catch(e)
				{
					bootbox.alert('发生异常！');
				}
			}
		},
		//保存草稿
		getDraft:function(){
			var info = getBase();
			info.examine = 0;
			info.examine_price = 0;
			info.examine_stock = 0;
			info.examine_final = 0;
			
			info.draft = 1;
			//info.isnew = 1;
			//info.isdelete=0;
			//info.status=0;
			info.downStatus = 0;
			//info.source = 0;
			info.modifytime = timestamp();
			return info;
		},
		getOverview:function(){
			
		},
		//提交审核
		getSubmit:function(){
			var info = getBase();
			info.examine = 0;
			info.examine_price = 0;
			info.examine_stock = 0;
			info.examine_final = 0;
			
			info.draft = 0;//只是draft改变了
			//info.isnew = 1;
			//info.isdelete=0;
			//info.status=0;
			info.downStatus = 0;
			//info.source = 0;
			info.modifytime = timestamp();
			return info;
		},
		getReEdit:function(){
			var info = getBase();
			//info.examine = 0;
			//info.examine_price = 0;
			//info.examine_stock = 0;
			//info.examine_final = 0;
			
			info.isnew = 0;
			//info.isdelete=0;
			//info.status=0;
			//info.draft = 0;
			info.downStatus = 1;
			//info.source = 0;
			info.modifytime = timestamp();
			return info;
		},
		/*//原来下架了，第二次提交审核
		getReSubmit:function(){
			var info = getBase();
			info.examine = 0;
			info.draft = 0;
			
			info.isnew = 0;
			info.status=0;
			//第二次提交审核还是不强制更改删除状态把
			//info.isdelete = 0;
			info.downStatus = 0;
			info.source = 0;
			info.modifytime = timestamp();
			return info;
		},*/
		validate:function(){
			var base = getBase();
			if(base.bcategory===undefined || base.bcategory===null || base.bcategory.length===0)
			{
				$('#bcategory3').addClass('has-error');
				if($('#bcategory3').parents('.col-md-3').find('.help-block').length>0)
				{
					$('#bcategory3').parents('.col-md-3').find('.help-block').addClass('has-error').text('请选择分类');
				}
				else
				{	
					$('<span class="help-block has-error">请选择分类</span>').insertAfter($('#bcategory3'));
				}
				
				$('#bcategory3').on('change',function(){
					$('#bcategory3').removeClass('has-error');
					$('#bcategory3').parents('.col-md-3').find('.help-block').remove();
				});
				return false;
			}
			
			if(base.name.length===0)
			{
				$('input[name=name]').closest('.form-group').addClass('has-error');
				if($('input[name=name]').parents('.col-md-9').find('.help-block').length>0)
				{
					$('input[name=name]').parents('.col-md-9').find('.help-block').addClass('has-error').text('请填写商品名称');
				}
				else
				{
					$('<span class="help-block has-error">请填写商品名称</span>').insertAfter($('input[name=name]'));
				}
				
				$('input[name=name]').on('change',function(){
					if($.trim($(this).val().length)!==0)
					{
						$('input[name=name]').closest('.form-group').removeClass('has-error');
						$('input[name=name]').parents('.col-md-9').find('.help-block').remove();
					}
				});
				return false;
			}
			
			if(parseInt(base.outside)===2 || parseInt(base.outside)===3)
			{
				var targets = null;
				if(parseInt(base.outside)===2)
				{
					targets = $('select[name=ztax]');
				}
				else
				{
					targets = $('select[name=postTaxNo]');
				}
				if(targets.val()===null || targets.val()===undefined || targets.val().length===0)
				{
					targets.closest('.form-group').addClass('has-error');
					if(targets.parents('.col-md-9').find('.help-block').length>0)
					{
						targets.parents('.col-md-9').find('.help-block').addClass('has-error').text('请选择税率');
					}
					else
					{	
						$('<span class="help-block has-error">请选择税率</span>').insertAfter(targets);
					}

					targets.on('change',function(){
						targets.closest('.form-group').removeClass('has-error');
						targets.parents('.col-md-9').find('.help-block').remove();
					});
					return false;
				}
			}
			
			if(base.brand===null || base.brand===undefined || base.brand.length===0)
			{
				$('select[name=brand]').closest('.form-group').addClass('has-error');
				if($('select[name=brand]').parents('.col-md-9').find('.help-block').length>0)
				{
					$('select[name=brand]').parents('.col-md-9').find('.help-block').addClass('has-error').text('请选择商品品牌');
				}
				else
				{
					$('<span class="help-block has-error">请选择商品品牌</span>').insertAfter($('select[name=brand]'));
				}
				
				$('select[name=brand]').on('change',function(){
					$('select[name=brand]').closest('.form-group').removeClass('has-error');
					$('select[name=brand]').parents('.col-md-9').find('.help-block').remove();
				});
				return false;
			}
			
			if(base.image.list==='')
			{
				$('.imageArea').closest('.form-group').addClass('has-error');
				return false;
			}
			
			
			if(base.barcode.length===0)
			{
				$('input[name=barcode]').addClass('has-error');
				if($('input[name=barcode]').parents('.col-md-3').find('.help-block').length>0)
				{
					$('input[name=barcode]').parents('.col-md-3').find('.help-block').addClass('has-error').text('请填写条形码');
				}
				else
				{	
					$('<span class="help-block has-error">请选择分类</span>').insertAfter($('input[name=barcode]'));
				}
				
				$('input[name=barcode]').on('change',function(){
					$('input[name=barcode]').removeClass('has-error');
					$('input[name=barcode]').parents('.col-md-3').find('.help-block').remove();
				});
				return false;
			}
			
			if(base.weight.length===0)
			{
				$('input[name=weight]').addClass('has-error');
				if($('input[name=weight]').parents('.col-md-3').find('.help-block').length>0)
				{
					$('input[name=weight]').parents('.col-md-3').find('.help-block').addClass('has-error').text('请填写重量');
				}
				else
				{	
					$('<span class="help-block has-error">请填写重量</span>').insertAfter($('input[name=weight]'));
				}
				
				$('input[name=weight]').on('change',function(){
					$('input[name=weight]').removeClass('has-error');
					$('input[name=weight]').parents('.col-md-3').find('.help-block').remove();
				});
				return false;
			}
			return true;
		}
	};
}();

$(document).ready(function(){
	product.init();
});