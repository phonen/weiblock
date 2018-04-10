//我的提现账户
function tocashManage(n){
	if(n==1){
		WST.dialogPwd('tocashAccount()');
	}else{
		$("#wst-dialog4").dialog("show");//提示对话框
		return false;
	}
}
function tocashAccount(){
	var payPwd = $('#wst-pwd-input').val();
    if(payPwd==''){
    	WST.msg('请输入支付密码','info');
        return false;
    }
    if(payPwd.length!=6){
    	WST.msg('请输入6位数字密码','info');
        return false;
    }
    WST.dialogHide(3);
    $('#wst-event32').addClass("active").attr('disabled', 'disabled');
    $.post(ThinkPHP.U('WeChat/Users/verifypPwd'), {payPwd:payPwd}, function(data){
        var json = WST.toJson(data);
        if( json.status == 1 ){
            location.href = ThinkPHP.U('WeChat/CashConfigs/index');
        }else{
        	WST.msg(json.msg,'warn');
        	$('#wst-event32').removeAttr('disabled').removeClass("active");
        }
        data = json = null;
    });
}
//返回提现账户
function returnAccount(){
	WST.showHide('','.accountadd,#accountadd');
	WST.showHide(1,'.account,#account,#accounts');
}
var cid;
//增加/修改提现账户
function toEditaccount(id){
	WST.showHide('','.account,#account,#accounts');
	WST.showHide(1,'.accountadd,#accountadd');
    WST.emptyData('','#accTargetId,#accUser,#accNo,#areaId');
    $("#areaId2").empty().html('<option value="">请选择</option>');
	cid = id;
	if(id>0){
        $.post(ThinkPHP.U('WeChat/CashConfigs/getInfo'), {id:id}, function(data){
            var json = WST.toJson(data);
            $('#accTargetId').val(json.accTargetId);
            $('#areaId').val(json.areaId1);
            getCitysby(json.areaId1,json.areaId2);
            $('#accUser').val(json.accUser);
            $('#accNo').val(json.accNo);
            json= null;
        });
	}
}
function editAccount(){
	var accTargetId = $('#accTargetId').val();
	var areaId2 = $('#areaId2').val();
	var accUser = $('#accUser').val();
	var accNo = $('#accNo').val();
    if(accTargetId==''){
    	WST.msg('请选择账户类型','info');
        return false;
    }
    if(areaId2==''){
    	WST.msg('请选择完整地址','info');
        return false;
    }
    if(accUser==''){
    	WST.msg('请填写持卡人','info');
    	$('#accUser').focus();
        return false;
    }
    if(accNo==''){
    	WST.msg('请填写卡号','info');
    	$('#accNo').focus();
        return false;
    }
    var param = {};
    param.id = cid;
    param.accTargetId = accTargetId;
    param.areaId2 = areaId2;
    param.accUser = accUser;
    param.accNo = accNo;
    $('#modifyAcc').html('修改中···').addClass("active").attr('disabled', 'disabled');
    $.post(ThinkPHP.U('WeChat/CashConfigs/edit'), param, function(data){
        var json = WST.toJson(data);
        if(json.status == '1'){
            WST.msg(json.msg,'success');
            $('#modifyAcc').html('确认');
    	    setTimeout(function(){
    	    	location.href = ThinkPHP.U('WeChat/CashConfigs/index');
    	    },1000);
        }else{
        	WST.msg(json.msg,'warn');
        	$('#modifyAcc').removeAttr('disabled').removeClass("active").html('确认');
            return false;
        }
    });
}
//删除提现账户
function delPrompt(id,n){
	if(n==1){
		WST.dialogPwd('toaccAccount('+id+')');
	}else{
		$("#wst-dialog4").dialog("show");//提示对话框
		return false;
	}
}
function toaccAccount(id){
	var payPwd = $('#wst-pwd-input').val();
    if(payPwd==''){
    	WST.msg('请输入支付密码','info');
        return false;
    }
    if(payPwd.length!=6){
    	WST.msg('请输入6位数字密码','info');
        return false;
    }
    $('#wst-event32').addClass("active").attr('disabled', 'disabled');
    $.post(ThinkPHP.U('WeChat/Users/verifypPwd'), {payPwd:payPwd}, function(data){
        var json = WST.toJson(data);
        if( json.status == 1 ){
        	setTimeout(function(){
        		delAccount(id);
        	 },500);
        }else{
        	WST.msg(json.msg,'warn');
        }
        WST.dialogHide(3);
    	$('#wst-event32').removeAttr('disabled').removeClass("active");
        data = json = null;
    });
}
function delAccount(id){
    $.post(ThinkPHP.U('WeChat/CashConfigs/del'), {id:id}, function(data){
        var json = WST.toJson(data);
        if(json.status == '1'){
            WST.msg(json.msg,'success');
    	    setTimeout(function(){
    	    	$("#acc_"+id).fadeOut(1000);
    	    },500);
        }else{
        	WST.msg(json.msg,'warn');
            return false;
        }
    });
}
//根据省份获取城市列表
function getCitysby(provinceId,areaId2){
	$("#areaId2").empty().html('<option value="">请选择</option>');
    if(provinceId>0){
	    $.post(ThinkPHP.U('WeChat/Areas/getCityListByProvince'), {provinceId:provinceId}, function(data){
	        var json = WST.toJson(data);
	        var str = '';
	        if(json.length>0){
	            for(var i=0; i<json.length; i++){
	                str += '<option value="'+ json[i].areaId +'" ';
	                str += (json[i].areaId==areaId2) ? 'selected' : '';
	                str += '>'+ json[i].areaName +'</option>';
	            }
	        }
	        $('#areaId2').append(str);
	        data = json = null;
	    });
    }
}
//提现页
function toextAccount(){
   location.href = ThinkPHP.U('WeChat/Users/toExtract');
}
//提现
function verExtract(n){
	if(n==1){
		WST.dialogPwd('toverExtract()');
	}else{
		$("#wst-dialog4").dialog("show");//提示对话框
		return false;
	}
}
function toverExtract(id){
	var payPwd = $('#wst-pwd-input').val();
    if(payPwd==''){
    	WST.msg('请输入支付密码','info');
        return false;
    }
    if(payPwd.length!=6){
    	WST.msg('请输入6位数字密码','info');
        return false;
    }
    $('#wst-event32').addClass("active").attr('disabled', 'disabled');
    $.post(ThinkPHP.U('WeChat/Users/verifypPwd'), {payPwd:payPwd}, function(data){
        var json = WST.toJson(data);
        if( json.status == 1 ){
        	drawsCashByUser();
        }else{
        	WST.msg(json.msg,'warn');
        }
        WST.dialogHide(3);
    	$('#wst-event32').removeAttr('disabled').removeClass("active");
        data = json = null;
    });
}
function drawsCashByUser(){
	var configId = $('#configId').val();
	var drawMoney = parseFloat($('#cashMoney').val());
    if(configId==''){
    	WST.msg('请选择提现账户','info');
        return false;
    }
    if(drawMoney==''){
    	WST.msg('请填写账提现金额','info');
        return false;
    }
    if(drawMoney<WST.CASHSTART_MONEY){
    	WST.msg('最低提现金额'+WST.CASHSTART_MONEY+'元','info');
        return false;
    }
    var param = {};
    param.configId = configId;
    param.drawMoney = drawMoney;
    $(".wst-cap-reflect .button").html('提交中···').attr('disabled', 'disabled');
    $.post(ThinkPHP.U('WeChat/CashDraws/drawsCashByUser'), param, function(data){
        var json = WST.toJson(data);
        if(json.status == '1'){
            WST.msg(json.msg,'success');
            $(".wst-cap-reflect .button").html('确认转出');
    	    setTimeout(function(){
    	    	location.href = ThinkPHP.U('WeChat/Users/toCapital');
    	    },1000);
        }else{
        	WST.msg(json.msg,'warn');
        	$(".wst-cap-reflect .button").removeAttr('disabled').html('确认转出');
            return false;
        }
    });
}
//提现记录列表
function getdrawsList(){
  $('#Load').show();
  loading = true;
  var param = {};
  param.pageSize = 10;
  param.currPage = Number( $('#currPage').val() ) + 1;
  $.post(ThinkPHP.U('WeChat/CashDraws/queryByList'), param, function(data){
      var json = WST.toJson(data);
      var chtml = '';
      var drawsStatus = {'-7':'用户取消','0':'待处理','1':'提现成功'};
      if(json && json.root && json.root.length>0){
          for(var i=0; i<json.root.length; i++){
        	  chtml += '<div class="ui-row-flex ui-whitespace wst-cap_head">';
        	  chtml += '<div class="ui-col ui-col wst-cap_headl"><span class="money">提现金额：¥'+json.root[i].money+'</span></div>';
        	  chtml += '<div class="ui-col ui-col wst-cap_headr"><span>'+json.root[i].createTime+'</span></div>'; 
              chtml += '</div>';
              chtml += '<div class="ui-row-flex ui-whitespace wst-cap_info">';
              var accNo = json.root[i].accNo.substring(json.root[i].accNo.length-4);
              chtml += '<div class="ui-col ui-col-3 wst-cap_headl"><span>【'+json.root[i].bankName+'】***'+accNo+'</span></div>';
              chtml += '<div class="ui-col ui-col-2 wst-cap_infor"><span>'+drawsStatus[json.root[i].cashSatus]+'</span></div>';
              chtml += '</div>';
          }
          $('#currPage').val(json.currPage);
          $('#totalPage').val(json.totalPage);
      }else{
    	  chtml += '<ul class="ui-row-flex wst-flexslp">';
		  chtml += '<li class="ui-col ui-flex ui-flex-pack-center">';
		  chtml += '<p>对不起，没有相关记录。</p>';
		  chtml += '</li>';
		  chtml += '</ul>';
      }
	  $('#info-list').append(chtml);
      loading = false;
      $('#Load').hide();
  });
}
$(document).ready(function(){
    initFooter('users');
});