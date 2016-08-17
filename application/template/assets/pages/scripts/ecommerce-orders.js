var EcommerceOrders = function () {

    var initPickers = function () {
        //init date pickers
        $('.date-picker1').datepicker({
            rtl: App.isRTL(),
			forceParse:true,
			language: 'zh-CN',
            autoclose: true,
        }).on('changeDate',function(event){
			$('.date-picker2').datepicker('setStartDate', $('.date-picker1').find('input').val());
		});
		
		$('.date-picker2').datepicker({
            rtl: App.isRTL(),
			forceParse:true,
			language: 'zh-CN',
            autoclose: true,
        }).on('changeDate',function(event){
			$('.date-picker1').datepicker('setEndDate', $('.date-picker2').find('input').val());
		});
    }
	
	
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
			ymdhis += " " + time.getUTCHours() + ":";
			ymdhis += time.getUTCMinutes() + ":";
			ymdhis += time.getUTCSeconds();
		}
		return ymdhis;
	}


    var handleOrders = function () {

        var grid = new Datatable();

        grid.init({
            src: $("#datatable_orders"),
            onSuccess: function (grid) {
                // execute some code after table records loaded
            },
            onError: function (grid) {
                // execute some code on network or other general error  
            },
            loadingMessage: '载入中...',
            dataTable: { // here you can define a typical datatable settings from http://datatables.net/usage/options 
                // Uncomment below line("dom" parameter) to fix the dropdown overflow issue in the datatable cells. The default datatable layout
                // setup uses scrollable div(table-scrollable) with overflow:auto to enable vertical scroll(see: assets/global/scripts/datatable.js). 
                // So when dropdowns used the scrollable div should be removed. 
                //"dom": "<'row'<'col-md-8 col-sm-12'pli><'col-md-4 col-sm-12'<'table-group-actions pull-right'>>r>t<'row'<'col-md-8 col-sm-12'pli><'col-md-4 col-sm-12'>>",
				createdRow: function ( row, data, index ) {
					$(row).find('.popovers').popover();
					$(row).find('td:eq(8)').addClass('order-status');
				},
				columns:[{
					data:'orderno',
					name:'order.orderno',
					orderable:false,
				},{
					data:'orderno',
					name:'order.orderno',
					orderable:true,
				},{
					data:'createtime',
					name:'order.createtime',
					orderable:true,
				},{
					data:'name',
					name:'user.name',
					orderable:true,
				},{
					data:'orderamount',
					name:'order.orderamount',
					orderable:true,
				},{
					data:'way_status',
					name:'order.way_status',
					orderable:true,
				},{
					data:'pay_status',
					name:'order.pay_status',
					orderable:true,
				},{
					data:'kouan',
					name:'order.kouan',
					orderable:true,
				},{
					data:'status',
					name:'order.status',
					orderable:true,
				},{
					data:'task',
					name:'task_user.orderno',
					orderable:true,
				},{
					data:'note',
					name:'order.note',
					orderable:false,
				},{
					data:'orderno',
					name:'order.orderno',
					orderable:false,
				}
				
				//这里一下都是附加数据
				,{
					data:'quittime',
					name:'order.quittime',
					visible:false,
				},{
					data:'erp',
					name:'order.erp',
					visible:false,
				},{
					data:'payed',
					name:'order.payed',
					visible:false,
				},{
					data:'pay_type',
					name:'order.pay_type',
					visible:false,
				},{
					data:'pay_money',
					name:'order.pay_money',
					visible:false,
				},{
					data:'pay_number',
					name:'order.pay_number',
					visible:false,
				},{
					data:'pay_time',
					name:'order.pay_time',
					visible:false,
				},{
					data:'province',
					name:'province.name',
					visible:false,
				},{
					data:'county',
					name:'county.name',
					visible:false,
				},{
					data:'city',
					name:'city.name',
					visible:false,
				},{
					data:'zcode',
					name:'address.zcode',
					visible:false,
				},{
					data:'address_name',
					name:'address.name',
					visible:false,
				},{
					data:'address_telephone',
					name:'address.telephone',
					visible:false,
				},{
					data:'feeamount',
					name:'order.feeamount',
					visible:false,
				},{
					data:'goodsamount',
					name:'order.goodsamount',
					visible:false,
				},{
					data:'taxamount',
					name:'order.taxamount',
					visible:false,
				},{
					data:'discount',
					name:'order.discount',
					visible:false,
				},{
					data:'money',
					name:'order.money',
					visible:false,
				},{
					data:'task_status',
					name:'task_user.status',
					visible:false,
				},{
					data:'refundtime',
					name:'order.refundtime',
					visible:false,
				},{
					data:'receive',
					name:'order.receive',
					visible:false,
				},{
					data:'need_kouan',
					name:'order.need_kouan',
					visible:false,
				},{
					data:'address',
					name:'address.address',
					visible:false
				},{
					data:'suborder_orderno',
					name:'( select GROUP_CONCAT(concat(replace(suborder_store.date,"-",""),suborder_store.id)) from suborder_store where suborder_store.main_orderno=order.orderno )',
					visible:false,
				},{
					data:'way_type',
					name:'order.way_type',
					visible:false,
				}],
				columnDefs:[{
					targets:0,
					render:function(data,type,full){
						return '<input type="checkbox" class="checkboxes" name="id[]" value='+data+'>';
					}
				},{
					targets:1,
					render:function(data,type,full) {
						if (full.suborder_orderno == null)
						{
							return data+'<br>[未拆单]';
						}
						else
						{
							return data+'<br>['+full.suborder_orderno+']';		
						}
					}
				},{
					targets:2,
					render:function(data,type,full){
						return unixtotime(data,true,8);
					}
				},{
					targets:4,
					render:function(data,type,full){
						return '<font style="cursor:pointer;" class="popovers" data-container="body" data-html="true" data-trigger="click hover" data-placement="right" data-content="商品:'+full.goodsamount+'<br>税款:'+full.taxamount+'<br>运费:'+full.feeamount+'<br>优惠:'+full.discount+'<br>余额:'+full.money+'" data-original-title="费用详情">'+data+'</font>';
					}
				},{
					targets:5,
					render:function(data,type,full){
						var text = (data==1?'已发货':'未发货');
						switch(full.way_type)
						{
							case '0':var way_type = '未知方式';break;
							case '1':var way_type = '单独发货';break;
							case '2':var way_type = '批量导入';break;
							case '3':var way_type = 'ERP通知';break;
							//default:var way_type = '未知方式';
						}
						if (data == 1) {
							text += ('<br>'+ way_type);
						};
						return '<font style="cursor:pointer;" class="popovers" data-container="body" data-html="true" data-trigger="click hover" data-placement="right" data-content="省份:'+full.province+'<br>城市:'+full.city+'<br>地区:'+full.county+'<br>地址:'+full.address+'<br>邮编:'+full.zcode+'<br>姓名:'+full.address_name+'<br>电话:'+full.address_telephone+'" data-original-title="收货地址">'+text+'</font>';
					}
				},{
					targets:6,
					render:function(data,type,full){
						switch(data)
						{
							case '0':return '未支付';
							case '2':return '<font style="cursor:pointer;" class="popovers" data-container="body" data-html="true" data-trigger="click hover" data-placement="right" data-original-title="'+(full.pay_type=='wechat'?'微信':'支付宝')+'" data-content="退款时间:'+unixtotime(full.refundtime,true,8)+'">已全额退款</font>';
							case '3':return '正在退款';
							case '4':return '部分退款';
							case '1':
							if(full.pay_type=='money')
							{
								return '<font style="cursor:pointer;" class="popovers" data-container="body" data-html="true" data-trigger="click hover" data-placement="right" data-original-title="余额支付" data-content="扣除余额:'+full.money+'">已支付('+full.pay_money+')</font>';
							}
							else
							{
								return '<font style="cursor:pointer;" class="popovers" data-container="body" data-html="true" data-trigger="click hover" data-placement="right" data-content="'+('金额:'+full.pay_money+'<br>时间:'+unixtotime(full.pay_time,true,8))+'<br>单号:'+full.pay_number+'<br>余额:'+full.money+'" data-original-title="'+(full.pay_type=='alipay'?'支付宝':(full.pay_type=='wechat'?'微信':'余额'))+'">已支付('+full.pay_money+')</font>';
							}
						}
						return '';
					}
				},{
					targets:7,
					render:function(data,type,full){
						if(full.need_kouan == 0)
							return '无需报关';
						switch(data)
						{
							case '0':return '未报关';
							case '1':return '报关通过';
							case '2':return '报关异常';
						}
						return '';
					}
				},{
					targets:8,
					render:function(data,type,full){
						switch(data)
						{
							case '0':return '无效<br>'+unixtotime(full.quittime,true,8);
							case '1':return '有效';
						}
						return '';
					}
				},{
					targets:9,
					render:function(data,type,full){
						if(data==null)
						{
							return '否';
						}
						else
						{
							var content = '是';
							if(full.task_status == 1)
							{
								content += '(成功)';
							}
							else if(full.task_status == 0)
							{
								content += '(正在进行)';
							}
							if(full.task_status == 2)
							{
								content += '(失败)';
							}
							return content;
						}
					}
				},{
					targets:11,
					render:function(data,type,full){
						var content = '<button data-orderno="'+data+'" class="btn btn-xs purple btn-outline orderdetail">订单详情</button>';
						content += '<button data-orderno="'+data+'" class="btn btn-xs yellow btn-outline noteBtn">订单备注</button>';
						//取消订单按钮
						if(full.status == '1')
						{
							content += '<button data-orderno="'+data+'" class="btn btn-xs red btn-outline quit">取消订单</button>';
						}
						//推送erp按钮
						/*if(full.status=='1')
						{
							if(full.erp=='0')
							{
								content += '<button data-orderno="'+data+'" class="btn btn-xs yellow btn-outline erp">推送ERP</button>';
							}
							else
							{
								content += '<button data-orderno="'+data+'" class="btn btn-xs yellow btn-outline erp">重推ERP</button>';
							}
						}*/
						//退款按钮
						/*if(full.pay_status==1 || full.pay_status == 4)
						{
							content += '<button data-orderno="'+data+'" class="btn btn-xs black btn-outline refund">全额退款</button>';
						}*/
						//推送支付单按钮
						/*if(full.status == '1')
						{
							if(full.pay_status=='1')
							{
								if(full.payed=='0')
								{
									content += '<button data-orderno="'+data+'" class="btn btn-xs blue btn-outline payed">推送支付</button>';
								}
								else
								{
									content += '<button data-orderno="'+data+'" class="btn btn-xs blue btn-outline payed">重推支付</button>';
								}
							}
						}*/
						//发货按钮
						if(full.status==1)
						{
							if(full.pay_status==1 && full.way_status==0)
							{
								content += '<button data-orderno="'+data+'" class="btn btn-xs green btn-outline send">订单发货</button>';
							}
						}
						return content;
					}
				}],
                "lengthMenu": [
                    [10, 20, 50, 100, 150, -1],
                    [10, 20, 50, 100, 150, "All"] // change per page values here
                ],
                "pageLength": 10, // default record count per page
                "ajax": {
                    "url": "index.php?c=datatables&a=order", // ajax source
                },
                "order": [
                    [2, "desc"]
                ],
				stateSave:true,
				//initComplete:initComplete,
            }
        });
		
		function initComplete(row,data)
		{
			var content = data.data || data;
			var orderamount = 0;
			var pay_money = 0;
			
			for(var i=0;i<content.length;i++)
			{
				orderamount += parseFloat(content[i].orderamount);
				if(content[i].pay_status==1)
				{
					console.log(content[i]);
					pay_money += parseFloat(content[i].pay_money);
				}
			}
			$('#orderamount').html(orderamount.toFixed(2));
			$('#pay_money').html(pay_money.toFixed(2));
		}
		
		grid.getDataTable().on('draw.dt',function(a,b){
			var data = b.aoData;
			var temp = [];
			for(var i=0;i<data.length;i++)
			{
				temp.push(data[i]._aData);
			}
			initComplete(a,temp);
		});

        // handle group actionsubmit button click
        grid.getTableWrapper().on('click', '.table-group-action-submit', function (e) {
            e.preventDefault();
            var action = $(".table-group-action-input", grid.getTableWrapper());
            if (action.val() != "" && grid.getSelectedRowsCount() > 0) {
				if(action.val()=='export')
				{
					var form = document.createElement("form");
					form.action = './index.php?c=export&a=order';
					form.target = '_blank';
					form.method = 'post';
					
					var ids = grid.getSelectedRows();
					for(var i=0;i<ids.length;i++)
					{
						var input = document.createElement('input');
						input.type = 'hidden';
						input.name = 'orderno[]';
						input.value = ids[i];
						form.appendChild(input);
					}
					form.submit();
				}
				else
				{
					grid.setAjaxParam("customActionType", "group_action");
					grid.setAjaxParam("customActionName", action.val());
					grid.setAjaxParam("id", grid.getSelectedRows());
					grid.getDataTable().ajax.reload();
					grid.clearAjaxParams();
				}
            } else if (action.val() == "") {
                App.alert({
                    type: 'danger',
                    icon: 'warning',
                    message: '请选择一个数据',
                    container: grid.getTableWrapper(),
                    place: 'prepend'
                });
            } else if (grid.getSelectedRowsCount() === 0) {
                App.alert({
                    type: 'danger',
                    icon: 'warning',
                    message: '没有选择任何数据',
                    container: grid.getTableWrapper(),
                    place: 'prepend'
                });
            }
        });

    }

    return {

        //main function to initiate the module
        init: function () {

            initPickers();
            handleOrders();
        }

    };

}();

jQuery(document).ready(function() {    
   EcommerceOrders.init();
});