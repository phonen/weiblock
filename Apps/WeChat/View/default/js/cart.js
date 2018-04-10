$(document).ready(function(){
    initFooter('cart');
    calculateTotalMoney();
    
    //选中店铺
    $('.ui-icon-successc').click(function(){
        changeIconStatus($(this), 1);
        var childrenId = $(this).attr('childrenId');
        var isCheck = ( $(this).attr('class').indexOf('wst-active') == -1 ) ? 0 : 1;
        if( $(this).attr('class').indexOf('wst-active') == -1 ){//取消选中
            changeIconStatus($('.'+childrenId), 2);
        }else{//选中
            changeIconStatus($('.'+childrenId), 2, 'wst-active');
        }
        var shop = $(this).attr('shop');
        var goodsCount = $('.ui-icon-successc1').length;//商品个数
	    for(var i=0; i<goodsCount; i++){
	        if( $('.ui-icon-successc1').eq(i).attr('shop') == shop ){
            	$('.ui-icon-successc1').eq(i).attr('isCheck', isCheck);
        		changeIsCheck( $('.ui-icon-successc1').eq(i) );
	        }
	    }
    });
    
    //选中商品
    $('.ui-icon-successc1').click(function(){
        changeIconStatus($(this), 1);
        var parentId = $(this).attr('parentId');
        var goodsId = Number( $(this).attr('goodsId'));
        if( $(this).attr('class').indexOf('wst-active') == -1 ){//取消选中
            changeIconStatus($('.'+parentId), 2);
            $(this).attr('isCheck', 0);
        }else{//选中
            var childrenId = $('.'+parentId).attr('childrenId');
            var goodsCount = $('.'+childrenId).length;//商品个数
            var isActive = 0;
            for(var i=0; i<goodsCount; i++){
                if( $('.'+childrenId).eq(i).attr('class').indexOf('wst-active') != -1 ){
                    isActive++;
                }
            }
            if(isActive == goodsCount){
                changeIconStatus($('.'+parentId), 2, 'wst-active');
            }
            $(this).attr('isCheck', 1);
        }
        changeIsCheck( $(this) );
    });
    //选中合计
    $('.ui-icon-successct').click(function(){
        changeIconStatus($(this), 1);
        var shopIconCount = $('.ui-icon-successc').length;//店铺个数
        var goodsCount = $('.ui-icon-successc1').length;//商品个数
        if( $(this).attr('class').indexOf('wst-active') == -1 ){//取消选中所有
            for(var i=0; i<shopIconCount; i++){
                changeIconStatus($('.ui-icon-successc').eq(i), 2);
            }
            for(var i=0; i<goodsCount; i++){
                changeIconStatus($('.ui-icon-successc1').eq(i), 2);
            }
        }else{//选中所有
            for(var i=0; i<shopIconCount; i++){
                changeIconStatus($('.ui-icon-successc').eq(i), 2, 'wst-active');
            }
            for(var i=0; i<goodsCount; i++){
                changeIconStatus($('.ui-icon-successc1').eq(i), 2, 'wst-active');
                changeIsCheck($('.ui-icon-successc1').eq(i),1);
            }
        }
        calculateTotalMoney();//计算总金额
    });
    //编辑
    $('#editCart').click(function(){
        $(this).hide();
        WST.showHide('','.wst-cart2r,.package2,.wst-cart2r1,.wst-settlement');
        WST.showHide(1,'.wst-buy_ca,.wst-buy_cas,.wst-cart_delete,#editDone');
        $('#state').val('1');
    });
    $('#editDone').click(function(){
        location.href = ThinkPHP.U('WeChat/Cart/index');
        $('#state').val('0');
    });
    $('#delit').click(function(){
        var goodsIconCount = $('.ui-icon-successc1').length;//商品个数
        var goodsIds = '';
        for(var i=0; i<goodsIconCount; i++){
            if( $('.ui-icon-successc1').eq(i).attr('class').indexOf('wst-active') != -1 ){
                goodsIds += $('.ui-icon-successc1').eq(i).prev('input.goodsid').val() + '#';
            }
        }
        if(goodsIds!=''){
        	WST.dialog('确定删除选中的商品吗？','delGoodsFromCart()');
        }else{
        	var dia=$.dialog({
        		title:'提示',
                content:'请选择要删除的商品',
                button:["确认"],
                allowScroll:true
            });
        }
    });
    //结算
    $('#buyit-area').click(function(){
        var goodsIconCount = $('.ui-icon-successc1').length;//商品个数
        var noGoodsSelected = true;
        for(var i=0; i<goodsIconCount; i++){
            if( $('.ui-icon-successc1').eq(i).attr('class').indexOf('wst-active') != -1 ){
                noGoodsSelected = false;
            }
        }
        if(noGoodsSelected){
        	WST.msg('请勾选要结算的商品','info');
            return false;
        }
        location.href = ThinkPHP.U('WeChat/Cart/settlement', 'isDefault=1');
    });
});

//计算总金额
function calculateTotalMoney(){
    var goodsCount = $('.ui-icon-successc1').length;//商品个数
    var totalMoney = 0;//合计金额
    var totalMoneyIconStatus = false;
    for(var i=0; i<goodsCount; i++){
        if( $('.ui-icon-successc1').eq(i).attr('class').indexOf('wst-active') != -1 ){
		    var goodsNum = Number( $('.ui-icon-successc1').eq(i).attr('goodsNum') );
		    var shopPrice =  Number( $('.ui-icon-successc1').eq(i).attr('shopPrice') );
            totalMoneyIconStatus = true;
            totalMoney += goodsNum*shopPrice;//金额相加
        }
    }
    if( totalMoneyIconStatus ){
        changeIconStatus($('.ui-icon-successct'), 2, 'wst-active');
    }else{
        changeIconStatus($('.ui-icon-successct'), 2);
    }
    $('#total-money').html(totalMoney.toFixed(2));
    $('#total-money1').html(totalMoney.toFixed(2));

    calculateDeliveryMoney();
}

