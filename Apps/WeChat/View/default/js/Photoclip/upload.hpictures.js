 jQuery.noConflict();
//关闭图片上传区
 function closeUploadArea(){
	jQuery('#upload_close').hide();
	jQuery('#upload_button').hide();
	jQuery('#upload_modal').hide();
	jQuery('.return_users').show();
	jQuery('.useri_info').show();
	jQuery('#useri_info').show();
	jQuery('#footer').show();
	
     //清空图片上传区的内容
     $('#clipArea').find('img').remove();
     $('#file').val('');
     $('#view').css('background-image','');
     $('#imgData').val('');
 }
 jQuery('#uploadImg').on('change', function() {
		jQuery('.return_users').hide();
		jQuery('.useri_info').hide();
		jQuery('#useri_info').hide();
		jQuery('#footer').hide();
		jQuery('#upload_close').show();
		jQuery('#upload_button').show();
		jQuery('#upload_modal').show();
});
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
        jQuery.post(Think.U('WeChat/Users/uploadImage'), {'imgData': imgData,'saveModulePath':'users'}, function(data){
            var json = WST.toJson(data);
            if(json.status==1){
            	jQuery.post(ThinkPHP.U('WeChat/Users/editUserInfo'), {userPhoto:json.filepath}, function(data){
                if(json.status==1){
                	WST.msg('修改头像成功','success');
                  jQuery('#imgurl').attr('src', ThinkPHP.ROOT +'/'+json.filepath);
                }else{
                	WST.msg('修改头像失败，请重试','warn');
                  return false;
                }
                closeUploadArea();
              });
            }else{
            	WST.msg(json.msg,'info');
            }
            $('#Load').hide();
        });
    }
});