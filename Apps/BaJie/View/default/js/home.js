//分类商家列表页
function goToShopsList(goodsCatId){
	location.href = ThinkPHP.U('WeChat/Shops/goToShops', 'goodsCatId1='+goodsCatId);
}
//商家列表页
function goToShops(){
	location.href = ThinkPHP.U('WeChat/Shops/goToShops');
}
//商家列表
function getShopList(){
    loading = true;
    var param = {};
    param.pageSize = 10;
    param.currPage = 1;
    param.desc = 2;
    param.descType = 1;
    param.key = '';
    param.catId = 0;
    $.post(ThinkPHP.U('WeChat/Shops/getShopsList'), param, function(data){
        var json = WST.toJson(data);
        var shtml = '';
        if(json && json.root && json.root.length>0){
            if(json.root.length>=10){
            	var numberi=10;
            }else{
            	var numberi=json.root.length;
            }
	            for(var i=0; i<numberi; i++){
	                shtml += '<a href="javascript:void(0);" onclick="goToShopHome('+json.root[i].shopId+','+json.root[i].isSelf+');">';
	                shtml += '<ul class="ui-row wst-rowsl">';
	                shtml += '<li class="ui-col ui-col-25">';
	                if(json.root[i].shopAtive==1){
	                	shtml += '<div class="wst-collsl ui-tag-limit">';
	                }else{
	                	shtml += '<div class="wst-collsl ui-tag-free">';
	                }
	                shtml += '<img src="'+ ThinkPHP.DEFAULT_IMG +'" data-echo="'+ ThinkPHP.ROOT +'/'+json.root[i].shopImg+'">';
	                shtml += '</div>';
	                shtml += '</li>';
	                shtml += '<li class="ui-col ui-col-75">';
	                shtml += '<div class="wst-colrsl">';
	                shtml += '<span class="wst-textsl">'+json.root[i].shopName+'</span></br>';
	                shtml += '<span><span class="wst-sh_colour">'+json.root[i].deliveryStartMoney+'元</span>起送，配送费<span class="wst-sh_colour">'+json.root[i].deliveryMoney+'元</span>，';
	                shtml += '人均<span class="wst-sh_colour">'+json.root[i].avgeCostMoney+'</span>元，平均<span class="wst-sh_colour">'+json.root[i].deliveryCostTime+'分</span>送达</span>';
	                shtml += '<div class="ui-row-flex">';
	                shtml += '<div class="ui-col ui-col-3 wst-rowslr">';
	                shtml += '<div class="wst-imgxx">';
	                for(var j=1; j<6; j++){
	                    if(j <= json.root[i].score){
	                        shtml += '<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars1.png">';
	                    }else{
	                        shtml += '<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars2.png">';
	                    }
	                }
	                shtml += '</div>';
	                shtml += '</div>';
	                var distance = (json.root[i].userDistance) ? json.root[i].userDistance : '';
	                shtml += '<i><span class="wst-sh_distance">距离：'+distance+'km</span></i>';
	                shtml += '</div>';
	                shtml += '</div></li>';
	                if(json.root[i].isDistributAll==1){
	                	shtml += '<i class="give">全国配送</i>';
	                }
	                shtml += '</ul></a>';
	            	}
            $('#currPage').val(json.currPage);
            $('#totalPage').val(json.totalPage);
        }else{
        	shtml += '<ul class="ui-row-flex wst-flexslp">';
        	shtml += '<li class="ui-col ui-flex ui-flex-pack-center">';
        	shtml += '<p>对不起，附近没有商家。</p>';
        	shtml += '</li>';
        	shtml += '</ul>';
        }
        $('#shops-list').append(shtml);
        loading = false;
        echo.init();//图片懒加载
    });
}
var currPage = totalPage = 0;
var loading = false;
$(document).ready(function(){
	initFooter('home');
    getShopList();

    $(window).scroll(function(){  
        if (loading) return;
        if ((5 + $(window).scrollTop()) >= ($(document).height() - $(window).height())) {
            currPage = Number( $('#currPage').val() );
            totalPage = Number( $('#totalPage').val() );
            if( totalPage > 0 && currPage < totalPage ){
                getShopList();
            }
        }
    });
	if(WST.communityName == ''){
 		communityList();
    }
	//广告
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
	var h= w*2.3/5;
		var o=$('.ui-slider').css("padding-top",h);
	    var scroll = new fz.Scroll('.ui-slider', {
	        scrollY: true
	    });
});