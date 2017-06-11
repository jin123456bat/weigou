var team = datatables({
	table:$('#team table'),
	ajax:{
		url:'./index.php?c=datatables&a=user',
		data:{
			close:0,
			oid:$_uid,
		},
	},
	columns:[{
		data:'id',
		name:'user.id',
	},{
		data:'gravatar',
		name:'(select upload.path from upload where upload.id=user.gravatar limit 1)',
	},{
		data:'name',
		name:'user.name',
	},{
		data:'vip',
		name:'user.vip',
	},{
		data:'telephone',
		name:'user.telephone',
	},{
		data:'wechat_no',
		name:'user.wechat_no',
	},{
		data:'o_master',
		name:'(select u.name from user as u where u.id=user.o_master limit 1)',
	},{
		data:'oid',
		name:'(select us.name from user as us where us.id=user.oid limit 1)',
	},{
		data:'money',
		name:'user.money',
	},{
		data:'invit',
		name:'user.invit',
	},{
		data:'regtime',
		name:'user.regtime',
	},{
		data:'master',
		name:'user.master',
	},{
		data:'id',
		name:'user.id',
	}],
	columnDefs:[{
		targets:0,
		render:function(data,full){
			return '<input type="checkbox" name="id[]" value="'+data+'">';
		}
	},{
		targets:1,
		render:function(data,full){
			return '<img src="'+data+'" onerror="this.src=\'https://placeholdit.imgix.net/~text?txtsize=13&txt=%E5%A4%B4%E5%83%8F&w=50&h=50\';" width="50" height="50" class="img-circle">';
		}
	},{
		targets:3,
		render:function(data,type){
			switch(parseInt(data))
			{
				case 0:return '普通';
				case 1:return '白金';
				case 2:return '钻石';
			}
			return '错误';
		}
	},{
		targets:5,
		render:function(data,type){
			if(data.length==0)
			{
				return '<font color="BCBCBC">暂无</font>';
			}
			return data;
		}
	},{
		targets:6,
		render:function(data,type){
			if(data==null)
			{
				return '<font color="BCBCBC">暂无</font>';
			}
			return data;
		}
	},{
		targets:7,
		render:function(data,type){
			if(data==null)
			{
				return '<font color="BCBCBC">暂无</font>';
			}
			return data;
		}
	},{
		targets:10,
		render:function(data,type){
			return unixtotime(data,true,8);
		}
	},{
		targets:11,
		render:function(data,type){
			return '<div style="cursor:default;" class="checkbox '+(data==1?'active blue':'')+'">'+(data==1?'导师':'普通')+'</div>';
		}
	},{
		targets:12,
		render:function(data,full){
			content = 
			`
			<div class="btn-group" role="group">
				<button class="btn btn-default btn-xs look" data-id="${data}">
					查看/编辑
				</button>
				<button class="btn btn-xs btn-default btn-icon dropdown-toggle" data-toggle="dropdown">
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu dropdown-sm">
				  <li><a class="blacklist" data-id="${data}" href="#">加入黑名单</a></li>
				</ul>
			</div>`;
			return content;
		}
	}],
	pagesize:10,
	onRowLoaded:function(row){
		row.find('td:last').css('text-align','center');
		row.find('td:first').css({'padding-left':'20px','padding-right':'20px'});
	}
});

$('#team_search').on('submit',function(){
	team.search($(this).find('input').val());
	return false;
});

$('#team table').on('click','.blacklist',function(){
	var id =$(this).data('id');
	var tr = $(this).parents('tr');
	$.post('./index.php?m=ajax&c=user&a=close',{id:id},function(response){
		if(response.code==1)
		{
			tr.remove();
		}
		else
		{
			bootbox.alert(response.result);
		}
	});
	return false;
}).on('click','.look',function(){
	window.location = './index.php?c=html&a=userinfo&id='+$(this).data('id');
});