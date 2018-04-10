//排序条件
function orderCondition(obj,desc){
    var classContent = $(obj).attr('class');
    var status = $(obj).attr('status');
    var theSiblings = $(obj).siblings('.sorts');
    $(obj).addClass('current');
    theSiblings.removeClass('current').attr('status','down');
    if(classContent.indexOf('current')==-1){
        $(obj).children('.spot').show();
        $(obj).children('.arrow-up').show();
        $(obj).children('.arrow_spot').hide();
        theSiblings.children('.arrows').hide();
        theSiblings.children('.arrow_spot').show();
        theSiblings.children('.spot').hide();
        
    }
    if(status.indexOf('down')>-1){
        if(classContent.indexOf('current')==-1){
            $(obj).children('.arrow-down').show();
            $(obj).children('.arrow-up').hide();
            $('#goodsOrder').val('0');
        }else{
            $(obj).children('.arrow-down').hide();
            $(obj).children('.arrow-up').show();
            $(obj).attr('status','up');
            $('#goodsOrder').val('1');
        }
    }else{
        $(obj).children('.arrow-down').show();
        $(obj).children('.arrow-up').hide();
        $(obj).attr('status','down');
        $('#goodsOrder').val('0');
    }
    $('#goodsOrderby').val(desc);//排序条件
    $('#currPage').val('0');//当前页归零
    $('#goods-list').html('');
    getGoodsList();
}
//商品列表
function getGoodsList(){
	$('#Load').show();
    loading = true;
    var param = {};
    param.shopId = $('#shopId').val();
    param.pageSize = 10;
    param.currPage = Number( $('#currPage').val() ) + 1;
    param.startPrice = Number( $('#startPrice').val() );
    param.endPrice = Number( $('#endPrice').val() );
    param.desc = $('#goodsOrderby').val();
    param.descType = $('#goodsOrder').val();
    param.catId = $('#catId').val();
    $.post(ThinkPHP.U('WeChat/Shops/getShopGoodsList'), param, function(data){
        var json = WST.toJson(data);
        var shtml = '';
        if(json && json.root && json.root.length>0){
            for(var i=0; i<json.root.length; i++){
            	shtml += '<ul class="ui-row wst-rowg">';
            	shtml += '<a href="javascript:void(0);" onclick="javascript:goToGoodsDetails('+json.root[i].goodsId+');">';
            	shtml += '<li class="ui-col ui-col-25"><div class="ui-collsl"><img src="'+ ThinkPHP.DEFAULT_IMG +'" data-echo="'+ ThinkPHP.ROOT +'/'+json.root[i].goodsThums+'"></div></li>';
            	shtml += '<li class="ui-col ui-col-75"><div class="wst-colrsl">';
            	shtml += '<span class="ui-nowrap-flex wst-nowrap-flexgo">'+json.root[i].goodsName+'</span>';
            	shtml += '<div class="ui-row-flex"><div class="ui-col-3 wst-icon-trendg">';
            	shtml += '<div class="wst-imgxx">';
                for(var j=1; j<6; j++){
                    if(j <= json.root[i].totalScore){
                    	shtml += '<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars1.png">';
                    }else{
                    	shtml += '<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars2.png">';
                    }
                }
                shtml += '<span>&nbsp;'+json.root[i].totalScore+'.0</span></div>';
            	shtml += '<span class="wst-icon-trendgt">¥'+json.root[i].shopPrice+'</span>';
            	shtml += '</div>';
            	shtml += '<div class="ui-col ui-col-2 ui-whitespace wst-carg">';
            	shtml += '<a href="javascript:void(0);" onclick="javascript:addHgoodsCart('+json.root[i].goodsId+','+json.root[i].goodsAttrId+','+json.root[i].shopId+','+json.root[i].goodsStock+');">';
            	shtml += '<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/car.png">';
            	shtml += '</a>';
                shtml += '</div></div></div>';
                shtml += '</li></a>';
                shtml += '</ul>';
            }
            $('#currPage').val(json.currPage);
            $('#totalPage').val(json.totalPage);
        }else{
        	shtml += '<ul class="ui-row-flex wst-flexslp">';
        	shtml += '<li class="ui-col ui-flex ui-flex-pack-center">';
        	shtml += '<p>对不起，没有相关商品。</p>';
        	shtml += '</li>';
        	shtml += '</ul>';
        }
        $('#goods-list').append(shtml);
        loading = false;
        $('#Load').hide();
        echo.init();//图片懒加载
    });
}
//加入店铺购物车
function addHgoodsCart(goodsId, goodsAttrId,shopId,goodsStock,gcount){
    var param = {};
    param.gcount = gcount ? gcount : 1;
    param.goodsId = goodsId;
    param.goodsAttrId = goodsAttrId;
    param.rnd = Math.random();
    param.shopId=shopId;
    param.goodsStock = goodsStock;
    $.post(ThinkPHP.U('WeChat/Cart/addToCartAjax'), param, function(data){
        var json = WST.toJson(data);
        if(json.status==1){
            $.post(ThinkPHP.U('WeChat/Cart/getshopCartGoodCnt'), param, function(data){
                var json = WST.toJson(data);
                if(json){
                	WST.msg('恭喜，加入购物车成功！','success');
                }
            	var carts=json.cartgoods[param.shopId].goodsCnt;
            	$('#shops-cart').show();
            	$('#shops-cart').html(carts);
            	var totalPrices=json.totalMoney;
            	$('#shops-totalPrices').html(totalPrices);
            });
        }
        if(json.status==2){
        	WST.msg('库存不足,请选择其他商品','info');
            return false;
        }
    });
}

var currPage = totalPage = 0;
var loading = false;
$(document).ready(function(){
	initFooter('home');
    getGoodsList();
        
    $(window).scroll(function(){  
        if (loading) return;
        if ((5 + $(window).scrollTop()) >= ($(document).height() - $(window).height())) {
            currPage = Number( $('#currPage').val() );
            totalPage = Number( $('#totalPage').val() );
            if( totalPage > 0 && currPage < totalPage ){
                getGoodsList();
            }
        }
    });
    //结算
    $('#shops-buyit').click(function(){
    	var carts=$('#shops-cart').html();
    	if(carts>0){
    		location.href = ThinkPHP.U('WeChat/Cart/index');
    	}else{
    		WST.msg('购物车无该店铺商品','info');
    	    shopClassifyOUT();
    	    return false;
    	}
    });
});