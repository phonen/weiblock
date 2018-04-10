jQuery.noConflict();
//店铺二级分类
var width=jQuery("#classifysh").css('width');
$("#classifysh").css('left',-width);
	//内部窗口w宽，h高
	var w=window.innerWidth
	|| document.documentElement.clientWidth
	|| document.body.clientWidth;
	var h=window.innerHeight
	|| document.documentElement.clientHeight
	|| document.body.clientHeight;
	jQuery('.wst-shophbg').css("height",h);
function shopClassifyIN(){
	var homepage=jQuery('#homepage').val();
	if(homepage==0){
	jQuery('#classifysh').animate({"left": 0}, 500);
	jQuery('#shophbg').show();
	jQuery('.wst-shop_cartl1').html('<span class="wst-shop_cartlword" onclick="javascript:shopClassifyOUT();"><img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/goodsClass.png"><span class="wst-shop_cartlwords">商品分类</span></span>');
	}
}
document.addEventListener('touchmove', function(event) {
    //判断条件,条件成立才阻止背景页面滚动,其他情况不会再影响到页面滚动
    if(!jQuery("#shophbg").is(":hidden")){
        event.preventDefault();
    }
})
function shopClassifyOUT(){
	var homepage=jQuery('#homepage').val();
	if(homepage==0){
	width=parseInt(width)+2;
	jQuery('#classifysh').animate({'left': -width}, 500);
	jQuery('#shophbg').hide();
	jQuery('.wst-shop_cartl1').html('<span class="wst-shop_cartlword" onclick="javascript:shopClassifyIN();"><img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/goodsClass.png"><span class="wst-shop_cartlwords">商品分类</span></span>');
	}
}
function shopgetcatId(obj,catId){
	jQuery('#catId').val(catId);
	jQuery('#currPage').val('0');//当前页归零
	jQuery('#goods-list').html('');
    shopClassifyOUT();
    getGoodsList();
}
//店铺三级分类
function shopClassifyIN2(kid){
	if($("#classifys"+kid).css("display")=='none'){
		jQuery("#classifys"+kid).slideDown(200);
	}else{
		jQuery("#classifys"+kid).slideUp(200);
	}
}
//店铺主页
function shopHome(){
	jQuery('#shop_goods').hide();
	jQuery('.returnshop_goods').hide();
	jQuery('#classifysh').hide();
	jQuery('#shop_home').fadeIn(500);
	shopClassifyOUT();
	jQuery('#homepage').val('1');
	jQuery('.wst-shop_cartl2').html('<span class="wst-shop_cartlword" onclick="javascript:shopGood();"><img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/goodsList.png"><span class="wst-shop_cartlwords">商品列表</span></span>');
}
function shopGood(){
	jQuery('#shop_goods').fadeIn(500);
	jQuery('.returnshop_goods').fadeIn(500);
	jQuery('#classifysh').show();
	jQuery('#shop_home').hide();
	jQuery('#homepage').val('0');
	jQuery('.wst-shop_cartl2').html('<span class="wst-shop_cartlword" onclick="javascript:shopHome();"><img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/goodsList.png"><span class="wst-shop_cartlwords">店铺介绍</span></span>');
}