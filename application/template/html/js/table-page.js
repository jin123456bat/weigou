var showPage = function(tpl,tr,start,length,total,canNext,canPrev){
	//对tr进行隐藏操作
	for(var i=0;i<total;i++)
	{
		if(i>=start && i<start+length)
		{
			$(tr[i]).removeClass('display-none');
		}
		else
		{
			$(tr[i]).addClass('display-none');
		}
	}
	
	if(!canNext)
	{
		tpl.find('li:last').addClass('disabled');
	}
	else
	{
		tpl.find('li:last').removeClass('disabled');
	}
	if(!canPrev)
	{
		tpl.find('li:first').addClass('disabled');
	}
	else
	{
		tpl.find('li:first').removeClass('disabled');
	}
}


$_tpl = $('<nav aria-label="Search results pages" style="text-align: center;"><ul class="pagination" style="padding:0px;margin:0px;"><li data-name="prev"><span><span aria-hidden="true">&laquo;</span></span></li></ul></nav>');

//每页显示多少行
$_length = 5;

$_start = 0;


tr = $('table.table-page tbody tr');
//有多少列
$_colspan = $(tr[0]).find('td').length;

tbody_length = tr.length;
if(tbody_length>$_length)
{
	canNext = true;
	canPrev = true;
	//生成中间的数字页
	for(var i=1;i<=Math.floor(tbody_length/$_length)+1;i++)
	{
		if(($_start/$_length)+1 == i)
		{
			if(i==1)
			{
				canPrev = false;
			}
			if(i==Math.floor(tbody_length/$_length)+1)
			{
				canNext = false;
			}
			page = $('<li data-name="'+i+'" class="active"><span>'+i+'<span class="sr-only">(current)</span></span></li>');
		}
		else
		{
			page = $('<li data-name="'+i+'" class=""><span>'+i+'</span></li>');
		}
		page.insertAfter($_tpl.find('li:last'));
	}
	
	nextBtn = $('<li data-name="next"><span><span aria-hidden="true">&raquo;</span></span></li>');
	nextBtn.insertAfter($_tpl.find('li:last'));
	
	//判断是否可以翻上一页或者下一页
	if(!canNext)
	{
		$_tpl.find('li:last').addClass('disabled');
	}
	else
	{
		$_tpl.find('li:last').removeClass('disabled');
	}
	if(!canPrev)
	{
		$_tpl.find('li:first').addClass('disabled');
	}
	else
	{
		$_tpl.find('li:first').removeClass('disabled');
	}
	
	$_tpl.find('li').on('click',function(){
		if($(this).hasClass('disabled'))
		{
			return false;
		}
		var name=$(this).data('name');
		if(name=="prev")
		{
			canPrev = true;
			canNext = true;
			btn = $('li.active');
			if(parseInt(btn.data('name'))==2)
			{
				canPrev = false;
			}
			if(parseInt(btn.data('name'))==Math.floor(tbody_length/$_length))
			{
				canNext = false;
			}
			$_start -= $_length;
			showPage($_tpl,tr,$_start,$_length,tbody_length,canNext,canPrev);
			btn.removeClass('active');
			btn.prev('li').addClass('active');
		}
		else if(name=="next")
		{
			canPrev = true;
			canNext = true;
			btn = $('li.active');
			if(parseInt(btn.data('name')) == Math.floor(tbody_length/$_length))
			{
				canNext = false;
			}
			if(parseInt(btn.data('name'))==2)
			{
				canPrev = false;
			}
			$_start += $_length;
			showPage($_tpl,tr,$_start,$_length,tbody_length,canNext,canPrev);
			btn.next('li').addClass('active');
			btn.removeClass('active');
		}
		else
		{
			canPrev = true;
			canNext = true;
			btn = $(this);
			if(parseInt(btn.data('name')) == Math.floor(tbody_length/$_length)+1)
			{
				canNext = false;
			}
			if(parseInt(btn.data('name'))==1)
			{
				canPrev = false;
			}
			$_start = (parseInt(name)-1)*$_length;
			showPage($_tpl,tr,$_start,$_length,tbody_length,canNext,canPrev);
			
			btn.siblings().removeClass('active');
			btn.addClass('active');
		}
		
	});
	
	showPage($_tpl,tr,$_start,$_length,tbody_length,canNext,canPrev);
	
	
	//把分页数据放到tfoot中
	var tfoot = $('<tfoot><tr><td colspan="'+$_colspan+'"></td></tr></tfoot>');
	tfoot.find('td').append($_tpl);
	$('table.table-page').append(tfoot);
}