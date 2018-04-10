//腾讯地图
var citylocation,communityname= null;
//调用城市服务信息
var  citylocation = new qq.maps.CityService({
        complete : function(results){
            a = results.detail.name;//区县
            b = results.detail.latLng;//纬经度
            c = results.detail.level;
            d = results.detail.detail;//区县/城市/省份/国家
            if(d){
            	wstGetPositionComplete(d);
            }
        }
    });

//地理位置信息
function geolocation_latlng(){
  var wstLatitude= WST.wstRealLatitude;//纬度
  var wstLongitude= WST.wstRealLongitude;//经度
    //设置经纬度信息
    var latLng = new qq.maps.LatLng(wstLatitude,wstLongitude);
    //调用城市经纬度查询接口实现经纬查询
    citylocation.searchCityByLatLng(latLng);
}
//获取当前地理位置信息的回调函数
function wstGetPositionComplete(data){
  var City = data.split(",",4);
  var currentCity0 = City[0];//区县
  var currentCity = City[1];//城市
  var currentCity2 = City[2];//省份
    var param = {};
    param.action = 'location';
    param.key = currentCity;
    $.post(ThinkPHP.U('WeChat/Index/city'), param, function(data){//检查系统里是否有该城市
		    var wstLatitude= WST.wstRealLatitude;//纬度
		    var wstLongitude= WST.wstRealLongitude;//经度
        if(data){
            if( WST.areaId2 == '' ){
                var param = {};
                param.areaId2 = data.areaId;
                param.areaName2 = data.areaName;
                param.wstLatitude = wstLatitude;
                param.wstLongitude = wstLongitude;
				setLocationSessionAndCookie(param,data.areaId,data.areaName);
            }
        	if(data.areaId != WST.areaId2){
        		WST.dialog('当前定位到'+data.areaName+',需不需要切换城市？','setCity('+data.areaId+', \''+data.areaName+'\')');
        	}
        }
    });
}
//搜索社区
function Searchcommunity(){
    $('#areaId4').html('');
	var areaId2=WST.areaId2;
    var key = $('#community-search').val();
    if(key==''){
    	WST.msg('请输入要搜索的社区名称','info');
		return false;
    }
    var param = {};
    param.key=key;
    param.areaId2=areaId2;
    $.post(ThinkPHP.U("WeChat/Communitys/searchCommunity"), param, function(data) {
        var json = WST.toJson(data);
        var str = '';
        if(json.status==1 && json.list){
        	for(var i in json.list){
        		str += '<li onclick="javascript:setCommunity('+json.list[i].areaId3+','+ json.list[i].communityId +',\''+ json.list[i].communityName +'\');"><div class="ui-list-info">';
        		str += '<h5 class="ui-nowrap">'+ json.list[i].communityName +'</h5>';
        		str += '</div></li>';
        	}
            $('#areaId4').append(str);
        }
        data = json = null;
    });
}
//设置跟位置信息有关的session和cookie
function setLocationSessionAndCookie(param,areaId,areaName){
    $.post(ThinkPHP.U("WeChat/Index/setLocationSessionAndCookie"), param, function(data) {
        if (data == 1) {
        	getByistance();
        	$('.wst-city_headt').html('');
        	var html='<span>当前定位城市：</span><button class="ui-btn ui-btn-danger"  onclick="setCity('+areaId+',\''+areaName+'\);" >'+areaName+'</button>';
        	$('.wst-city_headt').html(html);
        }
    });
}
//根据区县获取社区列表
function getCommunity(areaId3){
	  $('#areaId4').empty();
    $.post(ThinkPHP.U('WeChat/Communitys/getByDistrict'), {areaId3:areaId3}, function(data){
        var json = WST.toJson(data);
        var str = '';
        if(json.status==1 && json.list){
        	for(var i in json.list){
        		str += '<li onclick="javascript:setCommunity('+areaId3+','+json.list[i].communityId+',\''+json.list[i].communityName+'\');"><div class="ui-list-info">';
        		str += '<h5 class="ui-nowrap">'+ json.list[i].communityName +'</h5>';
        		str += '</div></li>';
        	}
            $('#areaId4').append(str);
        }
        data = json = null;
    });
}
//我们猜
function getByistance(){
	$.post(ThinkPHP.U('WeChat/Communitys/getByistance'), {}, function(data){
		var json = WST.toJson(data);
		if(json.communityId){
    		var communityId=json.communityId;
    		var communityName=json.communityName;
			var areaId3=json.areaId3;
			var communityNames=json.areaName2+json.areaName3+communityName;
			var html= '<span class="wst-community_headt1">我们猜您在：</span><span class="wst-community_headt2" onclick="javascript:setCommunity('+ areaId3 +',\''+ communityId +'\',\''+ communityName +'\');">'+communityNames+'</span>';
			$('#community_name').html(html);
			if(WST.communityName == ''){
				//设置默认地区
			    var param = {};
			    param.areaId3 = areaId3;
			    param.communityId = communityId;
			    param.communityName = communityName;
			    $.post(ThinkPHP.U('WeChat/Index/setCommunitySessionAndCookie'), param, function(data){
			    });
		    }
		}
	});
}
$(document).ready(function(){
	if( WST.wstRealLatitude !== '' || WST.wstRealLongitude !== ''){
		geolocation_latlng();
   }
	if(WST.communityId !== ''){
		getByistance();
	}
	if(WST.areaId2 !== ''){
		var areaId2=WST.areaId2;
		if(WST.areaId3 !== ''){
			var areaId3=WST.areaId3;
		}else{
			var	areaId3='';
		}
	    $.post(ThinkPHP.U('WeChat/Areas/getDistricts'), {cityId:areaId2}, function(data){//获取区县
	    	if(!data){return false;}
	        var json = WST.toJson(data);
	        var str = '';
	        if(!areaId3){
	        	var areaId3 = json[0].areaId;//默认取第一个区县
	        }
	        if(json.length>0){
	            for(var i=0; i<json.length; i++){
	                str += '<option value="'+ json[i].areaId +'" ';
	                str += (json[i].areaId==areaId3) ? 'selected' : '';
	                str += '>'+ json[i].areaName +'</option>';
	            }
	            $('#areaId3').append(str);
	            $.post(ThinkPHP.U('WeChat/Communitys/getByDistrict'), {areaId3:areaId3}, function(data){//获取社区
	                var json = WST.toJson(data);
	                var str = '';
	                if(json.status==1 && json.list){
	                	for(var i in json.list){
	                		if(i==0){
		                		str += '<li onclick="javascript:setCommunity('+areaId3+','+ json.list[i].communityId +',\''+ json.list[i].communityName +'\');"><div class="ui-list-info">';
		                		str += '<h5 class="ui-nowrap">'+ json.list[i].communityName +'</h5>';
		                		str += '</div></li>';
	                		}else{
		                		str += '<li onclick="javascript:setCommunity('+areaId3+','+ json.list[i].communityId +',\''+ json.list[i].communityName +'\');"><div class="ui-list-info">';
		                		str += '<h5 class="ui-nowrap">'+ json.list[i].communityName +'</h5>';
		                		str += '</div></li>';
	                		}
	                	}
	                    $('#areaId4').append(str);
	        			$.post(ThinkPHP.U('WeChat/Communitys/getByCountyId'), {areaId3:areaId3}, function(data){//获取区县
	        				var json = WST.toJson(data);
	        				if(json){
	        					if(areaId2!=json.parentId){
	        						$('#areaId4').html('');
	        					}
	        				}
	        			});
	                }
	            });
	        }
	    });
	}
});