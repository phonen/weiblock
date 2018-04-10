$(document).ready(function(){
    initFooter('home');
});
function goodsDetails(){
	//滑动效果
	var mySwiper = new Swiper('.swiper-container', {
		slidesPerView: 1,
		freeMode : true,
	    spaceBetween: 5,
	    prevButton:'.wst-parameter',
	    nextButton:'.wst-introduce',
	});
	//图
	(function(){
	    var slider = new fz.Scroll('.ui-slider', {
	        role: 'slider',
	        indicator: true,
	        autoplay: true,
	        interval: 3000
	    });
	})();
	//内部窗口w宽，h高
	var w=window.innerWidth
	|| document.documentElement.clientWidth
	|| document.body.clientWidth;
	var h=window.innerHeight
	|| document.documentElement.clientHeight
	|| document.body.clientHeight;
		var o=$('.ui-slider').css("padding-top",w);
	    var scroll = new fz.Scroll('.ui-slider', {
	        scrollY: true
	    });
	//加载商品详细介绍
	var goodsId = $('#goodsDesc').attr('goodsId');
	$.post(ThinkPHP.U('WeChat/Goods/getGoodsDescByGoodsId'), {goodsId:goodsId}, function(data){
        var json = WST.toJson(data);
        if(json.status==1){
            $('#goodsDesc').html(json.goodsDesc);
        }else{
        	WST.msg('获取商品介绍失败，请重试！','warn');
        }
    });
}
//检查商品库存/商品详情
function checkStock(obj, id, goodsId){
    $('#goodsAttrId').val(id);
    $(obj).addClass('wst-goodschoose ui-tag-freelimit').siblings().removeClass('wst-goodschoose ui-tag-freelimit');
    $.post(ThinkPHP.U('WeChat/Goods/getPriceAttrInfo'), {goodsId:goodsId, id:id}, function(data){
        var json = WST.toJson(data);
        $('#goodsPrice').html('¥'+json.attrPrice);
        $('#goodsStock').val(json.attrStock);
        if(json.attrStock>0){
          $('.goodsStock').html('&nbsp;&nbsp;&nbsp;有货').removeClass('wst-goods-red').addClass('wst-goods-black');
        }else{
          $('.goodsStock').html('&nbsp;&nbsp;&nbsp;无货').removeClass('wst-goods-black').addClass('wst-goods-red');
        }
    });
}
//优惠套餐详情
function inPackage(id){
	var data='#pac-header,#footer,#pac-list';
	var data2='#header_'+id+',#pac-footer2,#price_'+id+',#pac-details,.list_'+id;
	WST.showHide('',data);
	WST.showHide(1,data2);
}
//返回优惠套餐
function inReturn(id){
	var data='#header_'+id+',#pac-footer2,#price_'+id+',#pac-details,.list_'+id;
	var data2='#pac-header,#footer,#pac-list';
	WST.showHide('',data);
	WST.showHide(1,data2);
}
//检查商品库存/优惠套餐
function checkSpec(obj,goodsId,packageId,id){
	if(id>0){
	    $(obj).addClass('wst-goodschoose ui-tag-freelimit').siblings().removeClass('wst-goodschoose ui-tag-freelimit');
	    $.post(ThinkPHP.U('WeChat/Goods/getPriceAttrInfo'), {goodsId:goodsId, id:id}, function(data){
	        var json = WST.toJson(data);
	        if(json.attrStock>0){
	            $('#stock_'+packageId+'_'+goodsId).html(json.attrStock);
	        }else{
	        	$('#stock_'+packageId+'_'+goodsId).html('商品库存不足');
	        }
	    });
	}else{
		var stocks=$('#stock_'+packageId+'_'+goodsId).attr("stocks");
		if(stocks>0){
			$(obj).addClass('wst-checkg');
		}else{
			WST.msg('商品库存不足','info');
    	    return;
		}
	}
	var packagePrice = 0;
	var flag = 1;
	$(".list_"+packageId).each(function(){
		var hasAttr = $(this).attr("hasAttr");
		if(hasAttr==0){
			if($(this).hasClass("wst-checkg")){
				packagePrice += parseFloat($(this).attr("attrPrice"),10);
			}else{
				flag = 0;
			}
		}else{
			var gattr = $(this).find(".wst-goodschoose");
			if(gattr.length>0){
				packagePrice += parseFloat(gattr.attr("attrPrice"),10);
			}else{
				flag = 0;
			}
		}
	});
	if(flag==1){
		$("#package_"+packageId).html(packagePrice);
	}else{
		$("#package_"+packageId).html($("#package_"+packageId).attr("allPrices"));
	}
	$("#prompt_"+packageId+"_"+goodsId).hide();
}
//加入购物车/优惠套餐
function addCartPackage(obj,packageId){
	var flag = 1;
	var gflag = 1;
	var goodsAttrIds = [];
	$(".list_"+packageId).each(function(){
		var goodsId = $(this).attr("goodsId");
		var attrId = 0;
		var hasAttr = $(this).attr("hasAttr");
		if(hasAttr==0){
			if($(this).hasClass("wst-checkg")){
				attrId = $(this).attr("attrId");
				$("#prompt_"+packageId+"_"+goodsId).hide();
			}else{
				$("#prompt_"+packageId+"_"+goodsId).show();
				flag = 0;
			}
		}else{
			var gattr = $(this).find(".wst-goodschoose");
			if(gattr.length>0){
				attrId = gattr.attr("attrId");
				$("#prompt_"+packageId+"_"+goodsId).hide();
			}else{
				$("#prompt_"+packageId+"_"+goodsId).show();
				flag = 0;
			}
		}
		goodsAttrIds.push(goodsId+"_"+attrId+"_"+1);
	});
	if(flag==0){
		return;
	}
	var params = {};
		params.goodsAttrIds = goodsAttrIds.join("@");
		params.packageId = packageId;
	$(obj).attr("disabled",false);
	$.post(ThinkPHP.U('WeChat/Cart/addCartPackage') ,params,function(data) {
		var json = WST.toJson(data);
		if(json.status==1){
			WST.msg('恭喜，加入购物车成功！','success');
            setTimeout(function(){
            	location.href= ThinkPHP.U('WeChat/Cart/index');
            }, 2000);
		}else{
			WST.msg(json.msg,'info');
			$(obj).attr("disabled",true);
		}
	});
}
//跳转到商品评价页
function viewAppraises(goodsId){
    location.href = ThinkPHP.U("WeChat/Goods/toGoodsAppraise", "goodsId="+goodsId);
}
//跳转到商品优惠
function getPackages(goodsId){
    location.href = ThinkPHP.U("WeChat/Goods/toPackages", "goodsId="+goodsId);
}