//计算店铺的运费
function calculateDeliveryMoney(){
    var goodsCount = $('.ui-icon-successc1').length;//商品个数
    var shopCount = $('.ui-icon-successc').length;//店铺个数
    for(var i=0; i<shopCount; i++){
        var shop = $('.ui-icon-successc').eq(i).attr('shop');
        var shopGoodsMoney = 0;
	    for(var j=0; j<goodsCount; j++){
	        if( $('.ui-icon-successc1').eq(j).attr('shop') == shop && $('.ui-icon-successc1').eq(j).attr('class').indexOf('wst-active') != -1 ){
		    	var goodsNum = Number( $('.ui-icon-successc1').eq(j).attr('goodsNum') );
		    	var shopPrice =  Number( $('.ui-icon-successc1').eq(j).attr('shopPrice') );
		    	shopGoodsMoney += goodsNum * shopPrice;
	        }
	    }
        var deliveryFreeMoney = Number( $('.ui-icon-successc').eq(i).attr('deliveryFreeMoney') );//包邮起步价
	    var msg = '';
	    if( deliveryFreeMoney == 0 ){
	        msg += '店铺免运费';
	    }else if( deliveryFreeMoney > shopGoodsMoney ){
	        msg += '满'+ deliveryFreeMoney +'元免运费，差'+ (deliveryFreeMoney - shopGoodsMoney) +'元';
	    }else{
	        msg += '购买商品已达到'+ deliveryFreeMoney +'元，店铺免运费';
	    }
	    var shopMsg = $('.ui-icon-successc').eq(i).attr('childrenId');
	    $('#'+shopMsg).html(msg);
    }
}
//勾选/取消勾选商品
function changeIsCheck(obj,total){
    var goodsNum = Number( $(obj).attr('goodsNum') );
	if(goodsNum<=0){
		WST.msg('购买数量不能小于1','info');
        return false;
    }
    var goodsId = Number( $(obj).attr('goodsId') );
    var goodsAttrId = Number( $(obj).attr('goodsAttrId') );
    var shopPrice =  Number( $(obj).attr('shopPrice') );
    var isCheck =  Number( $(obj).attr('isCheck') );
    var batchNo =  Number( $(obj).attr('batchNo') );
    var packageId = Number( $(obj).attr('packageId') );
    changeCartGoodsNum(goodsId, goodsAttrId, goodsNum, shopPrice, isCheck,obj,batchNo,packageId,total);
}
//修改购物车商品的数量
function changeCartGoodsNum(goodsId, goodsAttrId, goodsNum, shopPrice, isCheck,obj,batchNo,packageId,total){
    var isCheck = (isCheck==0 || isCheck==1)  ? isCheck : -1;
    var param = {};
    param.goodsId = goodsId;
    param.goodsAttrId = goodsAttrId;
    param.num = goodsNum;
    param.isCheck = isCheck;
    param.batchNo = batchNo;
    param.packageId = packageId;
    param.total = total;
    $.post(ThinkPHP.U('WeChat/Cart/changeCartGoodsNum'), param, function(data){
        var json = WST.toJson(data);
        if(json.status == -2){
            var stock=json.goodsStock;
            WST.msg('库存不足，当前库存:'+stock,'info');
            var state=$('#state').val();
            var parentId = $(obj).attr('parentId');
            if(state==0){
                changeIconStatus($('.'+parentId), 2);
            	$(obj).removeClass('ui-icon-success-block wst-active').addClass('ui-icon-unchecked-s');
            }
        }else if(json.status == 1){
            $("#goodsCount_"+goodsId+'_'+goodsAttrId).val(goodsNum);
            calculateTotalMoney();//计算所有商品的总金额
        }
    });
}
//修改数量
function changeNum(goodsId, goodsAttrId, shopPrice, flag){
    var num = parseInt( $("#goodsCount_"+goodsId+'_'+goodsAttrId).val(), 10);
    var num = num ? num : 1;
    if(flag == 1){
        if(num > 1){
        	num = num - 1;
        }
    }else if(flag == 2){
        num = num + 1;
    }
    var maxVal = parseInt($("#goodsCount_"+goodsId+'_'+goodsAttrId).attr('maxVal'),10);
    if(maxVal<=num)num=maxVal;
    $('#goods_'+goodsId).attr('goodsNum', num);
    var isCheck = $('#goods_'+goodsId).attr('isCheck');
    var batchNo = $('#goods_'+goodsId).attr('batchNo');
    var packageId = $('#goods_'+goodsId).attr('packageId');
    changeCartGoodsNum(goodsId, goodsAttrId, num, shopPrice,isCheck,'',batchNo,packageId);
}

//删除购物车中的商品
function delGoodsFromCart(){
    var goodsIconCount = $('.ui-icon-successc1').length;//商品个数
    var cartIds = '';
    for(var i=0; i<goodsIconCount; i++){
        if( $('.ui-icon-successc1').eq(i).attr('class').indexOf('wst-active') != -1 ){
            cartIds += $('.ui-icon-successc1').eq(i).attr('cartId') + '#';
        }
    }
    $.post(ThinkPHP.U('WeChat/Cart/delCartGoods'), {cartIds:cartIds}, function(data){
        var json = WST.toJson(data);
        if(json.status==1){
        	WST.msg('删除成功','success');
            setTimeout(function(){
                location.href = ThinkPHP.U('WeChat/Cart/index');
            }, 2000);
        }else{
        	WST.msg('操作失败，请重试','success');
        }
        WST.dialogHide(1);
    });
}