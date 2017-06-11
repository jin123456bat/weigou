var rubbish = datatables({
	table:$('#rubbish table'),
	ajax:{
		url:'./index.php?c=datatables&a=product',
		data:{
			isdelete:1,
		},
	},
	columns:[{
		data:'id',
		name:'product.id',
	},{
		data:'name',
		name:'product.name',
	},{
		data:'id',
		name:'product.id',
	},{
		data:'outside',
		name:'product.outside',
	},{
		data:'price',
		name:'product.price',
	},{
		data:'source',
		name:'(select admin.username from admin where admin.id=product.source limit 1)',
	},{
		data:'status',
		name:'product.status',
	},{
		data:'id',
		name:'product.id',
	},

	{
		data:'v1price',
		name:'product.v1price',
		visible:false,
	},{
		data:'v2price',
		name:'product.v2price',
		visible:false,
	},{
		data:'pic',
		name:'(select upload.path from product_img left join upload on upload.id=product_img.fid where product_img.pid=product.id order by product_img.position asc limit 1)',
		visible:false,
	},{
		data:'brand',
		name:'(select brand.name_cn from brand where brand.id=product.brand limit 1)',
		visible:false,
	},{
		data:'downStatus',
		name:'product.downStatus',
		visible:false,
	}],
	columnDefs:[{
		targets:0,
		render:function(data,full){
			return '<input type="checkbox" name="id[]" value="'+data+'">';
		}
	},{
		targets:1,
		render:function(data,full){
			return '<div style="display: inline-block;width: 70px;height: 60px;vertical-align:middle;"><img onerror="this.src=\'https://placeholdit.imgix.net/~text?txtsize=18&txt=%E6%AD%A4%E5%A4%84%E6%97%A0%E5%9B%BE&w=60&h=60\';" src="'+full.pic+'" width="60px" height="60px"></div><div style="display: inline-block;width: 70%;height: 60px;vertical-align:middle;"><div style="display: block;width: 100%;word-break: break-all;overflow: hidden;height: 40px;line-height: 20px;">'+data+'</div><div style="height:20px;line-height: 20px;width:100%;overflow: hidden;">品牌：'+(full.brand==null?'':full.brand)+'</div></div>';
		}
	},{
		targets:3,
		render:function(data,full){
			if(data == 0)
			{
				return '普通商品';
			}
			else if(data == 1)
			{
				return '进口商品';
			}
			else if(data == 2)
			{
				return '直供商品';
			}
			else if(data == 3)
			{
				return '直邮商品';
			}
			alert('商品outside错误');
		}
	},{
		targets:4,
		render:function(data,full){
			return '普通:'+data+'<br>白金:'+full.v1price+'<br>钻石:'+full.v2price;
		}
	},{
		targets:6,
		render:function(data,full){
			if(status==1)
			{
				return '销售中';
			}
			else if(status==0)
			{
				return '下架'+(full.downStatus==1?',编辑中':'');
			}
		}
	},{
		targets:7,
		render:function(data,full){
			let content = '';
			if($_recycle_product)
			{
				content += '<button class="btn btn-outline btn-xs restore" data-id="'+data+'">还原</button>';
			}
			if($_delete_product)
			{
				content += '<div class="space"></div><button class="btn btn-outline btn-xs clear_delete" data-id="'+data+'">彻底删除</button>';
			}
			return content;
		}
	}],
	pagesize:10,
	onRowLoaded:function(row){
		row.find('td:last').css('text-align','center');
		row.find('td:first').css({'padding-left':'20px','padding-right':'20px'});
	}
});

$('#rubbish_search').on('submit',function(){
	rubbish.search($(this).find('input').val());
	return false;
});

//批量操作
$('#rubbish #multipleBtn').on('click',function(){
	var id = getSelectedCheckbox($('#rubbish'));
	if($('#rubbish #multipleSelect').val() == '')
	{
		return false;
	}
	if(id.length == 0)
	{
		bootbox.alert('请选择商品');
		return false;
	}
	if($('#rubbish #multipleSelect').val() == 'clear_delete')
	{
		bootbox.confirm({
			message:'确认删除?',
			buttons:{
				cancel:{
					label: '<i class="fa fa-times"></i> 取消',
				},
				confirm:{
					label:'<i class="fa fa-check"></i> 确定',
				}
			},
			callback: function(result,a) {
				if(result) {
					rubbish.addAjaxParameter('customActionType','group_action');
					rubbish.addAjaxParameter('id',id);
					rubbish.addAjaxParameter('customActionName','clear_delete');
					rubbish.reload();
				}
			},
		});
	}
	return false;
});

$('#rubbish table').on('click','.restore',function(){
	var tr = $(this).parents('tr');
	var id = $(this).data('id');
	$.post('./index.php?m=ajax&c=product&a=restore',{id:id},function(response){
		if(response.code==1)
		{
			tr.remove();
		}
		else
		{
			bootbox.alert(response.result);
		}
	});
	return false;
}).on('click','.clear_delete',function(){
	var tr = $(this).parents('tr');
	var id = $(this).data('id');
	
	bootbox.confirm({
		message:'确认删除?这将要移除所有商品相关数据，尤其针对订单系统，也会产生一定影响',
		buttons:{
			cancel:{
				label: '<i class="fa fa-times"></i> 取消',
			},
			confirm:{
				label:'<i class="fa fa-check"></i> 确定',
			}
		},
		callback: function(result,a) {
			if(result) {
				$.post('./index.php?m=ajax&c=product&a=clear_delete',{id:id},function(response){
					if(response.code==1)
					{
						tr.remove();
					}
					else
					{
						bootbox.alert(response.result);
					}
				});
			}
		},
	});
	return false;
});