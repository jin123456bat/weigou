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
					$(row).find('td:eq(6)').addClass('drawal-pass');
				},
				columns:[{
					data:'id',
					name:'drawal.id',
					orderable:false,
				},{
					data:'id',
					name:'drawal.id',
				},{
					data:'createtime',
					name:'drawal.createtime',
				},{
					data:'name',
					name:'user.name',
				},{
					data:'money',
					name:'drawal.money',
				},{
					data:'type',
					name:'bankcard.type',
				},{
					data:'pass',
					name:'drawal.pass'
				},{
					data:'id',
					name:'drawal.id'
				}
				//隐藏数据
				,{
					data:'bankcard_account',
					name:'bankcard.account',
					visible:false,
				},{
					data:'passtime',
					name:'drawal.passtime',
					visible:false,
				},{
					data:'account',
					name:'bankcard.account',
					visible:false,
				},{
					data:'account_name',
					name:'bankcard.name',
					visible:false,
				},{
					data:'subbank',
					name:'bankcard.subbank',
					visible:false,
				},{
					data:'account_province',
					name:'province.name',
					visible:false,
				},{
					data:'account_city',
					name:'city.name',
					visible:false,
				},{
					data:'bank',
					name:'bankcard.bank',
					visible:false,
				},{
					data:'telephone',
					name:'user.telephone',
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
					targets:3,
					render:function(data,type,full){
						return data+'('+full.telephone+')';
					}
				},{
					targets:5,
					render:function(data,type,full){
						if(data=='alipay')
						{
							return '支付宝<br>'+full.account+'<br>'+full.account_name;
						}
						else if (data='bank')
						{
							return '银行卡<br>'+full.account+'<br>'+full.bank+'<br>'+full.subbank+'<br>'+full.account_name+'<br>'+full.account_province+'<br>'+full.account_city;
						}
						return '未知账户';
					}
				},{
					targets:6,
					render:function(data,type,full){
						return data=='0'?'未通过':'已通过<br>'+unixtotime(full.passtime,true,8);
					}
				},{
					targets:7,
					render:function(data,type,full){
						if(full.pass=='0')
						{
							return '<button class="btn btn-xs btn-outline green pass" data-id="'+data+'">手动通过</button>' + '<button data-id="'+data+'" class="btn btn-xs btn-outline yellow edit">修改</button>';
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
                    "url": "index.php?c=datatables&a=drawal", // ajax source
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
			var money = 0;
			for(var i=0;i<content.length;i++)
			{
				money += parseFloat(content[i].money);
			}
			$('#money').html(money.toFixed(2));
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
				if(action.val() == 'export')
				{
					var form = document.createElement("form");
					form.action = './index.php?c=export&a=drawal';
					form.target = '_blank';
					form.method = 'post';
					
					var ids = grid.getSelectedRows();
					for(var i=0;i<ids.length;i++)
					{
						var input = document.createElement('input');
						input.type = 'hidden';
						input.name = 'id[]';
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