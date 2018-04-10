//排序条件
function orderCondition(obj,desc){
    var classContent = $(obj).attr('class');
    var status = $(obj).attr('status');
    var theSiblings = $(obj).siblings('.sorts');
    $(obj).addClass('wst-current');
    theSiblings.removeClass('wst-current').attr('status','down');
    if(classContent.indexOf('wst-current')==-1){
        $(obj).children('.spot').show();
        $(obj).children('.arrow-up').show();
        $(obj).children('.arrow_spot').hide();
        theSiblings.children('.arrows').hide();
        theSiblings.children('.arrow_spot').show();
        theSiblings.children('.spot').hide();
        
    }
    if(status.indexOf('down')>-1){
        if(classContent.indexOf('wst-current')==-1){
            $(obj).children('.arrow-down').show();
            $(obj).children('.arrow-up').hide();
            $('#shopOrder').val('0');
        }else{
            $(obj).children('.arrow-down').hide();
            $(obj).children('.arrow-up').show();
            $(obj).attr('status','up');
            $('#shopOrder').val('1');
        }
    }else{
        $(obj).children('.arrow-down').show();
        $(obj).children('.arrow-up').hide();
        $(obj).attr('status','down');
        $('#shopOrder').val('0');
    }
    $('#shopOrderby').val(desc);//排序条件
    $('#currPage').val('0');//当前页归零
    $('#shops-list').html('');
    getShopList();
}
//分类选择
function getcatId(catId){
	$('#catId').val(catId);
    $('#currPage').val('0');//当前页归零
    $('#shops-list').html('');
	getShopList();
}

//商家列表
function getShopList(){
	$('#Load').show();
    loading = true;
    var param = {};
    param.pageSize = 10;
    param.currPage = Number( $('#currPage').val() ) + 1;
    
    param.goodsCatId1 = $('#goodsCatId1').val();
    param.goodsCatId2 = $('#goodsCatId2').val();
    param.goodsCatId3 = $('#goodsCatId3').val();
    param.desc = $('#shopOrderby').val();
    param.descType = $('#shopOrder').val();
    param.key = $('#searchKey').val();
    param.catId = $('#catId').val();
    $.post(ThinkPHP.U('WeChat/Shops/getShopsList'), param, function(data){
        var json = WST.toJson(data);
        var shtml = '';
        if(json && json.root && json.root.length>0){
	            for(var i=0; i<json.root.length; i++){
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
	                shtml += '</a></ul>';
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
        $('#Load').hide();
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
});