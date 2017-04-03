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
            dataTable: {
            	createdRow: function( row, data, dataIndex )
				{
					if(data.close==1)
					{
						$(row).addClass('danger');
					}
				},
            	// here you can define a typical datatable settings from http://datatables.net/usage/options 
                // Uncomment below line("dom" parameter) to fix the dropdown overflow issue in the datatable cells. The default datatable layout
                // setup uses scrollable div(table-scrollable) with overflow:auto to enable vertical scroll(see: assets/global/scripts/datatable.js). 
                // So when dropdowns used the scrollable div should be removed. 
                //"dom": "<'row'<'col-md-8 col-sm-12'pli><'col-md-4 col-sm-12'<'table-group-actions pull-right'>>r>t<'row'<'col-md-8 col-sm-12'pli><'col-md-4 col-sm-12'>>",
				columns:[{
					data:'id',
					name:'user.id',
					orderable:false
				},{
					data:'gravatar',
					name:'upload.path',
					orderable:false
				},{
					data:'name',
					name:'user.name',
					orderable:true
				},{
					data:'telephone',
					name:'user.telephone',
					orderable:true
				},{
					data:'invit',
					name:'user.invit',
					orderable:true
				},{
					data:'regtime',
					name:'user.regtime',
					orderable:true
				},{
					data:'money',
					name:'user.money',
					orderable:true
				},{
					data:'vip',
					name:'user.vip',
					orderable:true
				},{
					data:'master',
					name:'user.master',
					orderable:true,
				},{
					data:'o_master',
					name:'(select master.name from user as master where master.id=user.o_master limit 1)',
					orderable:true,
				},{
					data:'oid',
					name:'(select o2o.name from user as o2o where o2o.id=user.oid limit 1)',
					orderable:true,
				},{
					data:'description',
					name:'user.description',
					orderable:true,
				},{
					data:'wechat_no',
					name:'user.wechat_no',
					orderable:true,
				},{
					data:'source',
					name:'(select name from source where source.id=user.source limit 1)',
					orderable:true,
				},{
					data:'id',
					name:'user.id',
					orderable:true,
				},
				//一下是隐藏内容
				{
					data:'close',
					name:'user.close',
					visible:false,
				}],
				columnDefs:[{
					targets:0,
					render:function(data,type,full){
						return '<input type="checkbox" class="checkboxes" name="id[]" value='+data+'>';
					}
				},{
					targets:1,
					render:function(data,type,full){
						if(data!=null)
						{
							return '<img src='+data+' width="50" style="width:50px;" height="50" class="img-responsive img-rounded">';
						}
						return '<img src=http://placehold.it/50x50 width="50" style="width:50px;" height="50" class="img-responsive img-rounded">';
					}
				},{
					targets:5,
					render:function(data,type,full){
						return unixtotime(data,true,8);
					}
				},{
					targets:6,
					render:function(data,type,full){
						return '<a class="showSwiftBtn" data-id="'+full.id+'">'+data+'</a>';
					}
				},{
					targets:7,
					render:function(data,type,full){
						switch(data)
						{
							case '0':return '普通会员';
							case '1':return '白金会员';
							case '2':return '钻石会员';
						}
						return '';
					}
				},{
					targets:8,
					render:function(data,type,full){
						return (data==0)?'否':'是';
					}
				},{
					targets:11,
					render:function(data,type,full){
						if(data.length>5)
						{
							return data.substring(0,5)+'...';
						}
						return data;
					}
				},{
					targets:14,
					render:function(data,type,full){
						buttons = '<button data-id="'+data+'" class="btn btn-xs btn-outline yellow password">改密</button>'
						+'<button data-id="'+data+'" class="btn btn-xs btn-outline yellow money">改钱</button>'
						+'<button data-id="'+data+'" class="btn btn-xs wechat purple btn-outline">微信号</button>';
						if (full.close==0)
						{
							buttons+='<button data-id="'+data+'" class="btn btn-xs closeUser red btn-outline">封停</button>';
						}
						else
						{
							buttons+='<button data-id="'+data+'" class="btn btn-xs closeUser red btn-outline">解封</button>';
						}
						return buttons;
					}
				}],
                "lengthMenu": [
                    [10, 20, 50, 100, 150, -1],
                    [10, 20, 50, 100, 150, "All"] // change per page values here
                ],
                /*buttons: [
	                { extend: 'print', className: 'btn dark btn-outline' },
	                { extend: 'copy', className: 'btn red btn-outline' },
	                { extend: 'pdf', className: 'btn green btn-outline' },
	                { extend: 'excel', className: 'btn yellow btn-outline ' },
	                { extend: 'csv', className: 'btn purple btn-outline ' },
	                {
	                    text: 'Reload',
	                    className: 'btn default',
	                    action: function ( e, dt, node, config ) {
	                        //dt.ajax.reload();
	                        alert('Custom Button');
	                    }
	                }
	            ],*/
                "pageLength": 10, // default record count per page
                "ajax": {
                    "url": "index.php?c=datatables&a=user", // ajax source
                },
                //responsive: true,
                "order": [
                    [5, "asc"]
                ], // set first column as a default sort by asc
				//initComplete:initComplete,
				//dom: "<'row' <'col-md-12'B>><'row'<'col-md-6 col-sm-12'l><'col-md-6 col-sm-12'f>r><'table-scrollable't><'row'<'col-md-5 col-sm-12'i><'col-md-7 col-sm-12'p>>",
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
					form.action = './index.php?c=export&a=user';
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