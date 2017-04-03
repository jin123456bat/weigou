var Login = function () {

	var handleLogin = function() {
		$('.login-form').validate({
	            errorElement: 'span', //default input error message container
	            errorClass: 'help-block', // default input error message class
	            focusInvalid: false, // do not focus the last invalid input
	            rules: {
	                username: {
	                    required: true
	                },
	                password: {
	                    required: true
	                },
	                remember: {
	                    required: false
	                }
	            },

	            messages: {
	                username: {
	                    required: "请输入用户名"
	                },
	                password: {
	                    required: "请输入密码"
	                }
	            },

	            invalidHandler: function (event, validator) { //display error alert on form submit   
	                $('.alert-danger', $('.login-form')).show();
	            },

	            highlight: function (element) { // hightlight error inputs
	                $(element)
	                    .closest('.form-group').addClass('has-error'); // set error class to the control group
	            },

	            success: function (label) {
	                label.closest('.form-group').removeClass('has-error');
	                label.remove();
	            },

	            errorPlacement: function (error, element) {
	                error.insertAfter(element.closest('.input-icon'));
	            },

	            submitHandler: function (form) {
	                login();
					return false;
	            }
	        });
			
			var login = function(){
				var name = $.trim($('input[name=username]').val());
				var password = $.trim($('input[name=password]').val());
				$.post('./index.php?m=ajax&c=source&a=login',{name:name,password:password},function(response){
					if(response.code==1)
					{
						window.location = './index.php?c=source&a=index';
					}
					else
					{
						$('.alert').show().find('span').html(response.result);
					}
				});
			}

	        $('.login-form input').keypress(function (e) {
	            if (e.which == 13) {
	                if ($('.login-form').validate().form()) {
	                    $('.login-form').submit();
	                }
	                return false;
	            }
	        });
	}

    return {
        //main function to initiate the module
        init: function () {
        	
            handleLogin();
            
            // init background slide images
		    $.backstretch([
		        "./application/template/assets/pages/media/bg/1.jpg",
		        "./application/template/assets/pages/media/bg/2.jpg",
		        "./application/template/assets/pages/media/bg/3.jpg",
		        "./application/template/assets/pages/media/bg/4.jpg"
		        ], {
		          fade: 1000,
		          duration: 8000
		    	}
        	);
        }
    };

}();

jQuery(document).ready(function() {
    Login.init();
});