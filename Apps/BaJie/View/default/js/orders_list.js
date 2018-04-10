var orderIdForCancel;//要取消的订单的id
var orderStatusForCancel;//要取消的订单的状态
var orderIdForComfirm;//要确认收货或拒收的订单的id
var cancelOrReject;//cancel:取消订单;reject:拒收订单
//返回订单列表
function returnOrder(){
	WST.showHide('','.order_return,.order_details,#order_details,.order_appraises,#order_appraises');
	WST.showHide(1,'.order_list,#order_list,.users_return,#footer');
}
//加载订单详情页
function getOrderDetails(orderId){
	WST.showHide('','.order_list,#order_list,.users_return,#footer');
	WST.showHide(1,'.order_return,.order_details,#order_details');
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
//取消订单
function confirmCancelOrder(orderId, orderStatus){
    orderIdForCancel = orderId;
    orderStatusForCancel = orderStatus;
    WST.dialog('确定取消该订单吗？','toCancelOrder()');
}
function toCancelOrder(){
    var param = {};
    if(orderStatusForCancel==-2 || orderStatusForCancel==0){//订单未付款或未受理
        cancelOrder(orderIdForCancel);
    }else if(orderStatusForCancel==1 || orderStatusForCancel==2){//订单已受理或打包中
        cancelOrReject = 'cancel';
        var dia=$.dialog({
            title:'取消/拒收',
            content:'<span>请填写原因：</span><textarea class="wst-reason" id="rejectionRemarks" autocomplete="off"></textarea>',
            button:["取消","<button class='wst-reason_submit' onclick='javascript:rejectionRemarks();'>提交</button>"],
        	allowScroll:true
        });
        $('#rejectionRemarks').focus();
    }
}
//提交取消/拒收原因
function rejectionRemarks(){
    var rejectionRemarks = $('#rejectionRemarks').val();
    if(rejectionRemarks==''){
    	WST.msg('原因不能为空','info');
	    $('#rejectionRemarks').focus();
        return false;
    }
    $('.ui-dialog').removeClass("show");
    if(cancelOrReject=='cancel'){//取消订单
        cancelOrder(orderIdForCancel, rejectionRemarks);
    }else if(cancelOrReject=='reject'){//拒收订单
        comfirmOrder(orderIdForComfirm, 2);
    }
}
//取消订单处理
function cancelOrder(orderId, rejectionRemarks){
    var param = {};
    param.orderId = orderId;
    if(rejectionRemarks){
        param.rejectionRemarks = rejectionRemarks;
    }
    $.post(ThinkPHP.U('WeChat/Orders/cancelOrder'), param, function(data){
        var json = WST.toJson(data);
        if(json.status!=-1){
            var status = $('#status').val();
            if(status==0){//当前栏是 全部
            	WST.msg('订单取消成功','success');
                setTimeout(function(){
                    location.href = ThinkPHP.U('WeChat/Orders/index');
                },2000);
            }else{
                setTimeout(function(){
                    $('#order'+orderIdForCancel).hide();
                    setTimeout(function(){
                    	WST.msg('订单取消成功','success');
                        $('#order'+orderIdForCancel).remove();
                    },500);
                },500);
            }
        }else{
        	WST.msg('订单取消失败，请重试！','warn');
        }
        WST.dialogHide(1);
    });
}
//确认收货/拒收
function toConfirmOrder(orderId, receiptOrReject){
    orderIdForComfirm = orderId;
    if(receiptOrReject=='receipt'){
    	WST.dialog('确认收货？','receiptOrder()');
    }else if(receiptOrReject=='reject'){
    	WST.dialog('确定拒收？','rejectOrder()');
    }
}
function receiptOrder(){
	WST.dialogHide(1);
    comfirmOrder(orderIdForComfirm, 1);
}
function rejectOrder(){
	WST.dialogHide(1);
    cancelOrReject = 'reject';
    var dia=$.dialog({
        title:'取消/拒收',
        content:'<span>请填写原因：</span><textarea class="wst-reason" id="rejectionRemarks" autocomplete="off"></textarea>',
        button:["取消","<button class='wst-reason_submit' onclick='javascript:rejectionRemarks();'>提交</button>"],
    	allowScroll:true
    });
    $('#rejectionRemarks').focus();
}
//拒收处理
function comfirmOrder(orderId, type){
    var param = {};
    param.orderId = orderId;
    if(type==1){//确认收货
        param.type = 1;
    }else if(type==2){//拒收订单
        var rejectionRemarks = $('#rejectionRemarks').val();
        if(rejectionRemarks){
            param.rejectionRemarks = rejectionRemarks;
        }
    }
    $.post(ThinkPHP.U('WeChat/Orders/confirmOrder'), param, function(data){
        var json = WST.toJson(data);
        if(json.status==1){
            if(type==1){
                var tips = '已确认收货'
            }else if(type==2){
                var tips = '已拒收'
            }
            var status = $('#status').val();
            if(status==0){//当前栏是 全部
            	WST.msg(tips,'success');
                setTimeout(function(){
                    location.href = ThinkPHP.U('WeChat/Orders/index');
                },2000);
            }else{
                setTimeout(function(){
                	$('#order'+orderIdForComfirm).hide();
                    setTimeout(function(){
                    	WST.msg(tips,'success');
                        $('#order'+orderIdForComfirm).remove();
                    },500);
                },500);
            }
        }else{
        	WST.msg('操作失败，请重试！','warn');
        }
    });
}
//付款
function toPay(key){
    location.href = ThinkPHP.U('WeChat/Payments/toPay', 'key='+key);
}
//加载评价页
function getOrderAppraises(orderId){
	WST.showHide('','.order_list,#order_list,.users_return,#footer,.wst-evaluate_how,#appraises-edit-container');
	WST.showHide(1,'.order_return,.order_appraises,#order_appraises');
    $('#orderId').val(orderId);
    $('#evaluate_list1').empty();
    $('#goodsScore').val('');
    $('#timeScore').val('');
    $('#serviceScore').val('');
    $.post(ThinkPHP.U('WeChat/Orders/getNoAppraiseOrderGoods'), {orderId: orderId}, function(data){
        var json = WST.toJson(data);
        var order=json.order
        //购买时间
        $('#evaluate_time').html('购买时间：'+order.createTime);
        var evaluateList=json.goodslist;
    	var ehtml = '';
        if(evaluateList){
        	for(var c in evaluateList){
        		ehtml += '<thead>';
        		ehtml += '<tr><th style="width:12%;">序号</th><th>商品</th><th>评价</th></tr>';
        		ehtml += '</thead>';
        		ehtml += '<tbody>';
        		c++;
        		ehtml += '<tr><td class="center">'+c+'</td>';
        		c--;
        		ehtml += '<td style="width:60%;"><img class="wst-details_img" src="'+ ThinkPHP.DEFAULT_IMG +'" data-echo="'+ ThinkPHP.ROOT +'/'+evaluateList[c].goodsThums+'">';
        		ehtml += '<span class="wst-details_text" id="details_text'+c+'">'+evaluateList[c].goodsName+'</span></td>';
        		ehtml += '<td class="wst-center" id="isAppraises'+evaluateList[c].goodsId+'_'+evaluateList[c].goodsAttrId+'">';
        		if(evaluateList[c].isAppraises==1){
        			ehtml += '已评价<a href="#appraises-edit-container"><button class="ui-btn wst-evaluate" onclick="scrollToAppraises('+evaluateList[c].id+',\''+evaluateList[c].goodsName+'\',1);">查看评价</button></a>';
        		}else{
        			ehtml += '未评价<a href="#appraises-edit-container"><button class="ui-btn wst-evaluate" onclick="scrollToAppraises('+evaluateList[c].goodsId+',\''+evaluateList[c].goodsName+'\',0,'+evaluateList[c].goodsAttrId+');">去评价</button></a>';
        		}
        		ehtml += '</td></tr>';
        		ehtml += '</tbody>';
        	}
        }
        $('#evaluate_list1').append(ehtml);
        echo.init();//图片懒加载
        $('#orderId').val(order.orderId);
        json = null;
    });
}
function scrollToAppraises(id,goodsName,type, goodsAttrId){
	$('.wst-evaluate_how').hide();
    $('#appraises-edit-container').show();
    $('#goodsName').html(goodsName);
    if(type==1){//查看评价
        $.post(ThinkPHP.U('WeChat/Goods/getAppraiseById'), {id:id}, function(data){
            var json = WST.toJson(data);
            if(json){
                var show0 = '';
                for(var i=0; i<5; i++) {
                    if(i<json.goodsScore){
                    	show0 += '<img class="wst-starsimg" src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars1.png">';
                    }
                }
                $('.stars0').html(show0);
                var goodsScores=json.goodsScore-1
                $('.goods_how'+goodsScores).show();
                var show1 = '';
                for(var i=0; i<5; i++) {
                    if(i<json.serviceScore){
                    	show1 += '<img class="wst-starsimg" src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars1.png">';
                    }
                }
                $('.stars1').html(show1);
                var serviceScores=json.serviceScore-1
                $('.time_how'+serviceScores).show();
                var show2 = '';
                for(var i=0; i<5; i++) {
                    if(i<json.timeScore){
                    	show2 += '<img class="wst-starsimg" src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars1.png">';
                    }
                }
                $('.stars2').html(show2);
                var timeScores=json.timeScore-1
                $('.service_how'+timeScores).show();
                var textarea ='<span class="wst-evaluate_text">'+json.content+'</span>';
                $('#evaluate_content').html(textarea);
                $('#evaluate_submit').hide();
            }
            json = null;
        });
    }else{//添加评价
        $('#goodsId').val(id);
        $('#goodsAttrId').val(goodsAttrId); 
        for(var s=0; s<3; s++){
        	var out = out+s;
        	var out ="";
            for(var i=0; i<5; i++) {
                out += '<img class="wst-starsimg" src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars2.png" onclick="javascript:clickStar('+s+','+i+');">';
            }
            $('.stars'+s).html(out);
        }
        var textarea ='<textarea class="wst-evaluate_content" id="appraises" placeholder="请输入点评内容"></textarea>';
        $('#evaluate_content').html(textarea);
        $('#evaluate_submit').show();
    }
}
//商品评价
function clickStar(which, index){
    if(which==0){
        var where = 'goods';
    }else if(which==1){
        var where = 'time';
    }else{
        var where = 'service';
    }
    $('#'+where+'Score').val(index+1);
    for(var i=0; i<5; i++){
        if( i <= index ){
            $('.'+where+'-appraises').children('img.wst-starsimg').eq(i).attr('src', ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars1.png');
        }else{
            $('.'+where+'-appraises').children('img.wst-starsimg').eq(i).attr('src', ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars2.png');
        }
    }
    $('.'+where+'_how'+index).show().siblings('span.wst-evaluate_how').hide();
}
//提交商品评价
function submitAppraises(){
    var orderId = $('#orderId').val();
    var goodsId = $('#goodsId').val();
    var goodsAttrId = $('#goodsAttrId').val();
    var goodsName = $('#goodsName').html();
    var goodsScore = $('#goodsScore').val();
    var timeScore = $('#timeScore').val();
    var serviceScore = $('#serviceScore').val();
    var appraises = $('#appraises').val();
    if(goodsScore==''){
    	WST.msg('请选择商品评分','info');
        return false;
    }
    if(timeScore==''){
    	WST.msg('请选择时效评分','info');
        return false;
    }
    if(serviceScore==''){
    	WST.msg('请选择服务评分','info');
        return false;
    }
    if( appraises.length < 3 || appraises.length > 50 ){
    	WST.msg('点评内容为3-50个字','info');
        $('#appraises').focus();
        return false;
    }
    var param = {};
    param.orderId = orderId;
    param.goodsId = goodsId;
    param.goodsAttrId = goodsAttrId;
    param.goodsScore = goodsScore;
    param.timeScore = timeScore;
    param.serviceScore = serviceScore;
    param.content = appraises;
    $.post(ThinkPHP.U('WeChat/Goods/addAppraises'), param, function(data){
        var json = WST.toJson(data);
        if(json.status==1){
        	var html = '已评价<a href="#appraises-edit-container"><button class="ui-btn wst-evaluate" onclick="scrollToAppraises('+json.id+',\''+goodsName+'\',1);">查看评价</button></a>';
            $('#isAppraises'+goodsId+'_'+goodsAttrId).html(html);
            $('#appraises-edit-container').hide();
            WST.msg('评价成功','success');
        }else if(json.status==-1){
        	WST.msg('该订单不存在','info');
        }else if(json.status==-2){
        	WST.msg('亲，该商品已经评价过了~','info');
        }else{
        	WST.msg('评价失败，请重试！','warn');
        }
    });
}
$(document).ready(function(){
    initFooter('users');
    if(orderListEmpty){
    	WST.msg('没有订单信息，快去下一个吧~','info');
    }
    $('.order_lists').click(function(){
    	$('#Load').show();
        $(this).addClass('wst-press').siblings('div.ui-col').removeClass('wst-press');
        var status = $(this).attr('status');
        $('#status').val(status);
        $('#orders-list').html('');
        var param = {};
        if(status){
            param.status = status;
        }
        param.byAjax = 1;
        $.post(ThinkPHP.U('WeChat/Orders/index'), param, function(data){
            var json = WST.toJson(data);
            var ohtml = '';
            var orderStatus = {'-7':'用户取消','-6':'用户取消','-5':'商家不同意拒收','-4':'商家确认拒收','-3':'用户拒收','-2':'未付款','-1':'用户取消','0':'未受理','1':'已受理','2':'打包中','3':'待收货','4':'已收货'};
            
            if(json && json.root && json.root.length>0){
                for(var i=0; i<json.root.length; i++){
                	ohtml += '<div id="order'+json.root[i].orderId+'">';
                	ohtml += '<div class="wst-interval"></div>';
                	ohtml += '<div class="ui-row-flex ui-whitespace wst-orders_shop">';
                	ohtml += '<div class="ui-col ui-col-2">'+json.root[i].shopName+'</div>';
                	ohtml += '<div class="ui-col wst-orders_shopr"><span>';
                    if(status==4){
                    	ohtml += '已收货';
                    }else if(status==5){
                    	ohtml += '待评价';
                    }else{
                    	ohtml += orderStatus[json.root[i].orderStatus];
                    }
                    if(json.root[i].orderStatus==-7||json.root[i].orderStatus==-6||json.root[i].orderStatus==-4||json.root[i].orderStatus==-1){
                        if(json.root[i].payType==1 && json.root[i].isPay==1){
                        	ohtml += (json.root[i].isRefund==1) ? '(已退款)' : '(未退款)';
                        }
                    }
                    ohtml += '</span></div>';
                    ohtml += '</div>';
                    ohtml += '<a href="javascript:void(0);" onclick="javascript:getOrderDetails('+json.root[i].orderId+')">';
                    if(json.root[i].data && json.root[i].data.length>0){
                        for(var j=0; j<json.root[i].data.length; j++){
                        	ohtml += '<div class="ui-row-flex wst-orders_goods">';
                        	ohtml += '<div class="ui-col"><div class="wst-orders_goodsl"><img src="'+ ThinkPHP.DEFAULT_IMG +'" data-echo="'+ ThinkPHP.ROOT +'/'+json.root[i].data[j].goodsThums+'"></div></div>';
                        	ohtml += '<div class="ui-col ui-col-3">';
                        	ohtml += '<div class="wst-orders_goodsr1">'+json.root[i].data[j].goodsName+'</div>';
                        	ohtml += '<div class="wst-orders_goodsr2"><span class="wst-orders_prices">¥'+json.root[i].data[j].goodsPrice+'</span><br/><span>×'+json.root[i].data[j].goodsNums+'</span></div>';
                        	ohtml += '</div>';
                        	ohtml += '</div>';
                        }
                    }
                    ohtml += '</a>';
                    ohtml += '<div class="ui-whitespace wst-orders_shopb">';
                    if(json.root[i].orderStatus==-2 || json.root[i].orderStatus==0 || json.root[i].orderStatus==1 || json.root[i].orderStatus==2){
                    	ohtml += '<a href="javascript:void(0);" onclick="javascript:confirmCancelOrder('+json.root[i].orderId+','+json.root[i].orderStatus+');">取消订单</a>';
                    }
                    if(json.root[i].orderStatus==-2){
                    	ohtml += '<a href="javascript:void(0);" class="wst-orders_shopbs" onclick="javascript:toPay(\''+json.root[i].orderId+'@1\');">继续支付</a>';
                    }
                    if(json.root[i].orderStatus==3){
                    	ohtml += '<a href="javascript:void(0);" onclick="javascript:toConfirmOrder('+json.root[i].orderId+',\'receipt\');">确认收货</a>';
                    	ohtml += '<a href="javascript:void(0);" class="wst-orders_shopbs" onclick="javascript:toConfirmOrder('+json.root[i].orderId+',\'reject\');">拒收</a>';
                    }
                    if(json.root[i].orderStatus==4){
                    	ohtml += '<a href="javascript:void(0);" onclick="javascript:getOrderAppraises('+json.root[i].orderId+');">'+((json.root[i].isAppraises==0) ? '去评价' : '查看评价')+'</a>';
                    }
                    if( json.root[i].noComplains==1 && (json.root[i].orderStatus==-5 || json.root[i].orderStatus==-4 || json.root[i].orderStatus==-3 || json.root[i].orderStatus==4) ){
                    	ohtml += '<a href="javascript:void(0);" class="wst-orders_shopbs" onclick="javascript:goToComplains('+json.root[i].orderId+');">投诉</a>';
                    }
                    ohtml += '</div>';
                    var goodsCount = json.root[i].data ? json.root[i].data.length : '';
                    ohtml += '<div class="ui-whitespace wst-orders_price">';
                    ohtml += '<span class="wst-orders_pricet">实付：<span class="wst-orders_prices">¥'+json.root[i].needPay+'</span></span><span class="wst-orders_pricet">共 '+goodsCount+'件商品</span>';
                    ohtml += '</div>';
                    ohtml += '</div></div>';
                }
            }else{
            	WST.msg('没有订单信息，快去下一个吧~','info');
            }
            $('#orders-list').html(ohtml);
            $('#Load').hide();
            echo.init();//图片懒加载
        });
    });
});