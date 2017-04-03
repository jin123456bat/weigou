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
		grid.setAjaxParam("orderno", $('#orderno').text());
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
                "pageLength": 10, // default record count per page
                "ajax": {
                    "url": "index.php?c=datatables&a=package", // ajax source
                },
				columns:[{
					data:'ship_type',
					name:'ship.name',
					orderable:true,
				},{
					data:'ship_number',
					name:'order_package.ship_number',
					orderable:true,
				},{
					data:'ship_time',
					name:'order_package.ship_time',
					orderable:true,
				},{
					data:'product',
					name:'',
				},{
					data:'id',
					name:'order_package.id',
				}],
                "columnDefs": [{
                    targets:2,
					render:function(data,type,full){
						if(data != 0)
						{
							return unixtotime(data,true,8);
						}
						else
						{
							return '未配送';
						}
					},
                },{
					targets:3,
					render:function(data,type,full){
						content = '';
						for(var i=0;i<data.length;i++)
						{
							content += data[i].name;
							if(data[i].content.length>0)
							{
								content += ('('+data[i].content+')');
							}
							content += ('x'+data[i].num+'<br>');
						}
						return content;
					}
				},{
					targets:4,
					render:function(data,type,full){
						return '<button class="btn btn-xs yellow btn-outline changeShip" data-id="'+data+'">修改配送信息</button><button class="btn btn-xs blue btn-outline lookShip" data-id="'+data+'">查看物流信息</button>';
					}
				}],
                "order": [
                    [0, "asc"]
                ] // set first column as a default sort by asc
            }
        });
    }

    var initPickers = function () {
        //init date pickers
        $('.date-picker').datepicker({
            rtl: App.isRTL(),
            autoclose: true
        });

        $(".datetime-picker").datetimepicker({
            isRTL: App.isRTL(),
            autoclose: true,
            todayBtn: true,
            pickerPosition: (App.isRTL() ? "bottom-right" : "bottom-left"),
            minuteStep: 10
        });
    }

    return {

        //main function to initiate the module
        init: function () {
            initPickers();
		    handleShipment();
        }

    };

}();

jQuery(document).ready(function() {    
   EcommerceOrdersView.init();
});