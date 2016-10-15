var EcommerceOrdersView = function () {

    var unixtotime = function (unixTime, isFull, timeZone) {
        if (typeof (timeZone) == 'number') {
            unixTime = parseInt(unixTime) + parseInt(timeZone) * 60 * 60;
        }
        var time = new Date(unixTime * 1000);
        var ymdhis = "";
        ymdhis += time.getUTCFullYear() + "-";
        ymdhis += (time.getUTCMonth() + 1) + "-";
        ymdhis += time.getUTCDate();
        if (isFull === true) {
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
                    [10, 20, 30, 100, 150, 200, -1],
                    [10, 20, 30, 100, 150, 200, "All"] // change per page values here
                ],
                createdRow: function (row, data, index) {
                    $(row).find('.popovers').popover();
                },
                "pageLength": 10, // default record count per page
                "ajax": {
                    "url": "index.php?c=datatables&a=refund", // ajax source
                },
                columns: [{
                    data: 'refundno',
                    name: 'refund.refundno',
                    visible: true,
                }, {
                    data: 'refundno',
                    name: 'refund.refundno',
                    visible: true,
                }, {
                    data: 'orderno',
                    name: 'refund.orderno',
                    orderable: true,
                }, {
                    data: 'name',
                    name: 'product.name',
                    orderable: true,
                }, {
                    data: 'status',
                    name: 'refund.status',
                    orderable: false,
                },
                {
                    data: 'createtime',
                    name: 'refund.createtime',
                    orderable: true,
                }, {
                    data: 'completetime',
                    name: 'refund.completetime',
                    orderable: true,
                }, {
                    data: 'money',
                    name: 'refund.money',
                    orderable: true,
                }, {
                    data: 'reason',
                    name: 'refund.reason',
                    orderable: true,
                },
                {
                    data: 'refundno',
                    name: 'refund.refundno',
                    orderable: false,
                }
                //隐藏字段
                ,{
                    data:'sku',
                    name:'product.sku',
                    visible:false,
                }],
                "columnDefs": [{
                    targets: 0,
                    render: function (data, type, full) {
                        return '<input type="checkbox" class="checkboxes" name="refundno[]" value=' + data + '>';
                    }
                },{
                    targets:3,
                    render:function(data,type,full){
                        if (data!=null) {
                            return data+'('+full.sku+')';
                        }
                        return '';
                    }
                },{
                    targets:4,
                    render:function(data,type,full){
                        switch(data)
                        {
                            case '0':return '未完成';
                            case '1':return '完成';
                            case '2':return '失败';
                        }
                        return '';
                    }
                },{
                    targets:[5,6],
                    render:function(data,type,full){
                        return unixtotime(data,true,8);
                    }
                },{
                    targets:9,
                    render:function(data,type,full){
                        return '';
                    }
                }],
                "order": [
                    [4, "desc"]
                ] // set first column as a default sort by asc
            }
        });

        grid.getTableWrapper().on('click', '.table-group-action-submit', function (e) {
            e.preventDefault();
            var action = $(".table-group-action-input", grid.getTableWrapper());
            if (action.val() != "" && grid.getSelectedRowsCount() > 0) {
                if (action.val() == 'export') {
                    var form = document.createElement("form");
                    form.action = './index.php?c=export&a=vip';
                    form.target = '_blank';
                    form.method = 'post';

                    var ids = grid.getSelectedRows();
                    for (var i = 0; i < ids.length; i++) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'id[]';
                        input.value = ids[i];
                        form.appendChild(input);
                    }
                    form.submit();
                }
                else {
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
            forceParse: true,
            language: 'zh-CN',
            autoclose: true,
        }).on('changeDate', function (event) {
            $('.date-picker2').datepicker('setStartDate', $('.date-picker1').find('input').val());
        });

        $('.date-picker2').datepicker({
            rtl: App.isRTL(),
            forceParse: true,
            language: 'zh-CN',
            autoclose: true,
        }).on('changeDate', function (event) {
            $('.date-picker1').datepicker('setEndDate', $('.date-picker2').find('input').val());
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

jQuery(document).ready(function () {
    EcommerceOrdersView.init();
});
