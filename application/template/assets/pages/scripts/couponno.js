var EcommerceOrders = function () {

    var initPickers = function () {
        //init date pickers
        $('.date-picker').datepicker({
            rtl: App.isRTL(),
            autoclose: true
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
					name:'couponno.id',
					orderable:false
				},{
					data:'couponno',
					name:'couponno.couponno',
					orderable:true,
				},{
					data:'couponno_starttime',
					name:'couponno.couponno_starttime',
					orderable:false,
				},{
					data:'total',
					name:'couponno.total',
					orderable:true,
				},{
					data:'times',
					name:'couponno.times',
					orderable:true,
				},{
					data:'limittimes',
					name:'couponno.limittimes',
					orderable:true,
				},{
					data:'coupon_name',
					name:'coupon_name',
					orderable:true,
				},{
					data:'coupon_max',
					name:'coupon_max',
					orderable:true,
				},{
					data:'coupon_value',
					name:'coupon_value',
					orderable:true,
				},{
					data:'coupon_time',
					name:'coupon_time',
					orderable:true,
				},{
					data:'product_name',
					name:'(select name from product where product.id=couponno.product_id)',
					orderable:false,
				},{
					data:'id',
					name:'couponno.id',
					orderable:false,
				},{
					data:'isdelete',
					name:'isdelete',
					visible:false,
				},{
					data:'createtime',
					name:'createtime',
					visible:false,
				},{
					data:'couponno_endtime',
					name:'couponno.couponno_endtime',
					visible:false,
				},{
					data:'coupon_time_type',
					name:'couponno.coupon_time_type',
					visible:false,
				},{
					data:'coupon_endtime',
					name:'couponno.coupon_endtime',
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
						if(data != 0)
						{
							return unixtotime(full.couponno_starttime,true,8)+'~'+unixtotime(full.couponno_endtime,true,8);
						}
						return '不限制';
					}
				},{
					targets:3,
					render:function(data,type,full){
						if(data==0)
						return '不限';
						return data;
					}
				},{
					targets:4,
					render:function(data,type,full){
						if(full.total==0)
						return '不限';
						return data;
					}
				},{
					targets:9,
					render:function(data,type,full){
						if(full.coupon_time_type == 0)
						{
							if(data == 0)
							{
								return '不限';
							}
							else
							{						
								return data+'天';
							}
						}
						else
						{
							return '截至'+unixtotime(full.coupon_endtime,true,8);
						}
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
                    "url": "index.php?c=datatables&a=couponno", // ajax source
                },
                "order": [
                    [10, "desc"]
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
		return grid;
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