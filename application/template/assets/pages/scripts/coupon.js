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
				columns:[{
					data:'id',
					name:'coupon.id',
					orderable:false
				},{
					data:'name',
					name:'coupon.name',
					orderable:true,
				},{
					data:'createtime',
					name:'coupon.createtime',
					orderable:true,
				},{
					data:'username',
					name:'user.name',
					orderable:true,
				},{
					data:'max',
					name:'max',
					orderable:true,
				},{
					data:'value',
					name:'value',
					orderable:true,
				},{
					data:'endtime',
					name:'coupon.endtime',
					orderable:true,
				},{
					data:'product_name',
					name:'(select name from product where product.id=coupon.product_id)',
					orderable:false,
				},{
					data:'source',
					name:'coupon.source',
					orderable:true,
				},{
					data:'used',
					name:'coupon.used',
					orderable:true,
				},{
					data:'isdelete',
					name:'coupon.isdelete',
					orderable:true,
				},{
					data:'id',
					name:'coupon.id',
					orderable:false,
				},{
					data:'couponno',
					name:'coupon.couponno',
					visible:false,
				},{
					data:'usedtime',
					name:'usedtime',
					visible:false,
				}],
				columnDefs:[{
					targets:0,
					render:function(data,type,full){
						return '<input type="checkbox" class="checkboxes" name="id[]" value='+data+'>';
					}
				},{
					targets:2,
					render:function(data,type,full){
						return unixtotime(data,true,8);
					}
				},{
					targets:6,
					render:function(data,type,full){
						if(data==0)
						return '不限';
						return unixtotime(data,true,8);
					}
				},{
					targets:8,
					render:function(data,type,full){
						return data==0?'管理员创建':('优惠券兑换<br>'+full.couponno);
					}
				},{
					targets:9,
					render:function(data,type,full){
						return (data==0)?'未使用':('已使用<br>'+unixtotime(full.usedtime,true,8));
					}
				},{
					targets:10,
					render:function(data,type,full){
						return data=='1'?'已删':'未删';
					}
				},{
					targets:11,
					render:function(data,type,full){
						if(full.isdelete==0)
						{
							return '<button data-id="'+data+'" class="btn red btn-xs btn-outline btn-transparents remove">删除</button>';
						}
						return '';
					}
				}],
                "lengthMenu": [
                    [10, 20, 50, 100, 150, -1],
                    [10, 20, 50, 100, 150, "All"] // change per page values here
                ],
                "pageLength": 10, // default record count per page
                "ajax": {
                    "url": "index.php?c=datatables&a=coupon", // ajax source
                },
                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

        // handle group actionsubmit button click
        grid.getTableWrapper().on('click', '.table-group-action-submit', function (e) {
            e.preventDefault();
            var action = $(".table-group-action-input", grid.getTableWrapper());
            if (action.val() != "" && grid.getSelectedRowsCount() > 0) {
                grid.setAjaxParam("customActionType", "group_action");
                grid.setAjaxParam("customActionName", action.val());
                grid.setAjaxParam("id", grid.getSelectedRows());
                grid.getDataTable().ajax.reload();
                grid.clearAjaxParams();
            } else if (action.val() == "") {
                App.alert({
                    type: 'danger',
                    icon: 'warning',
                    message: '请选择一个操作',
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