var WST = WST?WST:{};
WST.wxv = '1.7.6_1020';
WST.toJson = function(str,notLimit){
	var json = {};
	try{
		if(typeof(str )=="object"){
			json = str;
		}else{
			json = eval("("+str+")");
		}
		if(!notLimit){
			if(json.status && json.status=='-999'){
				var urls = escape(location.href);
    		var url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='+WST.WeiXinAppId+'&redirect_uri='+urls+'&response_type=code&scope=snsapi_userinfo&state='+WST.WeiXinAppCode+'#wechat_redirect';
    		window.location.href = url;
			}
		}
	}catch(e){
		alert("系统发生错误:"+e.getMessage);
		json = {};
	}
	return json;
}
//搜索
function WSTSearch(flag){
    var key = $('#ui-icon-search').val();
    if(key==''){
    	if(flag==1){
			WST.msg('请输入要搜索的商家名称','info');
		    return false;
    	}else{
        	WST.msg('请输入要搜索的商品名称','info');
            return false;
    	}
    }
    var url = (flag==1) ? 'WeChat/Shops/goToShops' : 'WeChat/Goods/index';
    location.href = ThinkPHP.U(url, 'key='+key);
}
//加入商品到购物车
function addGoodsCart(goodsId, goodsAttrId,goodsStock,gcount){
    var param = {};
    param.gcount = gcount ? gcount : 1;
    param.goodsId = goodsId;
    param.goodsAttrId = goodsAttrId;
    param.rnd = Math.random();
    param.goodsStock = goodsStock;
    $.post(ThinkPHP.U('WeChat/Cart/addToCartAjax'), param, function(data){
        var json = WST.toJson(data);
        if(json.status==1){
            $.post(ThinkPHP.U('WeChat/Cart/getCartGoodCnt'), {}, function(data){
                var json = WST.toJson(data);
                WST.msg('恭喜，加入购物车成功！','success');
                $('#cartGoodsNum').show();
                $('#cartGoodsNum').html(json.goodscnt);
            });
        }
        if(json.status==2){
        	WST.msg('库存不足,请选择其他商品','info');
            return false;
        }
    });
}

//商品详情页:加入购物车
function addToCart(goodsId){
    var gcount = $('#goodscount').val();
    var goodsAttrId = $('#goodsAttrId').val();
    var goodsStock = $('#goodsStock').val();
    addGoodsCart(goodsId, goodsAttrId,goodsStock,gcount);
}

//商品详情页:立即购买
function buyItNow(goodsId){
	var goodsStock = $('#goodsStock').val();
    var param = {};
    var gcount = $('#goodscount').val();
    param.gcount = gcount;
    param.goodsId = goodsId;
    param.goodsAttrId = $('#goodsAttrId').val();
    param.rnd = Math.random();
    param.goodsStock = goodsStock;
    $.post(ThinkPHP.U('WeChat/Cart/addToCartAjax'), param, function(data){
        var json = WST.toJson(data);
        if(json.status==1){
            location.href = ThinkPHP.U('WeChat/Cart/index');
        }
        if(json.status==2){
        	WST.msg('库存不足,请选择其他商品','info');
            return false;
        }
    });
}

//修改商品购买数量
function changeGoodsNum(flag){
    var num = parseInt($(".wst-buy_l2").val(),10);
    var num = num?num:1;
    if(flag==1){
        if(num>1)num = num-1;
    }else if(flag==2){
        num = num+1;
    }
    var maxVal = parseInt($(".wst-buy_l2").attr('maxVal'),10);
    if(maxVal<=num)num=maxVal;
    $(".wst-buy_l2").val(num);
}
//品牌页
function brandsLists(){
	location.href = ThinkPHP.U('WeChat/Brands/brandsList');
}
//社区页
function communityList(){
    location.href = ThinkPHP.U('WeChat/Index/community');
}
//城市列表页
function citysList(){
    location.href = ThinkPHP.U('WeChat/Index/citys');
}
//商家主页
function goToShopHome(shopId){
    location.href = ThinkPHP.U('WeChat/Shops/index','shopId='+shopId);
}

//商品详情
function goToGoodsDetails(goodsId){
    location.href = ThinkPHP.U('WeChat/Goods/goodsDetails','goodsId='+goodsId);
}

