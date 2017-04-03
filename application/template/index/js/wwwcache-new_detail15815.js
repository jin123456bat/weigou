 jQ(document).ready(function($){
 /*手寸选择*/
    $(".qualitySizeOpt").click(function (){
        $(this).parent().prev('input').val($(this).text());
    });
    //更多现货选择
    $('#more_clk').click(function() {
      $('#hide_tab a').removeClass('on');
      $('.f_cont').hide();
      $(this).addClass('on');
       $('#hide_tab a').eq(4).addClass('on');
       $('.f_cont').eq(4).show();
        $("html,body").animate({scrollTop: $('.g_dtal_wrap').offset().top+$('.g_dtal_wrap').height()+3}, 500);
    });
      
///*fixed滚动*/
  $(window).scroll(function(){ 
   
    var pr_top= $('.g_dtal_wrap').offset().top+$('.g_dtal_wrap').height()+3;
    var fix_h=$('.f_sys_tab').height();
    var s_tp=$(document).scrollTop();
    if (s_tp>pr_top) {
      $('.f_sys_tab').attr('id', 'fix_tab').css('border-top', 'none');
    }else{
            $('.f_sys_tab').removeAttr('id style');
          }
  })
//tab切换
  $('#hide_tab a').click(function() {
     $('#more_clk').removeClass('on');
    var th_indx=$(this).index('#hide_tab a');
     $('#hide_tab a').removeClass('on');
     $(this).addClass('on');
     $("html,body").animate({scrollTop: $('.g_dtal_wrap').offset().top+$('.g_dtal_wrap').height()+3}, 500);
      var s=$(this).parents('.f_sys_tab').siblings('.f_cont').eq(th_indx).index();
      $('.f_sys_tab').siblings('.f_cont').hide();
      $(this).parents('.f_sys_tab').siblings('.f_cont').eq(th_indx).show(); 
      $('.f_sys_tab').siblings('.f_cont').hide();
     
      if (th_indx==0) {
        
        $(this).parents('.f_sys_tab').siblings('.f_cont').eq(th_indx).show();
        $(this).parents('.f_sys_tab').siblings('.f_cont').eq(th_indx+2).show();
        $('#u_comm_tlt').show();
      }else{
        $(this).parents('.f_sys_tab').siblings('.f_cont').eq(th_indx).show();
         $('#u_comm_tlt').hide();
      }
  });

  //品牌备述
  var time=3500;
  var totl=$('.f_brand_tlt a').length;
  var nw_this=1;
  var setin=setInterval(movechange,time);
 $(".f_brand_tlt a").mouseover(function() {
      stop();
      var number = parseInt($(this).index('.f_brand_tlt a'));     
    $('.f_brand_tlt a').removeClass('on');
      $('.f_brand_con div').hide();
    $(this).addClass('on');
        $(this).parent('.f_brand_tlt').siblings('.f_brand_con').children('div').eq(number).show();
    });
    $(".f_brand_tlt a").mouseout(function() {
          move();
    })
    $('.f_brand_con div').mouseover(function(){
           stop();

    })
    $(".f_brand_con div").mouseout(function() {
          move();
    })
  function movechange(){
    if (nw_this>totl) {
      nw_this=1;
    };
    $('.f_brand_con div').hide();
      $('.f_brand_tlt a').removeClass('on');
      $('.f_brand_tlt_0'+nw_this).addClass('on');
    $('.f_brand_cont_0'+nw_this).show();
      nw_this++;
  }
  function stop(){
    clearInterval(setin);
    }
   function move(){
     setin=setInterval(movechange,time);
    }
/*选取材质*/
    $('.quality_rng').click(function() {
      $('.quality_rng').removeClass('quality_rng_hver');
      $(this).removeClass('quality_rng').addClass('quality_rng_hver').siblings('a').removeClass('quality_rng_hver').addClass('quality_rng');
    });

//详情页更多现货钻重排序
  $('#u_weig_sort').click(function() {
      if ($(this).hasClass('on')==true) {
        $(this).removeClass('on');
      }else{
        $(this).addClass('on');
      }
  });
//详情页更多现货手寸选择
  $('#u_mselsize').mouseenter(function() {
      $(this).children('dd').show();
  });
  $('#u_mselsize').mouseleave(function() {
      $(this).children('dd').hide();
  });
  $('#u_mselsize dd em').click(function() {
    var txt=$(this).text();
    $(this).parent().siblings('dt').children('i').text(txt);
  });
});
   
  /*显示如何测量手寸*/
    function showHowToMeasure(){

      $('#divlock').show();
      $('#modalwindow').show();
    }
    /*显示如何测量手环*/
    function showHowToMeasuresh(){
      $('#divlock').show();
      $('#modalwindowsz').show();
    }
    function hideHowToMeasure(){
      $('#divlock').hide();
      $('#modalwindow,#modalwindowsz').hide();
    }
          