//修改密码
function editpayPwd(type){
	if(type==1){
		var orpayPwd = $('#orpayPwd').val();
	    if(orpayPwd==''){
	    	WST.msg('原密码不能为空','info');
		    $('#orpayPwd').focus();
	        return false;
	    }
		if(orpayPwd.length != 6){
	    	WST.msg('请输入6位数字密码','info');
	    	$('#orpayPwd').focus();
	        return false;
	    }
	}
	var payPwd = $('#payPwd').val();
	var copayPwd = $('#copayPwd').val();
	if(payPwd==''){
    	WST.msg('新密码不能为空','info');
    	$('#payPwd').focus();
        return false;
    }
	if(copayPwd==''){
    	WST.msg('确认密码不能为空','info');
    	$('#copayPwd').focus();
        return false;
    }
	if(copayPwd.length != 6 || payPwd.length !=6){
    	WST.msg('请输入6位数字密码','info');
    	$('#copayPwd').focus();
        return false;
    }
	if(copayPwd!=payPwd){
    	WST.msg('确认密码不一致','info');
    	$('#copayPwd').focus();
        return false;
    }
    var param = {};
    param.type = type;
    param.orpayPwd = orpayPwd;
    param.payPwd = payPwd;
    param.copayPwd = copayPwd;
	$('#modifyPwd').html('修改中···').addClass("active").attr('disabled', 'disabled');
    $.post(ThinkPHP.U('WeChat/Users/editpayPwd'), param, function(data){
        var json = WST.toJson(data);
        if( json.status == 1 ){
        	WST.msg(json.msg,'success');
        	$('#modifyPwd').html('确认');
            setTimeout(function(){
                location.href = ThinkPHP.U('WeChat/Users/toSecurity');
            },2000);
        }else if(json.status == -2){
        	WST.msg('原密码不正确','warn');
        	$('#modifyPwd').removeAttr('disabled').removeClass("active").html('确认');
        }else{
        	WST.msg('操作失败，请重试','warn');
        	$('#modifyPwd').removeAttr('disabled').removeClass("active").html('确认');
        }
        data = json = null;
    });
}
var time = 0;
var isSend = false;
//发送短信
function obtainCode(type){
	if(type==0){
		var userPhone = $('#userPhone').val();
	    if(userPhone==''){
	    	WST.msg('手机号码不能为空','info');
		    $('#userPhone').focus();
	        return false;
	    }
	}
	if(WST.SMS_VERFY==1){
		getVerify('dialog-verifyImg');
		WST.dialogCode('sendCode('+type+')');
	}else{
		sendCode(type);
	}
}
function sendCode(type){
	var userPhone = $('#userPhone').val();
    var param = {};
	param.userPhone = userPhone;
	if(WST.SMS_VERFY==1){
		var smsVerfy = $('#smsVerfy').val();
	    if(smsVerfy==''){
	    	WST.msg('验证码不能为空','info');
	        return false;
	    }
	    param.smsVerfy = smsVerfy;
	}
	if(isSend)return;
	isSend = true;
    $.post(ThinkPHP.U('WeChat/Users/'+((type==0)?"sendCodeTie":"sendCodeEdit")), param, function(data){
        var json = WST.toJson(data);
        if( json.status == 1 ){
        	WST.msg(json.msg,'success');
			time = 120;
			$('#obtain').attr('disabled', 'disabled').html('120秒获取');
			var task = setInterval(function(){
				time--;
				$('#obtain').html(''+time+"秒获取");
				if(time==0){
					isSend = false;
					clearInterval(task);
					$('#obtain').removeAttr('disabled').html("重新发送");
				}
			},1000);
        }else{
        	WST.msg(json.msg,'warn');
        	isSend = false;
        }
    	WST.dialogHide(2);
        data = json = null;
    });
}
function obcodeBack(){
	if(WST.SMS_VERFY==1){
		getVerify('dialog-verifyImg');
		WST.dialogCode('secodeBack()');
	}else{
		secodeBack();
	}
}
function secodeBack(){
    var param = {};
	if(WST.SMS_VERFY==1){
		var smsVerfy = $('#smsVerfy').val();
	    if(smsVerfy==''){
	    	WST.msg('验证码不能为空','info');
	        return false;
	    }
	    param.smsVerfy = smsVerfy;
	}
	if(isSend)return;
	isSend = true;
    $.post(ThinkPHP.U('WeChat/Users/backpPwdEdit'), param, function(data){
        var json = WST.toJson(data);
        if( json.status == 1 ){
        	WST.msg(json.msg,'success');
			time = 120;
			$('#obtain').attr('disabled', 'disabled').html('120秒获取');
			var task = setInterval(function(){
				time--;
				$('#obtain').html(''+time+"秒获取");
				if(time==0){
					isSend = false;
					clearInterval(task);
					$('#obtain').removeAttr('disabled').html("重新发送");
				}
			},1000);
        }else{
        	WST.msg(json.msg,'warn');
        	isSend = false;
        }
    	WST.dialogHide(2);
        data = json = null;
    });
}
//修改手机号码
function editPhone(type){
	if(type==0){
		var userPhone = $('#userPhone').val();
	    if(userPhone==''){
	    	WST.msg('手机号码不能为空','info');
		    $('#userPhone').focus();
	        return false;
	    }
	}
	var phoneCode = $('#phoneCode').val();
    if(phoneCode==''){
    	WST.msg('请输入短信验证码','info');
    	$('#phoneCode').focus();
        return false;
    }
    var param = {};
    param.type = type;
    param.phoneCode = phoneCode;
	$('#modifyPhone').addClass("active").attr('disabled', 'disabled');
    $.post(ThinkPHP.U('WeChat/Users/editPhone'), param, function(data){
        var json = WST.toJson(data);
        if( json.status == 1 ){
        	WST.msg(json.msg,'success');
            setTimeout(function(){
                location.href = ThinkPHP.U('WeChat/Users/toSecurity');
            },2000);
        }else{
        	WST.msg(json.msg,'warn');
        	$('#modifyPhone').removeAttr('disabled').removeClass("active");
        }
        data = json = null;
    });
}
//找回支付密码
function backpPwd(){
	var phoneCode = $('#phoneCode').val();
    if(phoneCode==''){
    	WST.msg('请输入短信验证码','info');
    	$('#phoneCode').focus();
        return false;
    }
    var param = {};
    param.phoneCode = phoneCode;
	$('#modifyPhone').addClass("active").attr('disabled', 'disabled');
    $.post(ThinkPHP.U('WeChat/Users/backpPwd'), param, function(data){
        var json = WST.toJson(data);
        if( json.status == 1 ){
            setTimeout(function(){
                location.href = ThinkPHP.U('WeChat/Users/tobackpPwdt');
            },1000);
        }else{
        	WST.msg(json.msg,'warn');
        	$('#modifyPhone').removeAttr('disabled').removeClass("active");
        }
        data = json = null;
    });
}
$(document).ready(function(){
    initFooter('users');
});