//品牌列表页的商品
function getGoodsListByBrands(brandId){
    location.href = ThinkPHP.U('WeChat/Goods/index', 'brandId='+brandId);
}

//跳到商品分类页
function toGoodsCat(){
    location.href = ThinkPHP.U('WeChat/GoodsCat/index');
}
//订单投诉
function goToComplains(orderId){
    location.href = ThinkPHP.U('WeChat/OrderComplains/toComplains','orderId='+orderId);
}

//关注商品或店铺
function favorite(obj, targetId, favoriteType){
	var param = {};
    param.targetId = targetId;
    param.favoriteType = favoriteType;
    $(obj).attr('disabled', 'disabled').addClass('active');
    $.post(ThinkPHP.U('WeChat/Favorites/favorite'), param, function(data){
        var json = WST.toJson(data);
        if(json.status == 1){
        	WST.msg('已收藏','success');
    		if(favoriteType == 1){
    			var html = '<button class="ui-btn-s wst-shopsh_collection active" type="button" onclick="javascript:cancelfavorite(this,'+json.favoriteId+',1);"><span class="wst-shopsh_contactimg"><img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/storeCollect.png"></span><span>已收藏店铺</span>';
                setTimeout(function(){
                    $('.wst-follow').removeAttr('disabled').html(html);
                }, 2000);
    		}else{
    			var	html = '<button class="wst-fav_but" type="button" onclick="javascript:cancelfavorite(this,'+json.favoriteId+');"><img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/collect.png"></button>';
                setTimeout(function(){
                    $(obj).removeAttr('disabled').removeClass('active');
                    $('#goodsfa').html(html);
                    $('#goodsfa2').html(html);
                }, 2000);
    		}
        }else{
        	WST.msg('收藏失败，请重试','warn');
    	    $(obj).removeAttr('disabled').removeClass('active');
        }
    });
}
//取消关注商品或店铺
function cancelfavorite(obj,favoriteId,favoriteType){
    var param = {};
    param.favoriteId = favoriteId;
    $(obj).attr('disabled', 'disabled').addClass('active');
    $.post(ThinkPHP.U('WeChat/Favorites/cancelFavorite'), param, function(data){
        var json = WST.toJson(data);
        if(json.status == 1){
        	WST.msg('取消成功','success');
    		if(favoriteType == 1){
    			var shopId=$('#shopId').val();
    			var html = '<button class="ui-btn-s wst-shopsh_collection" type="button" onclick="javascript:favorite(this,'+shopId+',1);"><span class="wst-shopsh_contactimg"><img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/storeCollect.png"></span><span>收藏店铺</span>';
                setTimeout(function(){
                    $('.wst-follow').removeAttr('disabled').removeClass('active').html(html);
                }, 2000);
    		}else{
    			var goodsId=$('#goodsId').val();
    			var	html = '<button class="wst-fav_but" type="button" onclick="javascript:favorite(this,'+goodsId+');"><img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/collect_click.png"></button>';
                setTimeout(function(){
                    $(obj).removeAttr('disabled').removeClass('active');
                    $('#goodsfa').html(html);
                    $('#goodsfa2').html(html);
                }, 2000);
    		}
        }else{
        	WST.msg('取消失败','warn');
    	    $(obj).removeAttr('disabled').removeClass('active');
        }
    });
}
//只能輸入數字
WST.isNumberKey = function(evt){
	var charCode = (evt.which) ? evt.which : event.keyCode;
	if (charCode > 31 && (charCode < 48 || charCode > 57)){
		return false;
	}else{		
		return true;
	}
}
//隐藏显示
WST.showHide = function(t,str){
	var s = str.split(',');
	if(t){
		for(var i=0;i<s.length;i++){
		   $(s[i]).show();
		}
	}else{
		for(var i=0;i<s.length;i++){
		   $(s[i]).hide();
		}
	}
	s = null;
}
//清空值
WST.emptyData = function(t,str){
	var s = str.split(',');
	if(t){
		for(var i=0;i<s.length;i++){
		   $(s[i]).empty();
		}
	}else{
		for(var i=0;i<s.length;i++){
		   $(s[i]).val('');
		}
	}
	s = null;
}
//提示信息
WST.msg = function(content,type,stayTime){
	if(!stayTime){
		stayTime = '1200';
	}
	var el = $.tips({content:content,type:type,stayTime:stayTime});
    return  el;
}
//提示对话框
WST.dialog = function(content,event){
	$("#wst-dialog-con").html(content);
	$("#wst-event2").attr("onclick","javascript:"+event);
	$("#wst-dialog1").dialog("show");
}
WST.dialogCode = function(event){
	$("#wst-event22").attr("onclick","javascript:"+event);
	$("#wst-dialog2").dialog("show");
}
WST.dialogPwd = function(event){
	$("#wst-event32").attr("onclick","javascript:"+event);
	$("#wst-dialog3").dialog("show");
	$('#wst-pwd-input').focus();
}
WST.dialogShare = function(){
	$("#wst-dialog6").dialog("show");
}
//隐藏对话框
WST.dialogHide = function(event){
	$("#wst-dialog"+event).dialog("hide");
	if(event==2){
		$('#smsVerfy').val('');
	}else if(event==3){
		WST.emptyData('','#wst-pwd-input,.wst-pwd-input');
	}
}
//刷新验证码
function getVerify(targetId){
    $('#'+targetId).attr('src',WST.U('WeChat/Users/getVerify', 'rnd='+Math.random()));
}
//变换选中框的状态
function changeIconStatus(obj, toggle, status){
    if(toggle==1){
        if( obj.attr('class').indexOf('ui-icon-unchecked-s') > -1 ){
            obj.removeClass('ui-icon-unchecked-s').addClass('ui-icon-success-block wst-active');
        }else{
            obj.removeClass('ui-icon-success-block wst-active').addClass('ui-icon-unchecked-s');
        }
    }else if(toggle==2){
        if(status == 'wst-active'){
            obj.removeClass('ui-icon-unchecked-s').addClass('ui-icon-success-block wst-active');
        }else{
            obj.removeClass('ui-icon-success-block wst-active').addClass('ui-icon-unchecked-s');
        }
    }
}

