//返回订单列表
function returnOrderBad(){
	WST.showHide('','.return_bad,.order_details,#order_details');
	WST.showHide(1,'.orders_bad,#orders_bad,.users_return,#footer');
}
//加载订单详情页
function getOrderDetails(orderId){
	WST.showHide('','.orders_bad,#orders_bad,.users_return,#footer');
	WST.showHide(1,'.return_bad,.order_details,#order_details');
	$('#details_list1').empty();
	$('#details_list2').empty();
    $.post(ThinkPHP.U('WeChat/Orders/getOrderDetails'), {orderId:orderId}, function(data){
        var json = WST.toJson(data);
        //日志信息
        var logs=json.logs;
    	var lhtml = '';
        if(logs){
        	for(var a in logs){
        		lhtml += '<tbody>';
        		lhtml += '<tr><td style="width:50%;">'+logs[a].logTime+'</td><td>'+logs[a].logContent+'</td></tr>';
        		lhtml += '</table>';
        	}
        }
        $('#details_list1').append(lhtml);
        //订单信息
        var order=json.order;
        $('.information1').html(order.orderNo);
        if(order.payType==1){
        	$('.information2').html('在线支付');
        }else{
        	$('.information2').html('货到付款');
        }
        if(order.deliverType==1){
        	$('.information3').html('门店配送');
        }else{
        	$('.information3').html('商城配送');
        }
        $('.information4').html(order.orderRemarks);
        $('.information5').html(order.createTime);
        //收货人信息
        $('.consignee1').html(order.userName);
        $('.consignee2').html(order.userAddress);
        $('.consignee3').html(order.userPhone);
        $('.consignee4').html(order.requireTime);
        //商品信息
        var goodsList=json.goodsList;
    	var ghtml = '';
        if(goodsList){
        	for(var b in goodsList){
        		ghtml += '<tr><td style="width:50%;">';
        		ghtml += '<img class="wst-details_img" src="'+ ThinkPHP.DEFAULT_IMG +'" data-echo="'+ ThinkPHP.ROOT +'/'+goodsList[b].goodsThums+'"><span class="wst-details_text">'+goodsList[b].goodsName+'</span>';
        		ghtml += '</td><td class="wst-center">¥'+goodsList[b].shopPrice+'</td><td class="wst-center">'+goodsList[b].goodsNums+'</td><td class="wst-center">¥'+goodsList[b].money+'</td></tr>';
        		
        	}
        }
        $('#details_list2').append(ghtml);
        echo.init();//图片懒加载
        //金额
        $('.price1').html('商品总金额：¥'+order.totalMoney);
        $('.price2').html('+ 运费：¥'+order.deliverMoney);
        $('.price3').html(order.needPay);
        json = null;
    });
}
$(document).ready(function(){
    initFooter('users');
});