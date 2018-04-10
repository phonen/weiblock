$(document).ready(function(){
    initFooter('cart');
    //积分支付
	paymentMethod();
    $('#scorePayIcon').click(function(){
    	var checked=$("#scorePayIcon").attr("checked");
    	if(checked){
    		$('#scorePay').val(1);
    	}else{
    		$('#scorePay').val(0);
    	}
    	calculateTotalMoney();
    });
    //检测是否有指定地区的收货人地址
	$('#addressPer').empty();
	var isDefault=1;
	var param={};
	param.isDefault=isDefault;
    $.post(ThinkPHP.U('WeChat/UsersAddress/getUserAddressForOrder'),param, function(data){
    	var ahtml = '';
    	if(data){
            var json = WST.toJson(data);
            if(json){
            		ahtml += '<li class="ui-border-t ui-border-t1">';
            		ahtml += '<div class="wst-list-infose1 wst-list-infose_1">';
            		ahtml += '<a href="javascript:void(0);" onclick="javascript:getAddress('+json.addressId+');">';
            		ahtml += '<span>'+json.userName+'&nbsp;&nbsp;'+json.userPhone+'</span>';
            		ahtml += '<span>'+json.areaName+'</span>';
            		ahtml += '<span>'+json.address+'</span>';
            		ahtml += '</a>';
            		ahtml += '</div>';
            		ahtml += '</li>';
                $('#addressId').val(json.addressId);
            }
    	}else{
    		ahtml += '<li class="ui-border-t">';
    		ahtml += '<div class="ui-list-thumb wst-list-thumbse"><span>';
    		ahtml += '<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/user-address.png" class="wst-seimg">';
    		ahtml += '</span></div>';
    		ahtml += '<a href="'+ ThinkPHP.ROOT +'/index.php/WeChat/UsersAddress/index">';
    		ahtml += '<div class="wst-list-infose">';
    		ahtml += '<h5>本地区没有收货地址，新建一个。</h5>';
    		ahtml += '</div>';
    		ahtml += '</a>';
    		ahtml += '</li>';
    	}
        $('#addressPer').append(ahtml);
    });
});
//返回订单
function returnAddress(){
	WST.showHide('','#settlementh_or,.settlementh_or1,#settlementh_in,.settlementh_in1');
	WST.showHide(1,'#settlementh,#settlement,#footer');
}
//加载收货地址
function getAddress(addressId){
    var el=$.loading({
        content:'加载中...',
    })
    setTimeout(function(){
        el.loading("hide");
    },100);
    $('#ui-listse').empty();
    $.post(ThinkPHP.U('WeChat/UsersAddress/getUserAddressForOrder'), {}, function(data){
        var json = WST.toJson(data);
    	WST.showHide('','#settlementh,#settlement,#footer');
    	WST.showHide(1,'#settlementh_or,.settlementh_or1');
    	var shtml = '';
        if(json.length>0){
            for(var i in json){
                shtml += '<ul class="ui-list wst-listse" onclick="javascript:changeAddress(this,'+json[i].addressId+')">';
                shtml += '<li class="ui-border-t">';
                shtml += '<div class="wst-list-infose1">';
                shtml += '<span id="address_a'+json[i].addressId+'">'+json[i].userName+'&nbsp;&nbsp;'+json[i].userPhone+'</span>';
                shtml += '<span id="address_b'+json[i].addressId+'">'+json[i].areaName+'</span>';
                shtml += '<span id="address_c'+json[i].addressId+'">'+json[i].address+'</span>';
                shtml += '</div>';
                shtml += '</li>';
                shtml += '<div class="wst-list-infose2">';
                if(addressId==json[i].addressId){
                	shtml += '<i class="ui-icon-checked-s wst-icon-checked-s_se"></i>';
                }
                shtml += '</div>';
                shtml += '</ul>';
            }
        }else{
        	shtml += '<ul class="ui-row-flex wst-flexslp">';
        	shtml += '<li class="ui-col ui-flex ui-flex-pack-center">';
        	shtml += '<p>对不起,没有地址。</p>';
        	shtml += '</li>';
        	shtml += '</ul>';
        }
        $('#ui-listse').append(shtml);
        data = json = null;
    });
}
//改变收货地址
function changeAddress(obj, addressId){
	$(obj).children('.wst-list-infose2').html('<i class="ui-icon-checked-s wst-icon-checked-s_se"></i>');
	$(obj).siblings().children('.wst-list-infose2').html('');
	var address_a=document.getElementById('address_a'+addressId);
	var address_b=document.getElementById('address_b'+addressId);
	var address_c=document.getElementById('address_c'+addressId);
	$('#addressId').val(addressId);
	var str = '<a href="javascript:void(0);" onclick="javascript:getAddress('+addressId+');"><span>'+address_a.innerHTML+'</span><span>'+address_b.innerHTML+'</span><span>'+address_c.innerHTML+'</span></a>';
	$('.wst-list-infose_1').html(str);
    setTimeout(function(){
    	WST.showHide('','#settlementh_or,.settlementh_or1');
    	WST.showHide(1,'#settlementh,#settlement,#footer');
    },300);
}
//支付、配送、发票
function getOrderInfo(){
    var el=$.loading({
        content:'加载中...',
    })
    setTimeout(function(){
        el.loading("hide");
    },100);
    $('#payType_1').empty();
    $.post(ThinkPHP.U('WeChat/Payments/getPayType'), {}, function(data){
        var json = WST.toJson(data);
        if(json){
            var payType = json;
		    var payTypeSelected = $('#payTypeHidden').val();//支付方式//按照排序号区分支付方式
		    var payName = $('#payType').html();//支付方式名称
		    var isOnline = $('#isOnlineHidden').html();//是否是在線支付
		    var deliveryType = $('#deliveryTypeHidden').val();//是否自提
		    var invoiceTitle = $('#invoiceTitleHidden').val();//发票抬头
		    var orderMark = $('#orderMarkHidden').val();//订单备注
		    var reachTime = $('#reachTimeHidden').val();//送达时间
		    var shopDis = $('#shopDis').val();//分销店铺个数

		    //将之前保存的支付方式放到payType里面便于在编译模板时做比较，以此给选中支付方式加上的wst-shopsbu类名
		    for(var i in payType){
		    	payType[i].index = i;
		    	payType[i].payTypeSelected = payTypeSelected;
		    }
		    
		    WST.showHide('','#settlementh,#settlement,#footer');
		    WST.showHide(1,'#settlementh_in,.settlementh_in1');
		    
	    	//支付方式
	    	var paytypeValue=$('#paytypeValue').val();
	    	var pay = '';
	        if(payType){
	        	if(shopDis>0){
	        		pay += '<button type="button" class="wst-invoiceinr wst-invoiceinr_1 wst-shopsbu" paytypevalue="0" onclick="javascript:changePayType(this,1);">微信支付</button>';
	        	}else{
    	            for(var a in payType){
		            	if(paytypeValue==payType[a].payOrder){
		            		pay += '<button type="button" class="wst-invoiceinr wst-invoiceinr_1 wst-shopsbu" paytypevalue="'+payType[a].payOrder+'" onclick="javascript:changePayType(this,'+payType[a].isOnline+');">'+payType[a].payName+'</button>';
		            	}else{
		            		pay += '<button type="button" class="wst-invoiceinr wst-invoiceinr_1" paytypevalue="'+payType[a].payOrder+'" onclick="javascript:changePayType(this,'+payType[a].isOnline+');">'+payType[a].payName+'</button>';
		            	}
	            	}
	            }
	            $('#payType_1').append(pay);
	        }
	        
	        //是否自提
	        var deliverytypeValue=$('#deliverytypeValue').val();
	        if(deliveryType==0){
	        		var sin = '<button type="button" class="wst-invoiceinr wst-invoiceinr_2 wst-shopsbu" deliverytypeValue="0" onclick="javascript:changeDeliveryType(this);">不自提</button><button type="button" class="wst-invoiceinr wst-invoiceinr_2" deliverytypeValue="1" onclick="javascript:changeDeliveryType(this);">自提</button>';
	        }else{
	        		var sin = '<button type="button" class="wst-invoiceinr wst-invoiceinr_2" deliverytypeValue="0" onclick="javascript:changeDeliveryType(this);">不自提</button><button type="button" class="wst-invoiceinr wst-invoiceinr_2 wst-shopsbu" deliverytypeValue="1" onclick="javascript:changeDeliveryType(this);">自提</button>';
	        }
	    	$('#Since').html(sin);
	        
	    	//发票抬头
	    	if(invoiceTitle){
	        		var ris = '<label>发票抬头：</label><input  class="ui-border-binte" type="text" value="'+invoiceTitle+'">';
	    	    	$('#invoiceTitle').val('1');
	        }else{
	        		var ris = '<label>发票抬头：</label><input  class="ui-border-binte" type="text" value="" disabled>';
	    	    	$('#invoiceTitle').val('0');
	        }
	    	$('#rise').html(ris);
	    	//订单备注
	    	if(orderMark){
	        		var ris = '<label>订单备注：</label><input id="orderMark1" class="ui-border-binte" type="text" value="'+orderMark+'">';
	        }else{
	        		var ris = '<label>订单备注：</label><input id="orderMark1" class="ui-border-binte" type="text" value="">';
	        }
	    	$('#remarks').html(ris);
	    	
		    var reach_date = '';
		    var startDay = 0;
		    var days = 3;
		    var startNum = getTimeOneHourLater();//一小时后的时间
		    if(startNum > 95){//这种情况下送达时间要从第二天算起
		        startDay = 1;
		        days = 4;
		        startNum = 0;
		    }
		    var reachDateHidden = $('#reachDateHidden').val();//上一页保存的送达日期
		    for(var i=startDay; i<days; i++){
		    	var theDate = getRelatedDate(i);
		        reach_date += '<option name="reachDate[]"';
		        reach_date += (reachDateHidden == theDate) ? ' selected' : '';
		        reach_date += '>'+theDate+'</option>';
		    }
		    $('#requireDate').html(reach_date);
		    
			var currDate = getRelatedDate(0);//当前日期
		    if( reachDateHidden != '' && reachDateHidden != currDate ){
				createRequireTime(0);
		    }else{
				createRequireTime(startNum);
		    } 

		    orderInfo = goodslist = template = html = reach_date = null;
        }else{
        	WST.msg('请先选择支付方式','info');
        }
    });
}
//返回一小时后的时间，返回规则见变量times
function getTimeOneHourLater(){
    var d = new Date();   
    var currHour = d.getHours();
    var currMinutes = d.getMinutes();
    return currHour*4+(Math.ceil(currMinutes/15))+4;
}
//获取当前日期之后几天的日期，days为0时表示获取当前日期
function getRelatedDate(days){
    var d = new Date();
    d.setDate(d.getDate()+days);
    var m = d.getMonth()+1;
    return d.getFullYear()+'-'+(m>=10?m:'0'+m)+'-'+(d.getDate()>=10?d.getDate():"0"+d.getDate());
}
//期望送达时间
function createRequireTime(startNum){
	var reach_time = '';
	var reachTimeHidden = $('#reachTimeHidden').val();
    for(var i=startNum; i<=96; i++){  
        if(times[i]){   
            reach_time += '<option name="reachTime[]"';
            reach_time += (reachTimeHidden==times[i]) ? ' selected' : '';
            reach_time += '>'+times[i]+'</option>';
        }       
    }
    $('#requireTime').html(reach_time);
}
//更换期望送达日期
function changeRequireDate(obj){
	var startNum = getTimeOneHourLater();//一小时后的时间
	var currDate = getRelatedDate(0);//当前日期
	if( currDate != $(obj).val() ){
		startNum = 0;
	}
	createRequireTime(startNum);
} 
//更改支付方式
function changePayType(obj, isOnline){
	$(obj).addClass('wst-shopsbu').siblings('.wst-invoiceinr_1').removeClass('wst-shopsbu');
    $('#paytypeValue').val( $(obj).attr('paytypeValue') );
    $('#payName').val( $(obj).html() );
    $('#isOnline').val( isOnline );//是否是在线支付
}
//更改配送方式
function changeDeliveryType(obj){
	$(obj).addClass('wst-shopsbu').siblings('.wst-invoiceinr_2').removeClass('wst-shopsbu');
    $('#deliverytypeValue').val( $(obj).attr('deliverytypeValue') );
}
//保存支付配送发票等信息
function saveOrderInfo(){
	paymentMethod();
    $('#orderMarkHidden').val( $('#orderMark1').val() );
    setTimeout(function(){
    	WST.showHide('','#settlementh_in,.settlementh_in1');
    	WST.showHide(1,'#settlementh,#settlement,#footer,.invoice_prompt');
    },300);
}
function paymentMethod(){
    var paytypeValue = $('#paytypeValue').val();
    $('#isOnlineHidden').val( $('#isOnline').val());//是否是在线支付
    $('#payType').html( $('#payName').val() );
    var deliverytypeValue = $('#deliverytypeValue').val();
    if(deliverytypeValue==1){
        $('#deliveryType').html('自提');
        $('#deliveryTotalMoney').html('0.00');
    }else{
        $('#deliveryType').html('不自提');
        $('#deliveryTotalMoney').html( Number($('#totalDeliveryMoneyHidden').val()).toFixed(2));
    }
    calculateTotalMoney();//计算实时付款金额
    $('#payTypeHidden').val(paytypeValue);
    $('#deliveryTypeHidden').val( deliverytypeValue );
    var invoiceTitle = $('#invoiceTitle').val();
    if(invoiceTitle==0){
        $('#isInvoice').html('不开发票');
        $('#isInvoiceHidden').val(0);
    }else{
        $('#isInvoice').html('开发票');
        $('#isInvoiceHidden').val(1);
    }
    var rdate = $("#requireDate").val();
    var rtime = $("#requireTime").val();
    $('#reachDateHidden').val( rdate );
    $('#reachTimeHidden').val( rtime );
}
//送达时间段
var times = [
	"00:00", "00:15", "00:30", "00:45",
	"01:00", "01:15", "01:30", "01:45",
	"02:00", "02:15", "02:30", "02:45",
	"03:00", "03:15", "03:30", "03:45",
	"04:00", "04:15", "04:30", "04:45",
	"05:00", "05:15", "05:30", "05:45",
	"06:00", "06:15", "06:30", "06:45",
	"07:00", "07:15", "07:30", "07:45",
	"08:00", "08:15", "08:30", "08:45",
	"09:00", "09:15", "09:30", "09:45",
	"10:00", "10:15", "10:30", "10:45",
	"11:00", "11:15", "11:30", "11:45",
	"12:00", "12:15", "12:30", "12:45",
	"13:00", "13:15", "13:30", "13:45",
	"14:00", "14:15", "14:30", "14:45",
	"15:00", "15:15", "15:30", "15:45",
	"16:00", "16:15", "16:30", "16:45",
	"17:00", "17:15", "17:30", "17:45",
	"18:00", "18:15", "18:30", "18:45",
	"19:00", "19:15", "19:30", "19:45",
	"20:00", "20:15", "20:30", "20:45",
	"21:00", "21:15", "21:30", "21:45",
	"22:00", "22:15", "22:30", "22:45",
	"23:00", "23:15", "23:30", "23:45"
];
//计算实时付款总金额
function calculateTotalMoney(){
    var goodsTotalMoney = Number( $('#totalMoney').html() );//商品总金额
    var deliveryTotalMoney = Number( $('#deliveryTotalMoney').html() );//运费总金额
    var totalMoney = goodsTotalMoney + deliveryTotalMoney;
    var freightMoney=$('#totalDeliveryMoneyHidden').val();//总运费
    var scoreMoney = Number( $('#scoreMoney').val() );//可用的积分支付总金额
    if(scoreMoney>totalMoney){
        scoreMoney -=freightMoney;
        $('.wst-integralse2in').html( scoreMoney.toFixed(2) );
    }else{
    	$('.wst-integralse2in').html( scoreMoney.toFixed(2) );
    }
    if(totalMoney>=10){
    	$('#integralPay').show();
    	var userScoreCanUse=$('#userScoreCanUse').val();//可用积分
    	$('.wst-integralse1').html(userScoreCanUse);
        if( Number( $('#scorePay').val() ) == 1 ){
        	totalMoney -= scoreMoney;
        }
    }else{
    	$('#integralPay').hide();
    	$('.wst-integralse1').html('0');
    }
	$('#payMoney').html( totalMoney.toFixed(2) );
}
//提交订单
function submitOrders(){
    var addressId = $('#addressId').val();//收货地址id
    var payWay = $('#payTypeHidden').val();//支付方式
    var isSelf = $('#deliveryTypeHidden').val();//是否自提
    var isInvoice = $('#isInvoiceHidden').val();///是否开发票
    var invoiceClient = $('#invoiceTitleHidden').val();///发票抬头
    var isOnline = $('#isOnlineHidden').val();//是否是在线支付
    if(goodsIds == ''){
    	WST.msg('没有要结算的商品，请重试','info');
        return false;
    }
    if(addressId == ''){
    	WST.msg('请选择收货地址','info');
        return false;
    }
    if(payWay == ''){
    	WST.msg('请选择支付方式','info');
        return false;
    }
    if(isSelf == ''){
    	WST.msg('请选择提货方式','info');
        return false;
    }
    if(isInvoice == 1 && invoiceClient == ''){
    	WST.msg('请填写发票抬头','info');
        return false;
    }
    var param = {};
    param.addressId = addressId;//收货地址id
    param.payWay = isOnline;//支付方式
    param.isSelf = isSelf;//是否自提
    param.isInvoice = isInvoice;//是否开发票
    param.invoiceClient = invoiceClient;//发票抬头
    param.remarks = $('#orderMarkHidden').val();//订单备注
    var rdate = $("#requireDate").val();
    var rtime = $("#requireTime").val();
    param.requireTime = rdate + " " + rtime + ":00";//送达时间
    param.isScorePay = Number( $('#scorePay').val() );//是否用积分支付
    $('.wst-sett_but').addClass("active").attr('disabled', 'disabled');
    $.post(ThinkPHP.U('WeChat/Orders/addOrder'), param, function(data){
        var json = WST.toJson(data);
        if(json.status==1){
            if(isOnline==1){//在线支付
                location.href = ThinkPHP.U('WeChat/Payments/toPay', 'key='+json.orderunique+'@0');
            }else{
            	WST.msg('下单成功','success');
                setTimeout(function(){
                    location.href = ThinkPHP.U('WeChat/Orders/index');
                },2000);
            }
        }else{
        	WST.msg(json.msg,'info');
    	    $('.wst-sett_but').removeAttr('disabled').removeClass("active");
        }
    });
}