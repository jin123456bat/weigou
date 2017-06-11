var swift = datatables({
	table:$('#swift table'),
	ajax:{
		url:'./index.php?c=datatables&a=swift',
		data:{
			uid:$_uid,
		},
	},
	columns:[{
		data:'type',
		name:'swift.type',
	},{
		data:'time',
		name:'swift.time',
	},{
		data:'money',
		name:'swift.money',
	},{
		data:'source',
		name:'swift.source',
	},{
		data:'note',
		name:'swift.note',
	}],
	columnDefs:[{
		targets:0,
		render:function(data,type){
			return parseInt(data)==1?'支出':'收入';
		}
	},{
		targets:1,
		render:function(data,type){
			return unixtotime(data,true,8);
		}
	},{
		targets:3,
		render:function(data,type){
			switch(parseInt(data))
			{
				case 0:return '管理员修改';
				case 1:return '提现申请';
				case 2:return '一级销售分成';
				case 3:return '二级销售分成';
				case 4:return '导师销售分成';
				case 5:return '一级VIP分成';
				case 6:return '二级VIP分成';
				case 7:return '导师VIP分成';
				case 8:return '订单退款';
			}
			return '';
		}
	}],
	pagesize:10,
});