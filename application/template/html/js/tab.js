// JavaScript Document
$('.tab').on('click','.tab-title',function(){
	
	"use strict";
	
	$(this).siblings().removeClass('active');
	$(this).addClass('active');
	
	var href = $(this).attr('href') || $(this).data('href');
	if($(href).length===1)
	{
		$(href).siblings().removeClass('active');
		$(href).addClass('active');
	}
	else
	{
		window.location = href;
	}
	return false;
});