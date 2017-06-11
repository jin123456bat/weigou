// JavaScript Document
var dt = datatables({
	table:$('#draft table'),
	ajax:{
		data:{
			//这里其实不需要考录draft=1还是0，，因为不管是1还是0 只要没通过审核 都应该可以在draft中看到
			isdelete:0,
			status:0,
			where:'(downStatus=1 and status=0) or draft=1 or (draft=0 and (examine = -1 or examine_stock = -1 or examine_price = -1 or examine_final =-1))',
		},
		url:'./index.php?c=datatables&a=product',
	},
	columns:[{
		data:'id',
		name:'product.id',
	},{
		data:'name',
		name:'product.name',
	},{
		data:'examine',
		name:'product.examine',
	},{
		data:'isnew',
		name:'product.isnew',
	},{
		data:'modifytime',
		name:'product.modifytime',
	},{
		data:'id',
		name:'product.id',
	},{
		data:'id',
		name:'product.id',
	},{
		data:'status',
		name:'product.status',
		visible:false,
	},{
		data:'draft',
		name:'product.draft',
		visible:false,
	},{
		data:'stock',
		name:'product.stock',
		visible:false,
	},{
		data:'examine_result',
		name:'product.examine_result',
		visible:false,
	},{
		data:'examine_description',
		name:'product.examine_description',
		visible:false,
	},{
		data:'examine_stock_result',
		name:'product.examine_stock_result',
		visible:false,
	},{
		data:'examine_stock_description',
		name:'product.examine_stock_description',
		visible:false,
	},{
		data:'examine_price_description',
		name:'product.examine_price_description',
		visible:false,
	},{
		data:'examine_stock',
		name:'product.examine_stock',
		visible:false,
	},{
		data:'examine_price',
		name:'product.examine_price',
		visible:false,
	},{
		data:'examine_price_result',
		name:'product.examine_price_result',
		visible:false,
	},{
		data:'downStatus',
		name:'product.downStatus',
		visible:false,
	},{
		data:'examine_final',
		name:'product.examine_final',
		visible:false,
	},{
		data:'examine_final_result',
		name:'product.examine_final_result',
		visible:false,
	},{
		data:'examine_final_description',
		name:'product.examine_final_description',
		visible:false,
	},{
		data:'downStatus',
		name:'product.downStatus',
		visible:false,
	}],
	columnDefs:[{
		targets:2,
		render:function(data,full){
			if(full.draft == 1)
			{
				return '编辑中';
			}
			else if(full.draft == 0)
			{
				if(full.downStatus==1 && full.status==0)
				{
					return '<font color="#FF6600">重新编辑</font>';
				}
				if(full.examine_final == -1)
				{
					if($.trim(full.examine_final_description).length == 0)
					{
						var tooltips = '<div>'+'<font style="color:#FF3300;font-weight: 700;">'+full.examine_final_result+'</font>'+'</div>';
						return '<font style="color:#FF3300;font-weight: 700;">未通过</font>'+tooltips;
					}
					else
					{
						var tooltips = '<div style="cursor:pointer;" class="popover-div" data-trigger="hover" title="审核失败" data-content="'+full.examine_final_description+'">'+'<font style="color:#FF3300;font-weight: 700;">'+full.examine_final_result+'</font>'+'</div>';
						return '<font style="color:#FF3300;font-weight: 700;">未通过</font>'+tooltips;
					}
				}
				else if(full.examine == -1 || full.examine_price == -1 || full.examine_stock == -1)
				{
					var result = [
						full.examine!=-1?'':full.examine_result,
						full.examine_stock!=-1?'':full.examine_stock_result,
						full.examine_price!=-1?'':full.examine_price_result,
					];
					result = $.grep(result, function (n) {return $.trim(n).length != 0; });
					var content = [
						full.examine!=-1?'':full.examine_description,
						full.examine_stock!=-1?'':full.examine_stock_description,
						full.examine_price!=-1?'':full.examine_price_description,
					];
					content = $.grep(content, function (n) {return $.trim(n).length != 0; });
					if(content.length!=0)
					{
						var tooltips = '<div style="cursor:pointer;" class="popover-div" data-trigger="hover" title="审核失败" data-content="'+content.join(',')+'">'+'<font style="color:#FF3300;font-weight: 700;">'+result.join(',')+'</font>'+'</div>';
					}
					else
					{
						var tooltips = '<div>'+'<font style="color:#FF3300;font-weight: 700;">'+result.join(',')+'</font>'+'</div>';
					}
					
					return '<font style="color:#FF3300;font-weight: 700;">未通过</font>'+tooltips;
				}
			}
		}
	},{
		targets:3,
		render:function(data,full){
			if(data == 1)
			{
				return '新增';
			}
			else
			{
				return '修改';
			}
		}
	},{
		targets:4,
		render:function(data,full){
			return unixtotime(data,true,8);
		}
	},{
		targets:5,
		render:function(data,full){
			return '<button data-id="'+data+'" class="btn btn-outline btn-xs editBtn">编辑</button>';
		}
	},{
		targets:6,
		render:function(data,full){
			return '<button data-id="'+data+'" class="btn btn-outline btn-xs removeBtn">删除</button>';
		}
	}],
	pagesize:10,
	onRowLoaded:function(row){
		row.find('td:last').css('text-align','center');
		row.find('td:last').prev('td').css('text-align','center');
	},
	afterTableLoaded:function(table){
		table.find('.popover-div').popover();
	}
});

$('#draft table').on('click','.editBtn',function(){
	window.location = './index.php?c=html&a=product_edit&type=draft&id='+$(this).data('id');
}).on('click','.removeBtn',function(){
	var tr = $(this).parents('tr');
	var id = $(this).data('id');
	bootbox.confirm({
		message:'确认删除这个商品?',
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
				$.post('./index.php?m=ajax&c=product&a=remove',{id:id},function(response){
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