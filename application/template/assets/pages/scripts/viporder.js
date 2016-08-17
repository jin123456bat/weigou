var EcommerceOrdersView = function () {

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

    var handleShipment = function () {

        var grid = new Datatable();
        grid.init({
            src: $("#datatable_shipment"),
            onSuccess: function (grid) {
                // execute some code after table records loaded
            },
            onError: function (grid) {
                // execute some code on network or other general error  
            },
            loadingMessage: '载入中...',
            dataTable: { // here you can define a typical datatable settings from http://datatables.net/usage/options 
                "lengthMenu": [
                    [10, 20, 50, 100, 150, -1],
                    [10, 20, 50, 100, 150, "All"] // change per page values here
                ],
				createdRow: function ( row, data, index ) {
					$(row).find('.popovers').popover();
				},
                "pageLength": 10, // default record count per page
                "ajax": {
                    "url": "index.php?c=datatables&a=viporder", // ajax source
                },
				columns:[{
					data:'id',
					name:'vip_order.id',
					orderable:false,
				},{
					data:'orderno',
					name:'vip_order.orderno',
					orderable:true,
				},{
					data:'name',
					name:'user.name',
					orderable:true,
				},{
					data:'createtime',
					name:'vip_order.createtime',
					orderable:true,
				},{
					data:'vip_to',
					name:'vip_order.vip_to',
					orderable:true,
				},{
					data:'pay_status',
					name:'vip_order.paytime',
					orderable:true,
				},{
					data:'vip_from',
					name:'vip_order.vip_from',
					orderable:true,
				},{
					data:'id',
					name:'vip_order.id',
				},
				
				//以下是隐藏字段
				{
					data:'vip_to',
					name:'vip_order.vip_to',
					visible:false,
				},{
					data:'payprice',
					name:'vip_order.payprice',
					visible:false,
				},{
					data:'paytype',
					name:'vip_order.paytype',
					visible:false,
				},{
					data:'paynumber',
					name:'vip_order.paynumber',
					visible:false,
				},{
					data:'paytime',
					name:'vip_order.paytime',
					visible:false,
				}],
                "columnDefs": [{
					targets:0,
					render:function(data,type,full){
						return '<input type="checkbox" class="checkboxes" name="id[]" value='+data+'>';
					}
				},{
					targets:3,
					render:function(data,type,full){
						return unixtotime(data,true,8);
					}
				},{
					targets:4,
					render:function(data,type,full){
						if(full.vip_from==0)
						{
							if(full.vip_to==1)
							{
								return 200;
							}
							if(full.vip_to==2)
							{
								return 800;
							}
						}
						return 600;
					}
				},{
					targets:5,
					render:function(data,type,full){
						if(data != 0)
						{
							var paytype = full.paytype == 'alipay'?'支付宝':'微信';
							return '<font style="cursor:pointer;" class="popovers" data-container="body" data-html="true" data-trigger="click hover" data-placement="right" data-content="金额:'+full.payprice+'<br>时间:'+unixtotime(full.paytime,true,8)+'<br>单号:'+full.paynumber+'" data-original-title="'+paytype+'">已支付</font>';
						}
						else
						{
							return '未支付';
						}
					}
				},{
					targets:6,
					render:function(data,type,full){
						return '从V'+full.vip_from+'到V'+full.vip_to;
					}
				},{
					targets:7,
					render:function(data,type,full){
						return '';
					}
				}],
                "order": [
                    [3, "desc"]
                ] // set first column as a default sort by asc
            }
        });
		
		grid.getTableWrapper().on('click', '.table-group-action-submit', function (e) {
            e.preventDefault();
            var action = $(".table-group-action-input", grid.getTableWrapper());
            if (action.val() != "" && grid.getSelectedRowsCount() > 0) {
				if(action.val() == 'export')
				{
					var form = document.createElement("form");
					form.action = './index.php?c=export&a=vip';
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
	
	var initSelect2 = function(){
		$.fn.select2.defaults.set("theme", "bootstrap");
		
		function formatRepo(user) {
			if(!user.loading)
			{
				var markup = "<table class='movie-result'><tr>";
				markup += "<td valign='center'><img src='" + (user.gravatar==null?'http://placehold.it/50x50':user.gravatar) + "' width='50' heigth='50' /></td>";
				markup += "<td align='center' valign='center'><h5> " + user.name + "</h5>";
				markup += "<div class='movie-synopsis'> " + user.telephone + "</div>";
				markup += "</td></tr></table>"
				return markup;
			}
		}

		function formatRepoSelection(repo) {
			return repo.name;
		}

		$(".select2").select2({
			placeholder: '请选择用户',
			allowClear: true,
			language: {
			   noResults: function(){
				   return "没有找到任何匹配项";
			   },
			   inputTooShort:function(){
				   return '请输入用户名称';
				},
				searching:function(){
					return '正在努力搜索...';
				},
				loadingMore:function(){
					return '载入更多...';
				},
				inputTooLong:function(){
					return '输入太多了...';
				}
			},
			ajax: {
				url: "./index.php?m=ajax&c=user&a=search",
				dataType: 'json',
				delay: 250,
				data: function(params) {
					return {
						keywords: params.term, // search term
						length: params.page
					};
				},
				processResults: function(data, page) {
					return {
						results: data.body.data
					};
				},
				cache: true
			},
			escapeMarkup: function(markup) {
				return markup;
			},
			minimumInputLength: 1,
			templateResult: formatRepo,
			templateSelection: formatRepoSelection
		});
	}

    return {

        //main function to initiate the module
        init: function () {
            initPickers();
		    handleShipment();
			initSelect2();
        }

    };

}();

jQuery(document).ready(function() {    
   EcommerceOrdersView.init();
});
