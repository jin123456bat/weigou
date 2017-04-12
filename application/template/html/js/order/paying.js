var paying = datatables({
	table:$('#paying table'),
	ajax:{
		url:'./index.php?c=datatables&a=order',
		data:{
			status:1,
			pay_status:0,
			isdelete:0,
		},
	},
	sort:[{
		column:2,
		dir:'desc',
	}],
	columns:[{
		data:'orderno',
		name:'order.orderno',
	}, 
	//订单号
	{
		data:'orderno',
		name:'order.orderno',
	},
	//下单时间
	{
		data:'createtime',
		name:'order.createtime',
	},{
		data:'username',
		name:'(select user.name from user where user.id=order.uid limit 1)',
		visible:false,
	},
	//收货人信息
	{
		data:'address_name',
		name:'order.address_name',
	},{
		data:'address_province',
		name:'order.address_province',
		visible:false,
	},{
		data:'address_city',
		name:'order.address_city',
		visible:false,
	},{
		data:'address_county',
		name:'order.address_county',
		visible:false,
	},{
		data:'address_address',
		name:'order.address_address',
		visible:false,
	},{
		data:'address_telephone',
		name:'order.address_telephone',
		visible:false,
	},
	//支付信息 
	{
		data:'pay_status',
		name:'order.pay_status',
	},{
		data:'pay_money',
		name:'order.pay_money',
		visible:false,
	},{
		data:'pay_type',
		name:'order.pay_type',
		visible:false,
	},{
		data:'device',
		name:'order.device',
		visible:false,
	},
	//应付金额
	{
		data:'orderamount',
		name:'order.orderamount',
	},
	//订单类型
	{
		data:'orderType',//判断是否未空，未空普通订单，否则是团购订单
		name:'(select task_user.orderno from task_user where task_user.orderno=order.orderno limit 1)',
	},
	//订单状态
	{
		data:'way_status',//是否发货
		name:'order.way_status',
	},{
		data:'refund_money',
		name:'(select sum(money) from refund where refund.orderno=order.orderno and refund.status=1)',
		visible:false,
	},{
		data:'task_user_status',
		name:'(select task_user.status from task_user where task_user.orderno=order.orderno limit 1)',
		visible:false,
	},{
		data:'need_erp',
		name:'order.need_erp',
		visible:false,
	},{
		data:'receive',
		name:'order.receive',
		visible:false,
	},{
		data:'status',
		name:'order.status',
		visible:false,
	},
	//操作
	{
		data:'orderno',
		name:'order.orderno',
	},{
		data:'erp',
		name:'order.erp',
		visible:false,
	}],
	columnDefs:[{
		targets:0,
		render:function(data){
			return '<input type="checkbox" name="id[]" value="'+data+'">';
		}
	},{
		targets:2,
		render:function(data,full){
			return full.username+'<br>'+unixtotime(data,true,8);
		}
	},{
		targets:4,
		render:function(data,full){
			return full.address_name+' '+full.address_telephone+'<br>'+full.address_province+' '+full.address_city+' '+full.address_county+'<br>'+full.address_address;
		}
	},{
		targets:10,
		render:function(data,full){
			if(full.pay_status == 0)
			{
				return '未支付';
			}
			var device = '';
			if(full.pay_type=='alipay')
			{
				pay_type = '<font color="#3399FF">支付宝</font>';
			}
			else if(full.pay_type == 'wechat')
			{
				pay_type = '<font color="#339900">微信</font>';
				if(full.device == 'android')
				{
					device = 'Android(安卓)';
				}
				if(full.device == 'ios')
				{
					device = 'IOS(苹果)';
				}
				else
				{
					device = '微信公众号';
				}
			}

			return '支付金额:'+full.pay_money+'<br>'+'支付方式:'+pay_type+'<br>'+device;
		}
	},{
		targets:15,
		render:function(data){
			return data==null?'普通订单':'团购订单';
		}
	},{
		targets:16,
		render:function(data,full){

			content = '';
			if(full.status==0)
			{
				content += '<font color="#868686">订单关闭</font>';
				if(full.pay_status==2)
				{
					content += '<br><font color="#FF3300">已退款('+full.refund_money+')</font>';
				}
				return content;
			}
			else
			{
				if(full.pay_status==0)
				{
					return '<font color="#0099FF">待付款</font>';
				}

				if(full.orderType!=null)
				{
					if(full.pay_status==1)
					{
						content += '<font color="#009900">已支付</font>';
						if(full.task_user_status==0)
						{
							content += ',<font color="#6B6B6B">未成团</font>';
							return content;
						}
						else if(full.task_user_status==2)
						{
							return warning('团购订单退款失败');
						}
						else if(full.task_user_status==1)
						{
							if(full.way_status==0)
							{
								return '<font color="#FF6600">待发货，已成团</font>';
							}
							else if(full.way_status==1)
							{
								if(full.receive==0)
								{
									return '<font color="#9900FF">待收货</font>';
								}
								else
								{
									return '<font color="#FF00FF">已完成</font>';
								}
							}
							else if(full.way_status==2)
							{
								return warning('团购订单不应该出现部分发货的情况');
							}
						}
					}
					if(full.pay_status==3)
					{
						return warning('支付宝正在退款');
					}
					if(full.pay_status==2)
					{
						return warning('团购订单出现已退款，但是订单状态却依然有效的状态');
					}
					if(full.pay_status==4)
					{
						return warning('团购订单不会出现部分退款');
					}
					return warning();
				}
				else
				{
					if(full.pay_status==2)
					{
						return warning('已经退款但是没有取消订单?');
					}
					else if(full.pay_status==3)
					{
						return warning('订单正在处理退款');
					}
					else if((full.pay_status==1 || full.pay_status==4))
					{
						if(full.way_status==0)
						{
							content += '<font color="#FF6600">待发货</font>';
							if(full.pay_status==4)
							{
								content += '<br><font color="#FF6600">【部分退款】</font>';
							}
							if(full.need_erp == 1)
							{
								if(full.erp==1)
								{
									content += '<br><font color="#000000">（已推送）</font>';
								}
								else if(full.erp==0)
								{
									content += '<br><font color="#868686">（未推送）</font>';
								}
								else if(full.erp==2)
								{
									content += '<br><font color="#868686">（部分推送）</font>';
								}
							}
							return content;
						}
						else if(full.way_status==1)
						{
							if(full.receive==0)
							{
								return '<font color="#9900FF">待收货</font>';
							}
							else
							{
								return '<font color="#FF00FF">已完成</font>';
							}
						}
						else if(full.way_status==2)
						{
							return '<font color="#FF6600">部分发货</font>';
						}
					}
					return warning();
				}
			}
		}
	},{
		targets:22,
		render:function(data,full){
			showRefundBtn = false;
			showErpBtn = false;
			showWayBtn = false;
			showLogisticsBtn = false;
			
			content = '<button class="btn btn-outline btn-xs orderdetail" data-id="'+data+'">查看</button>';
			if(full.status==1)
			{
				if((full.pay_status == 1 || full.pay_status == 4) && full.receive==0)
				{
					showRefundBtn = true;
				}
				if(full.need_erp==1 && (full.pay_status == 1 || full.pay_status == 4) && full.erp_not_sending_num>0 && full.way_status==0)
				{
					showErpBtn = true;
				}
				
				if(full.need_erp==0)
				{
					//不要发ERP的只要没发货并且付款了的直接可以发货
					if((full.pay_status == 1 || full.pay_status == 4) && (full.way_status==0 || full.way_status==2))
					{
						showWayBtn = true;
					}
				}
				else
				{
					//需要发erp的，必须erp发送了之后才可以发货
					if(full.erp_sending_num>0 && (full.pay_status == 1 || full.pay_status == 4) && (full.way_status==0 || full.way_status==2))
					{
						showWayBtn = true;
					}
				}
				
				if((full.way_status==1 || full.way_status==2))
				{
					showLogisticsBtn = true;
				}
			}
			content += '<div class="space '+(showRefundBtn?'':'display-none')+'"></div><button class="btn btn-outline btn-xs '+(showRefundBtn?'':'display-none')+' refundBtn" data-id="'+data+'">全额退款</button>';
			content += '<div class="space '+(showErpBtn?'':'display-none')+'"></div><button class="btn btn-outline btn-xs '+(showErpBtn?'':'display-none')+' erpBtn" data-id="'+data+'">推送ERP</button>';
			content += '<div class="space '+(showWayBtn?'':'display-none')+'"></div><button class="btn btn-outline btn-xs '+(showWayBtn?'':'display-none')+' sendBtn" data-id="'+data+'">一键发货</button>';
			content += '<div class="space '+(showLogisticsBtn?'':'display-none')+'"></div><button class="btn btn-outline btn-xs '+(showLogisticsBtn?'':'display-none')+' logisticsBtn" data-id="'+data+'">查看物流</button>';
			return content;
		}
	}],
	pagesize:10,
	onRowLoaded:function(row){
		//row.find('td:last').css('text-align','center');
		row.find('td:last').prev().css('text-align','center');
		row.find('td:first').css({'padding-left':'20px','padding-right':'20px'});

	}
});

$('#paying_search').on('submit',function(){
	paying.addAjaxParameter('status',$(this).find('select').val());
	paying.search($(this).find('input').val());
	return false;
});

//批量操作
$('#paying .multipleBtn').on('click',function(){
	var table = $(this).parents('table');
	var id = getSelectedCheckbox(table);
	if($(this).parents('td').find('.multipleSelect').val() == '')
	{
		return false;
	}
	if(id.length == 0)
	{
		bootbox.alert('请选择商品');
		return false;
	}
	if($(this).parents('td').find('.multipleSelect').val() == 'delete')
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
					paying.addAjaxParameter('customActionType','group_action');
					paying.addAjaxParameter('id',id);
					paying.addAjaxParameter('customActionName','remove');
					paying.reload();
				}
			},
		});
	}
	return false;
});