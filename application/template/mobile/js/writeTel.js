$(function () {
    var settime = null;
    var renderData = {
        activityId: getUrlProperty().activityId,
        unionId: getUrlProperty().unionId,
        code: getUrlProperty().code, // 防篡改码
        spreadUnionId: getUrlProperty().spreadUnionId,
        checkLogin: getUrlProperty().checkLogin,
        telephone: getUrlProperty().telephone,
        spreadNickName: getUrlProperty().spreadNickName,
        nickName: getUrlProperty().nickName,
        spreadPhoneUrl: getUrlProperty().spreadPhoneUrl,
        userPhoneUrl: getUrlProperty().userPhoneUrl,
        shareCode: getUrlProperty().shareCode
    };
    var bagDetail = {
        init: function () {
            this.getInfo();
            this.robBtn();
            this.WX();
        },
        getInfo: function () {

            //var supportsOrientationChange = "onorientationchange" in window,
            //    orientationEvent = supportsOrientationChange ? "orientationchange" : "resize";
            //
            //window.addEventListener(orientationEvent, function() {
            //    if(window.orientation=='90'||window.orientation=='-90'){
            //        alert('111')
            //    }else{
            //        alert('222')
            //    }
            //}, false);
            var height=document.body.clientHeight;
            $('#shade').click(function(){
                $('#shadeTel,#shade').hide()
            })
            $('#load').css('left',(screen.width-40)/2+'px')
            $('#load').css('top',(screen.height-40)/2+'px')
            $('.wrap').css('height',document.body.clientHeight+'px')
            $('.sharePhoto').attr('style','margin-left:'+(document.body.clientWidth-83)/2+'px');
            $('.sharePhoto>img').attr('src', renderData.spreadPhoneUrl);
            $('.shareTest>span').text(renderData.spreadNickName);
            $('.promoCode').text(renderData.spreadUnionId);
            if (renderData.telephone != "") {
                $('.alterTel').show();
                $('.telePhone').text(renderData.telephone)
            }
            $('#alterTelImg').click(function () {
                $('.titieTel').text('修改手机后，领取的红包将会放到你的新账户中');
                $('#shade,#shadeTel').show();
                bagDetail.telBtn();
            })
            $('#phoneNum').focus(function(){
                $('.mod-orient-layer').hide()
                $('#shade,#shadeTel,#shade1').css('position','absolute');


                // $('#shadeTel').css({
                //   'bottom':0,
                //   'top':'auto'
                // });
            });
            $('#phoneNum').blur(function(){
                $('#shade,#shadeTel,#shade1').css('position','fixed');
                settime = setTimeout(function(){
                      $('.mod-orient-layer').attr('style','');
                },1000);
                // $('#shadeTel').css({
                //   'bottom':'auto',
                //   'top':'30%'
                // });
            });
             //$(window).resize(function(){
             //    if( $(this).height() > $(this).width() ){
             //        $('.mod-orient-layer').hide();
             //    }else{
             //        $('.mod-orient-layer').show();
             //    }
             //})
        },
        telBtn: function () {
            $('#shadeBtn').unbind('click').click(function () {
                $('#shadeBtn').css('background','#d8d8d8')
                var phoneNum = $("#phoneNum").val().replace(/\s/g, "");
                var reg = new RegExp('^\\d{11}$');
                //debugger;
                if (reg.test(phoneNum)) {
                    $('#shade1,#load').show();
                    $(".hint").hide();
                    if (renderData.telephone == "") {
                        $("#shadeBtn").attr('disabled', 'disabled');
                        $.ajax({
                            url: ' http://mportal.xiu.com/wechatacty/supportSendReward?',
                            type: 'get',
                            dataType: 'jsonp',
                            data: {
                                activityId: renderData.activityId,
                                unionId: renderData.unionId,
                                code: renderData.code,
                                checkLogin: false,
                                spreadUnionId: renderData.spreadUnionId,
                                nickName: encodeURI(renderData.nickName),
                                supportPhoneUrl: renderData.userPhoneUrl,
                                telephone: phoneNum
                            },
                            jsonp: 'jsoncallback',
                            async: true,
                            success: function (data) {
                                $('#shade1,#load').hide();
                                $('#shadeBtn').css('background','#fafafa')
                                if (data.result) {
                                    $("#robBtn").removeAttr('disabled');
                                    $("#shadeBtn").removeAttr('disabled');
                                    $('#robBtn').attr("style", "bottom:6%");
                                    location.href = "http://m.xiu.com/yingxiao/cps/getBag.html?" +
                                    "activityId=" + renderData.activityId + "&code=" + renderData.code +
                                    "&checkLogin=" + false + "&unionId=" + renderData.unionId + "&spreadUnionId=" + renderData.spreadUnionId +
                                    "&nickName=" + renderData.nickName + "&userPhoneUrl=" + renderData.userPhoneUrl;
                                }else{
                                    if(data.errorCode=="220"){
                                        $("#phoneNum").val('');
                                        $("#robBtn").removeAttr('disabled');
                                        $("#shadeBtn").removeAttr('disabled');
                                        $('.errorTxt').text('不能领取自己发出去的优惠码，赶紧分享给朋友吧')
                                        $('#shade,#error').show();
                                        $('#shadeTel').hide();
                                        $('#errorBtn').click(function(){
                                            console.log(2)
                                            $('#shade,#error').hide();
                                        })
                                    }else if(data.errorCode=="228"){
                                        $("#robBtn").removeAttr('disabled');
                                        $("#shadeBtn").removeAttr('disabled');
                                        $('.errorTxt').text('该手机号已领取！')
                                        $('#shade,#error').show();
                                        $('#shadeTel').hide();
                                        $('#errorBtn').click(function(){
                                            $('#shade,#error').hide();
                                        })
                                    }else{
                                        $('.hint').text(data.errorMsg);
                                        $('#hint').show();
                                    }
                                }
                            },
                            error: function () {
                                $('#shadeBtn').css('background','#fafafa')
                                $('#shade1,#load').hide();
                                $("#shadeBtn").removeAttr('disabled');
                            }
                        });
                    } else {
                        $('#shadeBtn').css('background','#fafafa')
                        $('.telePhone').text(phoneNum);
                        $('#shade,#shadeTel').hide();
                        $('#shade1,#load').hide();
                    }
                } else {
                    $('#shadeBtn').css('background','#fafafa')
                    $(".hint").show();
                    $(".hint").text('请输入正确的手机号码');
                }
                ;
            })
        },
        robBtn: function () {
            $('#robBtn').unbind('click').click(function () {
                $('#robBtn').attr('disabled', 'disabled');
                $('#shade1,#load').show();
                $('#robBtn>img').attr('src', '../static/css/cps/images/btn2.png');
                if (renderData.telephone != "") {
                    $.ajax({
                        url: ' http://mportal.xiu.com/wechatacty/supportSendReward?',
                        type: 'get',
                        dataType: 'jsonp',
                        data: {
                            activityId: renderData.activityId,
                            unionId: renderData.unionId,
                            code: renderData.code,
                            checkLogin: false,
                            spreadUnionId: renderData.spreadUnionId,
                            nickName: encodeURI(renderData.nickName),
                            supportPhoneUrl: renderData.userPhoneUrl,
                            telephone: $('.telePhone').text()
                        },
                        jsonp: 'jsoncallback',
                        async: true,
                        success: function (data) {
                            $('#robBtn>img').attr('src', '../static/css/cps/images/btn.png');
                            $('#shade1,#load').hide();
                            if (data.result) {
                                $("#robBtn").removeAttr('disabled');
                                location.href = "http://m.xiu.com/yingxiao/cps/getBag.html?" +
                                "activityId=" + renderData.activityId + "&code=" + renderData.code +
                                "&checkLogin=" + false + "&unionId=" + renderData.unionId + "&spreadUnionId=" + renderData.spreadUnionId +
                                "&nickName=" + renderData.nickName + "&userPhoneUrl=" + renderData.userPhoneUrl;
                            }else{
                                if(data.errorCode=="220"){
                                    $("#robBtn").removeAttr('disabled');
                                    $('.errorTxt').text('不能领取自己发出去的优惠码，赶紧分享给朋友吧')
                                    $('#shade,#error').show();
                                    $('#errorBtn').click(function(){
                                        $('#shade,#error').hide();
                                    })
                                }else if(data.errorCode=="228"){
                                    $("#robBtn").removeAttr('disabled');
                                    $('.errorTxt').text('该手机号已领取！')
                                    $('#shade,#error').show();
                                    $('#errorBtn').click(function(){
                                        $('#shade,#error').hide();
                                    })
                                }else{
                                    $('.hint').text(data.errorMsg);
                                    $('#hint').show();
                                }
                            }
                        },
                        error: function () {
                            $('#shade1,#load').hide();
                            $('#robBtn>img').attr('src', '../static/css/cps/images/btn.png');
                            $("#robBtn").removeAttr('disabled');
                        }
                    });
                } else {
                    $('#robBtn>img').attr('src', '../static/css/cps/images/btn.png');
                    $('.titieTel').text('领取的礼包将会放到你的手机账户中');
                    $('#shade,#shadeTel').show();
                    bagDetail.telBtn();
                }
            });
        },
        WX:function(){
            $.ajax({
                url: 'http://weixin.xiu.com/weixinsign/sign?url=' + encodeURIComponent(location.href),
                type: 'get',
                dataType: 'jsonp',
                jsonp: 'jsoncallback',
                success: function (data) {
                    if (data.code == 200) {
                        wx.config({
                            debug: false,
                            appId: data.appId,
                            timestamp: data.timestamp,
                            nonceStr: data.nonceStr,
                            signature: data.signature,
                            jsApiList: [
                                'checkJsApi',
                                'onMenuShareTimeline',
                                'onMenuShareAppMessage',
                                'hideMenuItems'
                            ]
                        });
                    } else {
                        alert(data.errMsg);
                    };
                }
            });

            var shareObj = {
                title: '快拿这个优惠码领取走秀网的大礼包！',
                desc: '国际品牌，全球同价，走秀网大礼包等着你！',
                link: 'http://m.xiu.com/yingxiao/cps/index.html?activityId='+renderData.activityId+'&spreadUnionId='+
                renderData.spreadUnionId+'&code='+renderData.code,
                imgUrl: 'http://m.xiu.com/yingxiao/static/css/cps/images/share_icon.jpg',
                success: function (res) {
                }
            };

            /** ?????????
             ========================================================================================*/

            wx.ready(function () {
                wx.hideMenuItems({
                    menuList: ["menuItem:copyUrl"] // ??????????????????????????????????????????menu??????3
                });
                wx.onMenuShareAppMessage(shareObj);

                wx.onMenuShareTimeline(shareObj);

                wx.onMenuShareQQ(shareObj);
            });
        }
    };
    bagDetail.init();
});
