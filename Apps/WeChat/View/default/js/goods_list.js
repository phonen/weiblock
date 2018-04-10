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

//获取商品列表
function getGoodsList(){
	$('#Load').show();
    loading = true;
    var param = {};
    param.pageSize = 10;
    param.currPage = Number( $('#currPage').val() ) + 1;
    
    param.goodsCatId1 = $('#goodsCatId1').val();
    param.goodsCatId2 = $('#goodsCatId2').val();
    param.goodsCatId3 = $('#goodsCatId3').val();
    param.brandId = $('#brandId').val();
    param.desc = $('#goodsOrderby').val();
    param.descType = $('#goodsOrder').val();
    param.key = $('#searchKey').val();
    $.post(ThinkPHP.U('WeChat/Goods/getGoodsList'), param, function(data){
        var json = WST.toJson(data);
        var ghtml = '';
        if(json && json.root && json.root.length>0){
            for(var i=0; i<json.root.length; i++){
            	ghtml += '<ul class="ui-row wst-rowg">';
            	ghtml += '<a href="javascript:void(0);" onclick="javascript:goToGoodsDetails('+json.root[i].goodsId+');">';
            	ghtml += '<li class="ui-col ui-col-25"><div class="ui-collsl"><img src="'+ ThinkPHP.DEFAULT_IMG +'" data-echo="'+ ThinkPHP.ROOT +'/'+json.root[i].goodsThums+'"></div></li>';
            	ghtml += '<li class="ui-col ui-col-75">';
            	ghtml += '<div class="wst-colrsl">';
            	ghtml += '<span class="ui-nowrap-flex wst-ui-colrslword">'+json.root[i].shopName+'&nbsp;&nbsp;'+json.root[i].goodsName+'</span>';
            	ghtml += '<div class="ui-row-flex">';
            	ghtml += '<div class="ui-col-3 wst-icon-trendg"><div class="imgxx">';
                for(var j=1; j<6; j++){
                    if(j <= json.root[i].score){
                    	ghtml += '<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars1.png">';
                    }else{
                    	ghtml += '<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars2.png">';
                    }
                }
            	ghtml += '</br></div>';
            	ghtml += '<span class="wst-icon-trendgt">¥'+json.root[i].shopPrice+'</span></br>';
                ghtml += '</div>';
                ghtml += '<div class="ui-col ui-col-2 ui-whitespace wst-carg">';
                ghtml += '<a href="javascript:void(0);" onclick="javascript:addGoodsCart('+json.root[i].goodsId+','+json.root[i].goodsAttrId+','+json.root[i].goodsStock+');">';
                ghtml += '<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/car.png">';
                ghtml += '</a>';
                var distance = (json.root[i].userDistance) ? json.root[i].userDistance : '';
                ghtml += '<i><span>'+distance+'km</span></i>';
                ghtml += '</div>';
                ghtml += '</div></div></li></a></ul>';
            }
            $('#currPage').val(json.currPage);
            $('#totalPage').val(json.totalPage);
        }else{
        	ghtml += '<ul class="ui-row-flex wst-flexslp">';
        	ghtml += '<li class="ui-col ui-flex ui-flex-pack-center">';
        	ghtml += '<p>对不起，没有相关商品。</p>';
        	ghtml += '</li>';
        	ghtml += '</ul>';
        }
        $('#goods-list').append(ghtml);
        loading = false;
        $('#Load').hide();
        echo.init();//图片懒加载
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
});