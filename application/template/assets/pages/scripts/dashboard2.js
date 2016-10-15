var Dashboard = function() {

    return {
        initDashboardDaterange: function() {
            if (!jQuery().daterangepicker) {
                return;
            }

            $('#dashboard-report-range').daterangepicker({
                "ranges": {
                    '今天': [moment(), moment()],
                    '昨天': [moment().subtract('days', 1), moment().subtract('days', 1)],
                    '最近7天': [moment().subtract('days', 6), moment()],
                    '最近30天': [moment().subtract('days', 29), moment()],
                    '这个月': [moment().startOf('month'), moment().endOf('month')],
                    '上个月': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
                },
                "locale": {
                    "format": "YYYY-MM-DD",
                    "separator": " - ",
                    "applyLabel": "应用",
                    "cancelLabel": "取消",
                    "fromLabel": "从",
                    "toLabel": "到",
                    "customRangeLabel": "自定义",
                    "daysOfWeek": [
                        "日",
                        "一",
                        "二",
                        "三",
                        "四",
                        "五",
                        "六"
                    ],
                    "monthNames": [
                        "一月",
                        "二月",
                        "三月",
                        "四月",
                        "五月",
                        "六月",
                        "七月",
                        "八月",
                        "九月",
                        "十月",
                        "十一月",
                        "十二月"
                    ],
                    "firstDay": 1
                },
                opens: (App.isRTL() ? 'right' : 'left'),
				
            }, function(start, end, label) {
                $('#dashboard-report-range span').html(start.format('YYYY-MM-DD') + ' ~ ' + end.format('YYYY-MM-DD'));
				Dashboard.initAmChart1(start.format('YYYY-MM-DD'),end.format('YYYY-MM-DD'));
				Dashboard.initAmChart2(start.format('YYYY-MM-DD'),end.format('YYYY-MM-DD'));
				Dashboard.initAmChart3(start.format('YYYY-MM-DD'),end.format('YYYY-MM-DD'));
			});
			
            $('#dashboard-report-range span').html(moment().subtract('days', 7).format('YYYY-MM-DD') + ' - ' + moment().format('YYYY-MM-DD'));
            $('#dashboard-report-range').show();
        },

        initAmChart1: function(start,end) {
            if (typeof(AmCharts) === 'undefined' || $('#dashboard_amchart_1').size() === 0) {
                return;
            }
			
			var chartData = [];
			
			$.ajax({
				url:'./index.php?m=ajax&c=source&a=dashboard2&type=order',
				async:false,
				method:'post',
				data:{start:start,end:end},
				success: function(response){
					chartData = response;
				}
			});
			
            var chart = AmCharts.makeChart("dashboard_amchart_1", {
                type: "serial",
                fontSize: 12,
                fontFamily: "Open Sans",
                dataDateFormat: "YYYY-MM-DD",
                dataProvider: chartData,

                addClassNames: true,
                startDuration: 1,
                color: "#6c7b88",
                marginLeft: 0,

                categoryField: "date",
                categoryAxis: {
                    parseDates: true,
                    minPeriod: "DD",
                    autoGridCount: false,
                    gridCount: 50,
                    gridAlpha: 0.1,
                    gridColor: "#FFFFFF",
                    axisColor: "#555555",
                    dateFormats: [{
                        period: 'DD',
                        format: 'DD'
                    }, {
                        period: 'WW',
                        format: 'MMM DD'
                    }, {
                        period: 'MM',
                        format: 'MMM'
                    }, {
                        period: 'YYYY',
                        format: 'YYYY'
                    }]
                },

                valueAxes: [{
                    id: "a1",
                    title: "订单量",
                    gridAlpha: 0,
                    axisAlpha: 0
                }, {
                    id: "a2",
                    position: "right",
                    gridAlpha: 0,
                    axisAlpha: 0,
                    labelsEnabled: false
                }],
                graphs: [{
                    id: "g1",
                    valueField: "total",
                    title: "订单量",
                    type: "column",
                    fillAlphas: 0.7,
                    valueAxis: "a1",
                    balloonText: "总计[[value]] 个",
                    legendValueText: "[[date]] : [[value]] 个",
                    legendPeriodValueText: "总计: [[value.sum]] 个",
                    lineColor: "#08a3cc",
                    alphaField: "alpha",
                }
//                , {
//                    id: "g2",
//                    valueField: "payed",
//                    classNameField: "bulletClass",
//                    title: "成交量",
//                    type: "line",
//                    valueAxis: "a2",
//                    lineColor: "#786c56",
//                    lineThickness: 1,
//                    legendValueText: "[[date]] : [[value]]",
//                    descriptionField: "date",
//                    bullet: "round",
//                    bulletSizeField: "size",
//                    bulletBorderColor: "#02617a",
//                    bulletBorderAlpha: 1,
//                    bulletBorderThickness: 2,
//					legendPeriodValueText: "总计: [[value.sum]] 个",
//                    bulletColor: "#89c4f4",
//                    labelPosition: "right",
//                    balloonText: "成交:[[value]]",
//                    showBalloon: true,
//                    animationPlayed: true,
//                }
                ],

                chartCursor: {
                    zoomable: false,
                    categoryBalloonDateFormat: "YYYY-MM-DD",
                    cursorAlpha: 0,
                    categoryBalloonColor: "#e26a6a",
                    categoryBalloonAlpha: 0.8,
                    valueBalloonsEnabled: false
                },
                legend: {
                    bulletType: "round",
                    equalWidths: false,
                    valueWidth: 120,
                    useGraphSettings: true,
                    color: "#6c7b88"
                }
            });
        },
		
		initAmChart2: function(start,end) {
            if (typeof(AmCharts) === 'undefined' || $('#dashboard_amchart_1').size() === 0) {
                return;
            }
			
			var chartData = [];
			
			$.ajax({
				url:'./index.php?m=ajax&c=source&a=dashboard2&type=payed',
				async:false,
				method:'post',
				data:{start:start,end:end},
				success: function(response){
					chartData = response;
				}
			});
			
            var chart = AmCharts.makeChart("dashboard_amchart_2", {
                type: "serial",
                fontSize: 12,
                fontFamily: "Open Sans",
                dataDateFormat: "YYYY-MM-DD",
                dataProvider: chartData,

                addClassNames: true,
                startDuration: 1,
                color: "#6c7b88",
                marginLeft: 0,

                categoryField: "date",
                categoryAxis: {
                    parseDates: true,
                    minPeriod: "DD",
                    autoGridCount: false,
                    gridCount: 50,
                    gridAlpha: 0.1,
                    gridColor: "#FFFFFF",
                    axisColor: "#555555",
                    dateFormats: [{
                        period: 'DD',
                        format: 'DD'
                    }, {
                        period: 'WW',
                        format: 'MMM DD'
                    }, {
                        period: 'MM',
                        format: 'MMM'
                    }, {
                        period: 'YYYY',
                        format: 'YYYY'
                    }]
                },

                valueAxes: [{
                    id: "a1",
                    title: "订单金额",
                    gridAlpha: 0,
                    axisAlpha: 0
                }, {
                    id: "a2",
                    position: "right",
                    gridAlpha: 0,
                    axisAlpha: 0,
                    labelsEnabled: false
                }],
                graphs: [{
                    id: "g1",
                    valueField: "total",
                    title: "订单金额",
                    type: "column",
                    fillAlphas: 0.7,
                    valueAxis: "a1",
                    balloonText: "总计[[value]] 元",
                    legendValueText: "[[date]] : [[value]] 元",
                    legendPeriodValueText: "总计: [[value.sum]] 元",
                    lineColor: "#08a3cc",
                    alphaField: "alpha",
                }
//                , {
//                    id: "g2",
//                    valueField: "payed",
//                    classNameField: "bulletClass",
//                    title: "成交量",
//                    type: "line",
//                    valueAxis: "a2",
//                    lineColor: "#786c56",
//                    lineThickness: 1,
//                    legendValueText: "[[date]] : [[value]]",
//                    descriptionField: "date",
//                    bullet: "round",
//                    bulletSizeField: "size",
//                    bulletBorderColor: "#02617a",
//                    bulletBorderAlpha: 1,
//                    bulletBorderThickness: 2,
//					legendPeriodValueText: "总计: [[value.sum]] 元",
//                    bulletColor: "#89c4f4",
//                    labelPosition: "right",
//                    balloonText: "成交:[[value]]",
//                    showBalloon: true,
//                    animationPlayed: true,
//                }
                ],

                chartCursor: {
                    zoomable: false,
                    categoryBalloonDateFormat: "YYYY-MM-DD",
                    cursorAlpha: 0,
                    categoryBalloonColor: "#e26a6a",
                    categoryBalloonAlpha: 0.8,
                    valueBalloonsEnabled: false
                },
                legend: {
                    bulletType: "round",
                    equalWidths: false,
                    valueWidth: 120,
                    useGraphSettings: true,
                    color: "#6c7b88"
                }
            });
        },


        initAmChart3: function(start,end) {
            if (typeof(AmCharts) === 'undefined' || $('#dashboard_amchart_3').size() === 0) {
                return;
			}
			
			var chartData = [];
			$.ajax({
				url:'./index.php?m=ajax&c=source&a=dashboard2&type=user',
				async:false,
				method:'post',
				data:{start:start,end:end},
				success: function(response){
					chartData = response;
				}
			});

            var chart = AmCharts.makeChart("dashboard_amchart_3", {
                "type": "serial",
                "addClassNames": true,
                "theme": "light",
                "path": "./application/template/assets/global/plugins/amcharts/ammap/images/",
                "autoMargins": false,
                "marginLeft": 30,
                "marginRight": 8,
                "marginTop": 10,
                "marginBottom": 26,
                "balloon": {
                    "adjustBorderColor": false,
                    "horizontalPadding": 10,
                    "verticalPadding": 8,
                    "color": "#ffffff"
                },
				
                "dataProvider": chartData,
                "valueAxes": [{
                    "axisAlpha": 0,
                    "position": "left"
                }],
                "startDuration": 1,
                "graphs": [{
                    "alphaField": "alpha",
                    "balloonText": "<span style='font-size:12px;'>注册量:<br><span style='font-size:20px;'>[[value]]</span> [[additional]]</span>",
                    "fillAlphas": 1,
                    "title": "注册",
                    "type": "column",
                    "valueField": "user",
                    "dashLengthField": "dashLengthColumn",
					
                    legendValueText: "[[date]] : [[value]] 个",
                    legendPeriodValueText: "总计: [[value.sum]] 个",
                }],
                "categoryField": "date",
                "categoryAxis": {
					parseDates: true,
                    minPeriod: "DD",
                    autoGridCount: false,
                    gridCount: 50,
                    gridAlpha: 0.1,
                    gridColor: "#FFFFFF",
                    axisColor: "#555555",
                    dateFormats: [{
                        period: 'DD',
                        format: 'DD'
                    }, {
                        period: 'WW',
                        format: 'MMM DD'
                    }, {
                        period: 'MM',
                        format: 'MMM'
                    }, {
                        period: 'YYYY',
                        format: 'YYYY'
                    }]
                },
				chartCursor: {
                    zoomable: true,
                    categoryBalloonDateFormat: "YYYY-MM-DD",
                    cursorAlpha: 0,
                    categoryBalloonColor: "#e26a6a",
                    categoryBalloonAlpha: 0.8,
                    valueBalloonsEnabled: false
                },
				legend: {
                    bulletType: "round",
                    equalWidths: false,
                    valueWidth: 120,
                    useGraphSettings: true,
                    color: "#6c7b88"
                },
                "export": {
                    "enabled": false
                }
            });
        },

        init: function() {
            this.initDashboardDaterange();
            this.initAmChart1();
			this.initAmChart2();
            this.initAmChart3();
        }
    };

}();

if (App.isAngularJsApp() === false) {
    jQuery(document).ready(function() {
        Dashboard.init(); // init metronic core componets
    });
}