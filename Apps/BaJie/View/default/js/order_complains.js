//返回投诉订单
function returnComplaintor(){
	var data1 = '.return_complaintor,.complaintor_details,#complaintor_details,.respondent_info,#respondent_info,.arbitration_infotime';
	WST.showHide('',data1);
	WST.showHide(1,'.return_users,.complaintor,#complaintor,#footer');
}
//加载投诉订单详情页
function getComplainDetails(complainId){
	WST.showHide('','.return_users,.complaintor,#complaintor,#footer');
	WST.showHide(1,'.return_complaintor,.complaintor_details,#complaintor_details');
    $.post(ThinkPHP.U('WeChat/orderComplains/getComplainDetails'), {complainId:complainId}, function(data){
        var json = WST.toJson(data);
        //投诉信息
        $('.complaint_info1').html(json.orderNo);
        if(json.complainType==1){
        	$('.complaint_info2').html('承诺的没有做到');
        }
        if(json.complainType==2){
        	$('.complaint_info2').html('未按约定时间发货 ');
        }
        if(json.complainType==3){
        	$('.complaint_info2').html('未按成交价格进行交易');
        }
        if(json.complainType==4){
        	$('.complaint_info2').html('恶意骚扰');
        }
        $('.complaint_info3').html(json.complainContent);
        var chtml = '';
        if(json.complainAnnex){
        	for(var c in json.complainAnnex){
        		chtml += '<img class="wst-complaintor_img" src="'+ ThinkPHP.DEFAULT_IMG +'" data-echo="'+ ThinkPHP.ROOT +'/'+json.complainAnnex[c]+'">';
        	}
        }
        $('.complaint_info4').html(chtml);
        echo.init();//图片懒加载
        $('.complaint_info5').html(json.complainTime);
        //应诉信息
        if(json.needRespond==1){
        	$('.respondent_info').show();
        	$('#respondent_info').show();
        	$('.respondent_info1').html(json.respondContent);
        	var rhtml = '';
            if(json.respondAnnex){
            	for(var r in json.respondAnnex){
            		rhtml += '<img class="wst-complaintor_img" src="'+ ThinkPHP.DEFAULT_IMG +'" data-echo="'+ ThinkPHP.ROOT +'/'+json.respondAnnex[r]+'">';
            	}
            }
            $('.respondent_info2').html(rhtml);
            echo.init();//图片懒加载
        	$('.respondent_info3').html(json.respondTime);
        }
        //仲裁结果
        if(json.complainStatus==0){
        	$('.arbitration_info1').html('等待处理');
        }
        if(json.complainStatus==1){
        	$('.arbitration_info1').html('等待被投诉方回应');
        }
        if(json.complainStatus==2){
        	$('.arbitration_info1').html('等待仲裁');
        }
        if(json.complainStatus==3){
        	$('.arbitration_info1').html('等待仲裁');
        }
        if(json.complainStatus==4){
        	$('.arbitration_info1').html('已仲裁');
        }
        if(json.complainStatus==4){
        	$('.arbitration_infotime').show();
        	$('.arbitration_info2').html(json.finalResultTime);
        }
        json = null;
    });
}
$(document).ready(function(){
    initFooter('users');
    $('#edit_button').click(function(){
      $("#edit_details").fadeIn(500);
  	  $('#edit_button').hide();
  	  $('#edit_button1').show();
    });
    $('#edit_button1').click(function(){
    	$("#edit_details").fadeOut(500);
    	$('#edit_button').show();
    	$('#edit_button1').hide();
    });
    $('#complainSubmit').click(function(){
        var complainType = $('input[name="radio"]:checked').val();
        var complainContent = $('#complainContent').val();
        if(complainType!=1 && complainType!=2 && complainType!=3 &&complainType!=4){
        	WST.msg('请选择投诉类型','info');
            return false;
        }
        if(complainContent == ''){
        	WST.msg('请输入投诉内容','info');
    	    $('#complainContent').focus();
            return false;
        }
        if( $('.edit_charts').length > 5 ){
        	WST.msg('附件数量不能超过5张','info');
            return false;
        }
        var imgContainer = new Array();
        $('.imgSrc').each(function(){
            imgContainer.push( $(this).val() );
        });
        var complainAnnex = imgContainer.join(',');

        var param = {};
        param.orderId = $('#orderId').val();
        param.complainType = complainType;
        param.complainContent = complainContent;
        param.complainAnnex = complainAnnex;
        $.post(Think.U('WeChat/OrderComplains/saveComplain'), param, function(data){
            var json = WST.toJson(data);
            if(json.status==1){
            	WST.msg('您的投诉已提交，请留意信息回复','success');
                $('#complainSubmit').attr('disabled', 'disabled');
                setTimeout(function(){
                	location.href = ThinkPHP.U('WeChat/OrderComplains/index');
                },2000);
            }else{
            	WST.msg(json.msg,'info');
                $('#complainSubmit').removeAttr('disabled');
            }
        });
    });
});