 jQuery.noConflict();
//关闭图片上传区
 function closeUploadArea(){
	jQuery('#upload_close').hide();
	jQuery('#upload_button').hide();
	jQuery('#upload_modal').hide();
	jQuery('.return_order').show();
	jQuery('.edit_info').show();
	jQuery('#edit_info').show();
	jQuery('#footer').show();
     //清空图片上传区的内容
     $('#clipArea').find('img').remove();
     $('#file').val('');
     $('#view').css('background-image','');
     $('#imgData').val('');
 }
 jQuery('#uploadImg').on('change', function() {
		jQuery('.return_order').hide();
		jQuery('.edit_info').hide();
		jQuery('#edit_info').hide();
		jQuery('#footer').hide();
		jQuery('#upload_close').show();
		jQuery('#upload_button').show();
		jQuery('#upload_modal').show();
});
//删除上传的附件
 function delImg(obj){
     $(obj).parent('li.edit_charts').remove();
     isDisabledButton();
 }

 //检查上传的附件的数量，超过5张就禁用上传按钮
 function isDisabledButton(){
     if( $('.edit_charts').length >= 5 ){
         $('#addImg').attr('disabled', 'disabled');
         $('#uploadImg').attr('disabled', 'disabled');
     }else{
         $('#addImg').removeAttr('disabled');
         $('#uploadImg').removeAttr('disabled');
     }
 }
//头像上传
jQuery("#clipArea").photoClip({
    width: 350,
    height: 350,
    file: "#uploadImg",
    view: "#view",
    ok: "#upload_button",
    loadStart: function() {
    	$('#Load').show();
    },
    loadComplete: function() {
    	$('#Load').hide();
    },
    clipFinish: function(dataURL) {
    	jQuery('#imgData').val(dataURL);
        var imgData = $('#imgData').val();
        if(!imgData || imgData==''){
        	WST.msg('请先选择图片','info');
            return false;
        }
        $('#Load').show();
        $.post(Think.U('WeChat/Users/uploadImage'), {'imgData': imgData,'saveModulePath':'complains'}, function(data){
            var json = WST.toJson(data);
            if(json.status==1){
                var str = '<li class="edit_charts">';
                str += '<span class="ui-icon-delete" onclick="javascript:delImg(this);"></span>';
                str += '<img src="'+ ThinkPHP.ROOT +'/'+json.filepath+'">';
                str += '<input type="hidden" class="imgSrc" value="'+json.filepath+'">';
                str += '</li>';
                $('#edit_chart').append(str);
                isDisabledButton();
            }else{
            	WST.msg('上传失败，请重试','warn');
            }
            closeUploadArea();
            $('#Load').hide();
        });
    }
});