//底部的tab
function initFooter(tab){
    var homeImage = (tab=='home') ? 'home-active' : 'home';
    var brandImage = (tab=='brand') ? 'brand-active' : 'brand';
    var categoryImage = (tab=='category') ? 'category-active' : 'category';
    var cartImage = (tab=='cart') ? 'cart-active' : 'cart';
    var usersImage = (tab=='users') ? 'users-active' : 'users';
    $('#home').append('<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/'+homeImage+'.png" style="width:34px;height:21px;margin-top:3px;"><span class="wst-menu_word wst-'+homeImage+'_word">首页</span>');
    $('#brand').append('<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/'+brandImage+'.png" style="width:34px;height:21px;margin-top:3px;"><span class="wst-menu_word wst-'+brandImage+'_word">品牌团</span>');
    $('#category').append('<img class="wst-footer-img" src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/'+categoryImage+'.png" style="width:34px;height:21px; margin-left:2px; margin-top:3px;"><span class="wst-menu_word wst-'+categoryImage+'_word">分类</span>');
    $('#cart').prepend('<img class="wst-footer-img cart-img" src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/'+cartImage+'.png" style="width:34px;height:21px;margin-top:3px;"><span class="wst-menu_word wst-'+cartImage+'_word">购物车</span>');
    $('#users').append('<img class="wst-footer-img" src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/'+usersImage+'.png" style="width:34px;height:21px;margin-left:2px;margin-top:3px;"><span class="wst-menu_word wst-'+usersImage+'_word">我的</span>');
}
var currentPosition,timer;
$(document).ready(function(){
	echo.init();//图片懒加载
	  if(WST.communityName !== ''){
	 		$('#community_display').html(WST.communityName);
	  }
    // 滚动到顶部	
    $(window).scroll(function(){
        if( $(window).scrollTop() > 100 ){
            $('#toTop').show();
        }else{
            $('#toTop').hide();
        }
    });
    $('#toTop').on('click', function() {
    	timer=setInterval("runToTop()",1);
	}); 
});
function runToTop(){  
	currentPosition=document.documentElement.scrollTop || document.body.scrollTop; 
	currentPosition-=20;
	if(currentPosition>0)  
	{  
	window.scrollTo(0,currentPosition);  
	}  
	else  
	{  
	window.scrollTo(0,0);  
	clearInterval(timer); 
	}  
}