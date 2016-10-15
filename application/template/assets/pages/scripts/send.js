$(document).ready(function(e) {
    //拆分按钮
	var explodePackageBtn = '<button class="btn btn-xs blue explodePackage">拆分</button>';
	//合并按钮
	var implodePackageBtn = '<button class="btn btn-xs green implodePackage">合并</button>';
	
	//库存所在第几列
	var stock_line = 2;
	//一次拆分几个
	var explodeNum = 1;
		
	//拆分按钮
	$('#sendModal').on('click','.explodePackage',function(){
		var old_stock = parseInt($(this).parents('tr').find('td:eq('+stock_line+')').html());
		$(this).parents('tr').find('td:eq('+stock_line+')').html(old_stock - explodeNum);
		var tr = $(this).parents('tr').clone();
		tr.find('td:eq('+stock_line+')').html(explodeNum);
		tr.find('td:eq(5)').empty().append(implodePackageBtn);
		tr.insertAfter($(this).parents('tr'));
		
		if(old_stock - explodeNum  == 1)
		{
			$(this).remove();
		}
		return false;
	});
	
	//合并按钮
	$('#sendModal').on('click','.implodePackage',function(){
		var this_line_stock = parseInt($(this).parents('tr').find('td:eq('+stock_line+')').html());
		var prev_line_stock = parseInt($(this).parents('tr').prev().find('td:eq('+stock_line+')').html());
		$(this).parents('tr').prev().find('td:eq('+stock_line+')').html(this_line_stock+prev_line_stock);
		if($(this).parents('tr').prev().find('td:eq(5)').find('.explodePackage').length == 0)
		{
			$(this).parents('tr').prev().find('td:eq(5)').append(explodePackageBtn);
		}
		$(this).parents('tr').remove();
		return false;
	});
	
	$('#sendModal').on('click','.confirmSend',function(){
		$('body').modalmanager('loading');
		var data = [];
		var tr = $('#sendTable').find('.package');
		$.each(tr,function(index,value){
			data.push({
				id:$(value).data('id'),
				ship_type:$(value).find('select[name=ship_type]').val(),
				ship_number:$(value).find('input[name=ship_number]').val(),
			});
		});
		var orderno = $(this).data('orderno');
		$.post('index.php?m=ajax&c=order&a=confirmSend',{orderno:orderno,data:JSON.stringify(data)},function(response){
			$('body').modalmanager('loading');
			if(response.code==1)
			{
				$('#sendModal').modal('hide');
				$('button.send[data-orderno='+orderno+']').parents('tr').find('td:eq(5)').find('font').html('已发货');
				$('button.send[data-orderno='+orderno+']').remove();
			}
			else
			{
				alert(response.result);
			}
		});
		return false;
	});	
});