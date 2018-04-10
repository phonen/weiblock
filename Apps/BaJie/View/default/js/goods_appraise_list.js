//排序条件
function orderCondition(obj,type){
    $(obj).addClass('wst-selected').siblings('.wst-appraise_heads').removeClass('wst-selected');
    $('#type').val(type);
    $('#currPage').val('0');//当前页归零
    $('#appraise-list').html('');
    getGoodsAppraiseList();
}

//商品评价列表
function getGoodsAppraiseList(){
	$('#Load').show();
    loading = true;

    var param = {};
    param.currPage = Number( $('#currPage').val() ) + 1;
    param.goodsId = $('#goodsId').val();
    param.type = $('#type').val();

    $.post(ThinkPHP.U('WeChat/Goods/getAppraisesList'), param, function(data){
        var json = WST.toJson(data);
        var ahtml = '';
        if(json && json.root && json.root.length>0){
            for(var i=0; i<json.root.length; i++){
            	ahtml += '<div class="ui-row-flex ui-whitespace wst-appraise_info">';
                ahtml += '<div class="ui-col ui-col">用户：'+json.root[i].loginName+'</div>';
                ahtml += '<div class="ui-col ui-col"><span class="wst-appraise_infor">'+json.root[i].createTime+'</span></div>';
                ahtml += '</div>';
                ahtml += '<div class="ui-whitespace wst-appraise_info1">';
                ahtml += '<p class="ui-txt-default">商品评分：';
                for(var j=0; j<json.root[i].goodsScore; j++){
                	ahtml += '<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars1.png">';
                }
                ahtml += '</p>';
                ahtml += '<p class="ui-txt-default">服务评分：';
                for(var j=0; j<json.root[i].serviceScore; j++){
                	ahtml += '<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars1.png">';
                }
                ahtml += '</p>';
                ahtml += '<p class="ui-txt-default">时效评分：';
                for(var j=0; j<json.root[i].timeScore; j++){
                	ahtml += '<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/stars1.png">';
                }
                ahtml += '</p>';
                ahtml += '</div>';
                ahtml += '<div class="ui-whitespace wst-appraise_info2">';
                ahtml += '<p class="ui-txt-default">'+json.root[i].content+'</p>';
                ahtml += '</div>';
            }
            $('#currPage').val(json.currPage);
            $('#totalPage').val(json.totalPage);
        }else{
        	ahtml += '<ul class="ui-row-flex wst-flexslp">';
        	ahtml += '<li class="ui-col ui-flex ui-flex-pack-center">';
        	ahtml += '<p>对不起，没有相关评价。</p>';
        	ahtml += '</li>';
        	ahtml += '</ul>';
        }
        $('#appraise-list').append(ahtml);
        loading = false;
        $('#Load').hide();
    });
}

var currPage = totalPage = 0;
var loading = false;
$(document).ready(function(){
    initFooter('home');
    getGoodsAppraiseList();

    $(window).scroll(function(){  
        if (loading) return;
        if ((5 + $(window).scrollTop()) >= ($(document).height() - $(window).height())) {
            currPage = Number( $('#currPage').val() );
            totalPage = Number( $('#totalPage').val() );
            if( totalPage > 0 && currPage < totalPage ){
                getGoodsAppraiseList();
            }
        }
    });
});