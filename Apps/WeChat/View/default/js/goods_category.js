var catLeft,catRight;

$(document).ready(function(){
	initFooter('category');
//内部窗口w宽，h高
var w=window.innerWidth
|| document.documentElement.clientWidth
|| document.body.clientWidth;
var h=window.innerHeight
|| document.documentElement.clientHeight
|| document.body.clientHeight;
	var o=document.getElementById('ui-scrollerl');
	var a= h-50;
	o.style.height=a+'px';
    var scroll = new fz.Scroll('.ui-scrollerl', {
        scrollY: true,
        slidingY: 'y'
    });
});

function showRight(obj, index){
	$(obj).addClass('wst-goodscate_selected').siblings('#goodscate').removeClass('wst-goodscate_selected');
	$('.goodscate1').eq(index).show().siblings('.goodscate1').hide();
}

//商品列表页
function getGoodsList(goodsCatId, goodsCat){
	var param = '';
	if(goodsCat==2){
		param += 'goodsCatId2=';
	}else if(goodsCat==3){
		param += 'goodsCatId3=';
	}
	param += goodsCatId;
	location.href = ThinkPHP.U('WeChat/Goods/index',param);
}