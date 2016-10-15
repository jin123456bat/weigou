var EcommerceProductsEdit = function () {
	
	var drawPhoto = function(id,path,name,sort,position){
		var tpl = '<tr>'
				+'	<td>'
				+'		<a href="'+path+'" class="fancybox-button" data-rel="fancybox-button">'
				+'			<img class="img-responsive" src="'+path+'" alt=""> </a><input type="hidden" name=image_id value="'+id+'">'
				+'	</td>'
				+'	<td>'
				+'		<input type="text" class="form-control" name="image_name" value="'+name+'"> </td>'
				+'	<td>'
				+'		<input type="text" class="form-control" name="image_sort" value="1"> </td>'
				+'	<td>'
				+'		<label>'
				+'			<input type="radio" class="image_position" name="image_position['+id+']" value="1" '+((position==1)?'checked=checked':'')+'> </label>'
				+'	</td>'
				+'	<td>'
				+'		<label>'
				+'			<input type="radio" class="image_position" name="image_position['+id+']" value="2" '+((position==2)?'checked=checked':'')+'> </label>'
				+'	</td>'
				+'	<td>'
				+'		<a href="javascript:;" class="btn btn-default btn-sm remove">'
				+'			<i class="fa fa-times"></i> 删除 </a>'
				+'	</td>'
				+'</tr>';
		tpl = $(tpl);
		tpl.find('.remove').bind('click',function(){
			$(this).parents('tr').remove();
		});
		tpl.find('.fancybox-button').fancybox();
		$('#photoList tbody').append(tpl);
	}
	
    var handleImages = function() {

        // see http://www.plupload.com/
        var uploader = new plupload.Uploader({
            runtimes : 'html5,flash,silverlight,html4',
             
            browse_button : document.getElementById('tab_images_uploader_pickfiles'), // you can pass in id...
            container: document.getElementById('tab_images_uploader_container'), // ... or DOM Element itself
             
            url : $('#tab_images_uploader_uploadfiles').data('url'),
             
            filters : {
                max_file_size : '10mb',
                mime_types: [
                    {title : "图像文件", extensions : "jpg,gif,png,bmp"},
                ]
            },
         
            // Flash settings
            flash_swf_url : './application/template/assets/plugins/plupload/js/Moxie.swf',
     
            // Silverlight settings
            silverlight_xap_url : './application/template/assets/plugins/plupload/js/Moxie.xap',             
         
            init: {
                PostInit: function() {
                    $('#tab_images_uploader_filelist').html("");
         
                    $('#tab_images_uploader_uploadfiles').click(function() {
                        uploader.start();
                        return false;
                    });

                    $('#tab_images_uploader_filelist').on('click', '.added-files .remove', function(){
                        uploader.removeFile($(this).parent('.added-files').attr("id"));    
                        $(this).parent('.added-files').remove();                     
                    });
                },
         
                FilesAdded: function(up, files) {
                    plupload.each(files, function(file) {
                        $('#tab_images_uploader_filelist').append('<div class="alert alert-warning added-files" id="uploaded_file_' + file.id + '">' + file.name + '(' + plupload.formatSize(file.size) + ') <span class="status label label-info"></span>&nbsp;<a href="javascript:;" style="margin-top:-5px" class="remove pull-right btn btn-sm red"><i class="fa fa-times"></i> 删除</a></div>');
                    });
                },
         
                UploadProgress: function(up, file) {
                    $('#uploaded_file_' + file.id + ' > .status').html(file.percent + '%');
                },

                FileUploaded: function(up, file, response) {
                   	response = JSON.parse(response.response);
					
                    if (response.code && response.code==1) {
                        var id = response.body.id;

                        $('#uploaded_file_' + file.id + ' > .status').removeClass("label-info").addClass("label-success").html('<i class="fa fa-check"></i> 成功');
						drawPhoto(response.body.id,response.body.path,response.body.name,1,2);
                    } else {
                        $('#uploaded_file_' + file.id + ' > .status').removeClass("label-info").addClass("label-danger").html('<i class="fa fa-warning"></i> 失败'); // set failed upload
                        App.alert({type: 'danger', message: '文件上传失败.', closeInSeconds: 5, icon: 'warning'});
                    }
                },
         
                Error: function(up, err) {
                    App.alert({type: 'danger', message: err.result, closeInSeconds: 5, icon: 'warning'});
                }
            }
        });

        uploader.init();

    }

    var initComponents = function () {
        //init datepickers
        $('.date-picker').datepicker({
            rtl: App.isRTL(),
            autoclose: true
        });

        //init datetimepickers
        $(".datetime-picker").datetimepicker({
            isRTL: App.isRTL(),
            autoclose: true,
            todayBtn: true,
            pickerPosition: (App.isRTL() ? "bottom-right" : "bottom-left"),
            minuteStep: 10
        });
    }

    return {

        //main function to initiate the module
        init: function () {
            initComponents();
            handleImages();
        },
		
			
		drawPhoto:function(id,path,name,sort,position){
			drawPhoto(id,path,name,sort,position);
		},
		
		getPhoto:function(){
			var image = [];
			$.each($('#photoList tbody').find('tr'),function(index,value){
				var temp = {
					id:$(value).find('input[name=image_id]').val(),
					sort:$(value).find('input[name=image_sort]').val(),
					position:$(value).find('input.image_position:checked').val(),
				};
				image.push(temp);
			});
			return image;
		}

    };

}();

jQuery(document).ready(function() {    
   EcommerceProductsEdit.init();
});