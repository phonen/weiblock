//列表
function getFavoriteList(){
	$('#Load').show();
    loading = true;

    var param = {};
    param.currPage = Number( $('#currPage').val() )+1;
    param.favoriteType = $('#favoriteType').val();
    param.favoriteKey = $('#favoriteSearch').val();

    $.post(ThinkPHP.U('WeChat/Favorites/getFavoritesList'), param, function(data){
        var json = WST.toJson(data);
        var str = '';
        if(json && json.root && json.root.length>0){
            for(var i=0; i<json.root.length; i++){
            	str += '<div class="ui-row-flex wst-fav_list" id="favorites-'+json.root[i].favoriteId+'">';
                str += '<div class="ui-col"><div class="wst-fav_listl">';
                if(param.favoriteType == 1){
                    str += '<a href="javascript:void(0);" onclick="javascript:goToShopHome('+json.root[i].shopId+','+json.root[i].isSelf+');">';
                }else{
                    str += '<a href="javascript:void(0);" onclick="javascript:goToGoodsDetails('+json.root[i].goodsId+');">';
                }
                str += '<img src="'+ ThinkPHP.DEFAULT_IMG +'" data-echo="'+ ThinkPHP.ROOT +'/'+json.root[i].favoriteImg+'">';
                str += '</a>';
                str += '</div></div>';
                str += '<div class="ui-col ui-col-2 wst-fav_listr">';
                if(param.favoriteType == 1){
                    str += '<a href="javascript:void(0);" onclick="javascript:goToShopHome('+json.root[i].shopId+','+json.root[i].isSelf+');">';
                }else{
                    str += '<a href="javascript:void(0);" onclick="javascript:goToGoodsDetails('+json.root[i].goodsId+');">';
                }
                str += '<p class="ui-nowrap ui-whitespace wst-fav_listr1">'+json.root[i].favoriteName+'</p>';
                str += '</a>';
                if(param.favoriteType == 0){
                    str += '<p class="ui-whitespace wst-fav_listr2">¥'+json.root[i].shopPrice+'<span>';
                    str += '<img src="'+ ThinkPHP.ROOT +'/Apps/WeChat/View/default/images/car.png" onclick="javascript:addGoodsCart('+json.root[i].goodsId+','+json.root[i].goodsAttrId+');"></span>';
                    str += '</p>';
                }
                str += '<p class="ui-whitespace">';
                str += '<button type="button" class="ui-btn-s" onclick="javascript:toCancel('+json.root[i].favoriteId+');">取消关注</button>';
                str += '</p>';
                str += '</div></div>';
            }
            $('#currPage').val( json.currPage );
            $('#totalPage').val( json.totalPage );
        }else{
        	str += '<ul class="ui-row-flex wst-flexslp">';
        	str += '<li class="ui-col ui-flex ui-flex-pack-center">';
        	str += '<p>没有相关信息</p>';
        	str += '</li>';
        	str += '</ul>';
        }
        $('#favorites-list').append(str);
        loading = false;
        $('#Load').hide();
        echo.init();//图片懒加载
    });
}
//是否取消关注
function toCancel(favoriteId){
    favoriteIdForCancel = favoriteId;
    if( $('#favoriteType').val() == 1 ){
    	WST.dialog('您确定取消关注该店铺吗？','cancelFavorite()');
    }else{
    	WST.dialog('您确定取消关注该商品吗？','cancelFavorite()');
    }
}

//取消关注
function cancelFavorite(){
    var param = {};
    param.favoriteId = favoriteIdForCancel;
    $.post(ThinkPHP.U('WeChat/Favorites/cancelFavorite'), param, function(data){
        var json = WST.toJson(data);
        if(json.status == 1){
        	WST.msg('取消成功','success');
            $('#favorites-'+favoriteIdForCancel).fadeOut(1000);
            setTimeout(function(){
                $('#favorites-'+favoriteIdForCancel).remove();
            },1000);
        }else{
        	WST.msg('取消失败','warn');
        }
        WST.dialogHide(1);
    });
}
var currPage = totalPage = 0;
var loading = false;
var favoriteIdForCancel = 0;//拟取消的ID
$(document).ready(function(){
    initFooter('users');
    getFavoriteList();
    
    $('.favorites_choice').click(function(){
        var favoriteType = $(this).attr('favoriteType');
        $('#favoriteType').val(favoriteType);
        if(favoriteType == 1){
            var searchTips = '搜索店铺';
        }else{
            var searchTips = '搜索商品';
        }
        $('#favoriteSearch').attr('placeholder', searchTips);
        $(this).addClass('current').siblings('li.favorites_choice').removeClass('current');
        $('#currPage').val(0);
        $('#favorites-list').html('');
        getFavoriteList();
    });
    
    $('#toSearch').click(function(){
        $('#favorites-list').html('');
        getFavoriteList();
    });
    $(window).scroll(function(){  
        if (loading) return;
        if ((5 + $(window).scrollTop()) >= ($(document).height() - $(window).height())) {
            currPage = Number( $('#currPage').val() );
            totalPage = Number( $('#totalPage').val() );
            if( totalPage > 0 && currPage < totalPage ){
                getFavoriteList();
            }
        }
    });
});