//设置所在城市
function setCity(areaId, areaName){
        var el=$.loading({
            content:'正在切换城市......',
        })
        setTimeout(function(){
            el.loading("hide");
        },500);
        var param = {};
        param.areaId2 = areaId;
        param.areaName2 = areaName;
        $.post(ThinkPHP.U('WeChat/Index/setLocationSessionAndCookie'), param, function(data){
            if(data==1){
                location.href = ThinkPHP.U('WeChat/Index/community');
            }else{
                var el=$.loading({
                    content:'加载中...',
                })
               setTimeout(function(){
            	   el.loading("hide");
                   WST.msg('切换失败，请重试','warn');
               },500);
                setTimeout(function(){
                    location.href = ThinkPHP.U('WeChat/Index/community');
                },2000);
            }
        });
}
//设置所在社区
function setCommunity(areaId3,communityId,communityName){
    var el=$.loading({
        content:'正在切换社区......',
    })
    setTimeout(function(){
        el.loading("hide");
    },500);
    var param = {};
    param.areaId3 = areaId3;
    param.communityId = communityId;
    param.communityName = communityName;
    $.post(ThinkPHP.U('WeChat/Index/setCommunitySessionAndCookie'), param, function(data){
        if(data==1){
            location.href = ThinkPHP.U('WeChat/Index/index');
        }else{
            var el=$.loading({
                content:'加载中...',
            })
            setTimeout(function(){
            	el.loading("hide");
                WST.msg('切换失败，请重试','warn');
            },500);
            setTimeout(function(){
                location.href = ThinkPHP.U('WeChat/Index/index');
            },2000);
        }
    });
}