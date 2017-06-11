// JavaScript Document
var examine = datatables({
	table:$('#examine table'),
	ajax:{
		url:'./index.php?c=datatables&a=product',
		data:{
			isdelete:0,
			draft:0,
			examine:[0,2],
			status:0,
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
		data:'stock',
		name:'product.stock',
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
		data:'examine',
		name:'product.examine',
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
		}
	},{
		targets:4,
		render:function(data,full){
			return '普通:'+data+'<br>白金:'+full.v1price+'<br>钻石:'+full.v2price;
		}
	},{
		targets:7,
		render:function(data,full){
			if(full.examine==0)
			{
				return '<font style="color:#FF6600;">待审核</font>';
			}
			else if(full.examine==2)
			{
				return '<font color="#0099FF">审核中</font>';
			}
		}
	},{
		targets:8,
		render:function(data,full){
			content = '<button class="btn btn-outline btn-xs lookBtn" data-id="'+data+'">查看</button>';
			content += '<div class="space"></div><button class="btn btn-outline btn-xs editBtn" data-id="'+data+'">编辑</button>';
			content += '<div class="space"></div><div class="btn-group"><a class="btn btn-outline btn-xs examineBtn dropdown-toggle" aria-haspopup="true" aria-expanded="false" data-toggle="dropdown" data-id="'+data+'">审核</a><ul class="dropdown-menu dropdown-sm"><li><a class="examine_pass">通过</a></li><li><a class="examine_refuse">驳回</a></li></ul></div>';
			return content;
		}
	}],
	pagesize:10,
	onRowLoaded:function(row){
		row.find('td:last').css('text-align','center');
		row.find('td:first').css({'padding-left':'20px','padding-right':'20px'});
	}
});

$('#examine table').on('click','.editBtn',function(){
	var id = $(this).data('id');
	$.post('./index.php?m=ajax&c=product&a=examine2',{id:id},function(response){
		if(response.code==1)
		{
			window.location = './index.php?c=html&a=product_edit&type=examine&id='+id;
		}
	});
	return false;
}).on('click','.examine_pass',function(){
	var tr = $(this).parents('tr');
	var id = $(this).parents('.btn-group').find('.examineBtn').data('id');
	$.post('./index.php?m=ajax&c=product&a=examine_pass',{id:id},function(response){
		if(response.code==1)
		{
			tr.remove();
		}
		else
		{
			bootbox.alert(response.result);
		}
	});
}).on('click','.examine_refuse',function(){
	var id = $(this).parents('.btn-group').find('.examineBtn').data('id');
	$('#examineModal input[name=id]').val(id);
	$('#examineModal input[name=type]').val('examine');
	$('#examineModal').modal('show');
}).on('click','.lookBtn',function(){
	window.open('/index.php?c=index&a=product&id='+$(this).data('id'));
});


$('#examine .search').on('submit',function(){
	examine.addAjaxParameter('status',$(this).find('select').val());
	examine.search($(this).find('input').val());
	return false;
});