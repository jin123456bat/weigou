var EcommerceProducts = function () {

    var initPickers = function () {
        //init date pickers
        $('.date-picker').datepicker({
            rtl: App.isRTL(),
            autoclose: true
        });
    }
	
	var timestamp = Date.parse( new Date())/1000;
	
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


    var handleProducts = function() {
        var grid = new Datatable();

        grid.init({
            src: $("#datatable_products"),
            onSuccess: function (grid) {
                // execute some code after table records loaded
            },
            onError: function (grid) {
                // execute some code on network or other general error  
            },
            loadingMessage: '载入中...',
            dataTable: {
				columns:[{
					data:'id',
					name:'product.id',
					orderable:false
				},{
					data:'sku',
					name:'product.sku',
					orderable:true,
				},{
					data:'name',
					name:'product.name',
					orderable:true,
				},{
					data:'outside',
					name:'product.outside',
					orderable:true
				},{
					data:'category',
					orderable:true,
				},{
					data:'price',
					name:'product.price',
					orderable:true,
				},{
					data:'store',
					name:'store.name',
					orderable:true,
				},{
					data:'modifytime',
					name:'product.modifytime',
					orderable:true,
				},{
					data:'stock',
					name:'stock',
					orderable:true,
				},{
					data:'status',
					name:'status',
					orderable:true,
				},{
					data:'sort',
					name:'product.sort',
					orderable:true,
				},{
					data:'id',
					name:'product.id',
					orderable:true,
				},{
					data:'auto_stock',
					name:'product.auto_stock',
					visible:false,
				},{
					data:'auto_status',
					name:'product.auto_status',
					visible:false,
				},{
					data:'avaliabletime_from',
					name:'product.avaliabletime_from',
					visible:false,
				},{
					data:'avaliabletime_to',
					name:'product.avaliabletime_to',
					visible:false,
				},{
					data:'v1price',
					name:'product.v1price',
					visible:false,
				},{
					data:'v2price',
					name:'product.v2price',
					visible:false,
				}],
				columnDefs:[{
					targets:0,
					render:function(data,type,full){
						return '<input type="checkbox" class="checkboxes" name="id[]" value='+data+'>';
					}
				},{
					targets:3,
					render:function(data,type,full){
						switch(data)
						{
							case '0':return '普通商品';
							case '1':return '进口商品';
							case '2':return '直供商品';
							case '3':return '直邮商品';
							default:return '未知商品类型';
						}
					}
				},{
					targets:5,
					render:function(data,type,full){
						return 'v0价:'+data+'<br>v1价:'+full.v1price+'<br>v2价:'+full.v2price;
					}
				},{
					targets:7,
					render:function(data,type,full){
						return unixtotime(data,true,8);
					}
				},{
					targets:8,
					render:function(data,type,full){
						if(full.auto_stock==0)
							return '不限制';
						return data;
					}
				},{
					targets:9,
					render:function(data,type,full){
						if(full.auto_status==1)
						{
							if(timestamp>=full.avaliabletime_from && timestamp<=full.avaliabletime_to)
							{
								return '自动托管(上架)';
							}
							else
							{
								return '自动托管(下架)';
							}
						}
						switch(data)
						{
							case '1':return '上架';
							case '0':return '下架';
						}
					}
				},{
					targets:11,
					render:function(data,type,full){
						return '<a target="_blank" class="btn btn-xs green-stripe default" href="index.php?c=admin&a=edit_product&id='+data+'">编辑</a>'
					}
				}],
                "lengthMenu": [
                    [10, 20, 50, 100, 150,-1],
                    [10, 20, 50, 100, 150,'全部'] // change per page values here 
                ],
                "pageLength": 10, // default record count per page
                "ajax": {
                    "url": "index.php?c=datatables&a=product", // ajax source
                },
                "order": [
                    [1, "asc"]
                ],
				stateSave:true,
            }
        });

         // handle group actionsubmit button click
        grid.getTableWrapper().on('click', '.table-group-action-submit', function (e) {
            e.preventDefault();
            var action = $(".table-group-action-input", grid.getTableWrapper());
            if (action.val() != "" && grid.getSelectedRowsCount() > 0) {
				if(action.val() == 'export')
				{
					var form = document.createElement("form");
					form.action = './index.php?c=export&a=product';
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

            handleProducts();
            initPickers();
            
        }

    };

}();

jQuery(document).ready(function() {    
   EcommerceProducts.init();
});