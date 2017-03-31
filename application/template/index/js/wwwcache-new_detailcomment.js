jQ(document).ready(function($){ 
    /*评论分页上一页*/
    $('#up').live('click', function(){
        pn = $(this).attr('data1');
        if(typeof(pn) != 'undefined')
        {
            var size = $(this).attr('data2');
            var type = $(this).attr('data3');
            var funName = typeof g_comment_funname == 'undefined' ? ajax_proexstyle_comment : g_comment_funname;
            if(type == 'proexstyle')
            {
            	funName(size, pn);
            }
            else if(type == 'diamond')
        	{
            	funName(size, pn);
        	}
        }
    })

    /*评论分页下一页*/
    $('#next').live('click', function(){
        pn = $(this).attr('data1');
        if(typeof(pn) != 'undefined')
        {
            var size = $(this).attr('data2');
            var type = $(this).attr('data3');
            var funName = typeof g_comment_funname == 'undefined' ? ajax_proexstyle_comment : g_comment_funname;
            if(type == 'proexstyle')
            {
            	funName(size, pn);
            }
            else if(type == 'diamond')
        	{
            	funName(size, pn);
        	}
        }
    })
    /*咨询分页上一页*/
    $('#consult_up').live('click', function(){
        pn = $(this).attr('data1');
        if(typeof(pn) != 'undefined')
        {
            var size = $(this).attr('data2');
            var type = $(this).attr('data3');
            if(type == 'proexstyle' || type == 'pairring')
            {
            	ajax_proexstyle_consult(size, pn);
            }
        }
    })

    /*咨询分页下一页*/
    $('#consult_next').live('click', function(){
        pn = $(this).attr('data1');
        if(typeof(pn) != 'undefined')
        {
            var size = $(this).attr('data2');
            var type = $(this).attr('data3');
            if(type == 'proexstyle' || type == 'pairring')
            {
            	ajax_proexstyle_consult(size, pn);
            }
        }
    })
})

/**
 * [异步加载裸钻商品评论]
 * @param  val	分数段（10/20/30/...）
 * @param  pn	查询的页码
 */
function ajax_diamond_comment(val, pn)
{
    var loadingEleMent = $('#loadingCommentShow');
    setLoadingPos('loadingCommentShow','luozuanpinglun');
    $.ajax({
        'url' : '/rate/ajaxcomment',
        'type' : 'POST',
        'data' :  {type : 'diamond', diamond : val, pn : pn},
        'success' : function(data){
            /*如果返回的内容为空 那就hide页面的评论内容*/
            if($.trim(data) == '')
            {
                $('.luozuanpinglun').hide();
                $('#luozuanpinglun').hide();
            }
            else
            {
                $('#luozuanpinglun').html(data);
            }
            if( loadingEleMent.length > 0 ) loadingEleMent.fadeOut();
            $('#luozuanpinglun').fadeTo('slow',1);
        }
    })
}

/**
 * [异步加载裸钻商品评论]
 * @param  val	分数段（10/20/30/...）
 * @param  pn	查询的页码
 */
function ajax_diamond_comment_diamond(val, pn)
{
    var loadingEleMent = $('#loadingCommentShow');
    setLoadingPos('loadingCommentShow','luozuanpinglun_diamond');
    $.ajax({
        'url' : '/rate/ajaxcommentdiamond',
        'type' : 'POST',
        'data' :  {type : 'diamond', diamond : val, pn : pn,id:gCurrCfg.productId},
        'success' : function(data){
            /*如果返回的内容为空 那就hide页面的评论内容*/
            if($.trim(data) == '')
            {
                $('.luozuanpinglun').hide();
                $('#luozuanpinglun_diamond').hide();
            }
            else
            {
                $('#luozuanpinglun_diamond').html(data);
                var num = $('#luozuanpinglun_diamond').find('#totalComment').html();
                $('.tab_text').html('<strong>'+num ? num : 0+'</strong>');
            }
            if( loadingEleMent.length > 0 ) loadingEleMent.fadeOut();
            $('#luozuanpinglun_diamond').fadeTo('slow',1);
        }
    })
}

/**
 * [异步加载成品商品评论]
 * @param  val	款式号
 * @param  pn	查询的页码
 */
function ajax_proexstyle_comment(val, pn)
{
    var loadingEleMent = $('#loadingCommentShow');
    setLoadingPos('loadingCommentShow','chengpinpinglun');
    if(undefined!= $('#quality').val()){this.quality = $('#quality').val()}else{this.quality='';}
    $.ajax({
        'url' : '/rate/ajaxcomment',
        'type' : 'POST',
        'data' :  {type : 'proexstyle', proexstyle : val, pn : pn,quality:this.quality,id:gCurrCfg.productId},
        'success' : function(data){
            /*如果返回的内容为空 那就hide页面的评论内容*/
            if($.trim(data) == '')
            {
                //$('.comments_ls').hide();
            }
            else
            {
                $('#chengpinpinglun').html(data);
                var num = $('#chengpinpinglun').find('#totalComment').html();
                $('.tab_text').html('<strong>'+num ? num : 0+'</strong>');
            }
            if( loadingEleMent.length > 0 ) loadingEleMent.fadeOut();
            $('#chengpinpinglun').fadeTo('slow',1);
        }
    })
}

/**
 * [异步加载成品商品咨询]
 * @param  val	款式id
 * @param  pn	查询的页码
 */
function ajax_proexstyle_consult(val, pn)
{
	var topicVal = (typeof g_topic != 'undefined') ? g_topic : 'pairring';
	var url = '/comment/ajaxconsult';
	url += (typeof g_tpl != 'undefined') ? '/tpl/' + g_tpl : '';
    var loadingEleMent = $('#loadingCommentShow');
    setLoadingPos('loadingCommentShow','chengpinconsult');
    $.ajax({
        'url' : url,
        'type' : 'POST',
        'data' :  {type : 'consulting', topic : topicVal, id : val, pn : pn},
        'success' : function(data){
            /*如果返回的内容为空 那就hide页面的评论内容*/
            if($.trim(data) == '')
            {
                $('.FAQ').hide();
            }
            else
            {
                $('#chengpinconsult').html(data);
            }
            if( loadingEleMent.length > 0 ) loadingEleMent.fadeOut();
            $('#chengpinconsult').fadeTo('slow',1);
        }
    })
}
function ajax_proexstyle_consultdz(val, pn)
{
    var loadingEleMent = $('#loadingCommentShow');
    setLoadingPos('loadingCommentShow','chengpinconsultdz');
    $.ajax({
        'url' : '/comment/ajaxconsultdz',
        'type' : 'POST',
        'data' :  {type : 'consulting', topic : 'pairring', id : val, pn : pn},
        'success' : function(data){
            /*如果返回的内容为空 那就hide页面的评论内容*/
            if($.trim(data) == '')
            {
                $('.FAQ').hide();
            }
            else
            {
                $('#chengpinconsultdz').html(data);
            }
            if( loadingEleMent.length > 0 ) loadingEleMent.fadeOut();
            $('#chengpinconsultdz').fadeTo('slow',1);
        }
    })
}
function pageHover(obj, flg){
	if(!flg){
		if($(obj).attr('id') == 'consult_up' || $(obj).attr('id') == 'up'){
			$(obj).removeClass('upA');
			$(obj).addClass('upB');
		}
		if($(obj).attr('id') == 'consult_next' || $(obj).attr('id') == 'next'){
			$(obj).removeClass('nextA');
			$(obj).addClass('nextB');
		}
	}
}

function pageBlur(obj, flg){
	if(!flg){
		if($(obj).attr('id') == 'consult_up' || $(obj).attr('id') == 'up'){
			$(obj).removeClass('upB');
			$(obj).addClass('upA');
		}
		if($(obj).attr('id') == 'consult_next' || $(obj).attr('id') == 'next'){
			$(obj).removeClass('nextB');
			$(obj).addClass('nextA');
		}
	}
}