var provinceId = $('#provinceId').val();//所在省份的ID
var areaId2=$('#areaIds2').val();//所在城市的ID
var addressIdForDel;//拟删除的地址的id
//返回地址管理
function returnAddress(){
	WST.showHide('','.address_add,#address_add');
	WST.showHide(1,'.address,#address,#footer');
    WST.emptyData('','#username,#cellphone,#phone,#address_detailed');
	emptyOpt('#areaId,#areaId2,#areaId3,#areaId4');
    $('.ui-icon-successct').removeClass('ui-icon-success-block wst-active').addClass('ui-icon-unchecked-s');
}
//新增时获取所在城市的地区信息
function getAddressWhenAddNew(){
    $.post(ThinkPHP.U('WeChat/Areas/getProvinceList'), {}, function(data){//获取省份列表
        var json = WST.toJson(data);
        var str = '';
        if(json.status==1 && json.list){
            for(var i in json.list){
                str += '<option value="'+ json.list[i].areaId +'" ';
                str += (json.list[i].areaId==provinceId) ? 'selected' : '';
                str += '>'+ json.list[i].areaName +'</option>';
            }
            $('#areaId').append(str);
            $.post(ThinkPHP.U('WeChat/Areas/getCityListByProvince'), {provinceId:provinceId}, function(data){//获取城市列表
                var json = WST.toJson(data);
                var str = '';
                if(json.length>0){
                    for(var i=0; i<json.length; i++){
                        str += '<option value="'+ json[i].areaId +'" ';
                        str += (json[i].areaId==areaId2) ? 'selected' : '';
                        str += '>'+ json[i].areaName +'</option>';
                    }
                    $('#areaId2').append(str);

                    $.post(ThinkPHP.U('WeChat/Areas/getDistricts'), {cityId:areaId2}, function(data){//获取区县
                        var json = WST.toJson(data);
                        var str = '';
                        var areaId3=WST.areaId3;//所在城市的ID
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
                                var communityId=WST.communityId;//所在地区的ID
                                if(json.status==1 && json.list){
                                    for(var i in json.list){
                                        str += '<option value="'+ json.list[i].communityId +'" ';
                                        str += (json.list[i].communityId==communityId) ? 'selected' : '';
                                        str += '>'+ json.list[i].communityName +'</option>';
                                    }
                                    $('#areaId4').append(str);
                                }
                            });
                        }
                    });
                }
            });
        }
        data = json = str = null;
    });
}
//新增或编辑收货地址页
function toEditAddress(addressId){
	WST.showHide('','.address,#address,#footer');
	WST.showHide(1,'.address_add,#address_add');
    if(addressId){
    	$('.address_adds').html('修改收货地址').after('<a href="javascript:void(0);" class="wst-btnc useri_nicknamed" onclick="javascript:toDelAddress('+addressId+');">删除</a>');
        var shtml = '<button class="swt-address_but" type="button" onclick="javascript:saveAddress('+addressId+');">保存</button>';
        $('.wst-settlement_r').html(shtml);
        $.post(ThinkPHP.U('WeChat/UsersAddress/getAddressById'), {addressId:addressId}, function(data){
            var addressInfo = WST.toJson(data);
            $('#username').val(addressInfo.userName);
            $('#cellphone').val(addressInfo.userPhone);
            $('#phone').val(addressInfo.userTel);
            $('#address_detailed').val(addressInfo.address);
            if(addressInfo.isDefault==1){
            	$('.ui-icon-successct').removeClass('ui-icon-unchecked-s').addClass('ui-icon-success-block wst-active');
            }
            getAddress(addressInfo);
            addressInfo= null;
        });
    }else{
        var shtml = '<button class="swt-address_but" type="button" onclick="javascript:saveAddress();">保存</button>';
        $('.wst-settlement_r').html(shtml);
    	$('.address_adds').html('新增收货地址');
    	getAddressWhenAddNew();
    }
}
//保存收货地址
function saveAddress(addressId){
    var userName = $('#username').val();
    var userPhone = $('#cellphone').val();
    var userTel = $('#phone').val();
    var areaId = $('#areaId').val();
    var areaId2 = $('#areaId2').val();
    var areaId3 = $('#areaId3').val();
    var communityId = $('#areaId4').val();
    var address = $('#address_detailed').val();
    if( $('.ui-icon-successct').attr('class').indexOf('ui-icon-unchecked-s') > -1 ){
        var isdefaultAddress = 0;//不设为默认地址
    }else{
        var isdefaultAddress = 1;//设为默认地址
    }
    if(userName==''){
    	WST.msg('收货人姓名不能为空','info');
	    $('#username').focus();
        return false;
    }
    if(userPhone=='' && userTel==''){
    	WST.msg('手机号码和固定电话不能同时为空','info');
        return false;
    }
    if(areaId==''){
    	WST.msg('请选择省份','info');
	    $('#areaId').focus();
        return false;
    }
    if(areaId2==''){
    	WST.msg('请选择城市','info');
	    $('#areaId2').focus();
        return false;
    }
    if(areaId3==''){
    	WST.msg('请选择区县','info');
	    $('#areaId3').focus();
        return false;
    }
    if(communityId==''){
    	WST.msg('请选择社区','info');
	    $('#areaId4').focus();
        return false;
    }
    if(address==''){
    	WST.msg('请填写详细地址','info');
	    $('#address_detailed').focus();
        return false;
    }
    var param = {};
    param.addressId = addressId;
    param.userName = userName;
    param.areaId1 = areaId;
    param.areaId2 = areaId2;
    param.areaId3 = areaId3;
    param.communityId = communityId;
    param.userPhone = userPhone;
    param.userTel = userTel;
    param.address = address;
    param.isDefault = isdefaultAddress;
	$('.swt-address_but').addClass("active").attr('disabled', 'disabled');
    $.post(ThinkPHP.U('WeChat/UsersAddress/editAddress'), param, function(data){
        var json = WST.toJson(data);
        if( json.status == 1 ){
        	WST.msg('操作成功','success');
            setTimeout(function(){
                location.href = ThinkPHP.U('WeChat/UsersAddress/index');
            },2000);
        }else{
        	WST.msg('操作失败，请重试','warn');
        	$('.swt-address_but').removeAttr('disabled').removeClass("active");
        }
        data = json = null;
    });
}
//编辑时获取地区信息
function getAddress(addressInfo){
    var areaId = addressInfo.areaId1;
    var areaId2 = addressInfo.areaId2;
    var areaId3 = addressInfo.areaId3;
    var communityId = addressInfo.communityId;
    $.post(ThinkPHP.U('WeChat/Areas/getProvinceList'), {}, function(data){//获取省份列表
        var json = WST.toJson(data);
        var str = '';
        if(json.status==1 && json.list){
            for(var i in json.list){
                str += '<option value="'+ json.list[i].areaId +'" ';
                str += (json.list[i].areaId==areaId) ? 'selected' : '';
                str += '>'+ json.list[i].areaName +'</option>';
            }
            $('#areaId').append(str);

            $.post(ThinkPHP.U('WeChat/Areas/getCityListByProvince'), {provinceId:areaId}, function(data){//获取城市列表
                var json = WST.toJson(data);
                var str = '';
                if(json.length>0){
                    for(var i=0; i<json.length; i++){
                        str += '<option value="'+ json[i].areaId +'" ';
                        str += (json[i].areaId==areaId2) ? 'selected' : '';
                        str += '>'+ json[i].areaName +'</option>';
                    }
                    $('#areaId2').append(str);

                    $.post(ThinkPHP.U('WeChat/Areas/getDistricts'), {cityId:areaId2}, function(data){//获取区县
                        var json = WST.toJson(data);
                        var str = '';
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
                                        str += '<option value="'+ json.list[i].communityId +'" ';
                                        str += (json.list[i].communityId==communityId) ? 'selected' : '';
                                        str += '>'+ json.list[i].communityName +'</option>';
                                    }
                                    $('#areaId4').append(str);
                                }
                            });
                        }
                    });
                }
            });
        }
        data = json = str = null;
    });
}
//根据省份获取城市列表
function getCitysByProvince(provinceId){
    emptyOpt('#areaId2,#areaId3,#areaId4');
    if(provinceId>0){
	    $.post(ThinkPHP.U('WeChat/Areas/getCityListByProvince'), {provinceId:provinceId}, function(data){
	        var json = WST.toJson(data);
	        var str = '';
	        if(json.length>0){
	            for(var i=0; i<json.length; i++){
	                str += '<option value="'+ json[i].areaId +'">'+ json[i].areaName +'</option>';
	            }
	        }
	        $('#areaId2').append(str);
	        data = json = null;
	    });
    }
}

//根据城市获取区县列表
function getDistricts(cityId){
    emptyOpt('#areaId3,#areaId4');
    $.post(ThinkPHP.U('WeChat/Areas/getDistricts'), {cityId:cityId}, function(data){
        if(data){
            var json = WST.toJson(data);
            var str = '';
            if(json && json.length>0){
                for(var i=0; i<json.length; i++){
                    str += '<option value="'+ json[i].areaId +'">'+ json[i].areaName +'</option>';
                }
            }
            $('#areaId3').append(str);
            data = json = null;
        }
    });
}

//根据区县获取社区列表
function getCommunity(areaId3){
    $('#areaId4')
    $.post(ThinkPHP.U('WeChat/Communitys/getByDistrict'), {areaId3:areaId3}, function(data){
        var json = WST.toJson(data);
        var str = '';
        if(json.status==1 && json.list && json.list.length>0){
            for(var i=0; i<json.list.length; i++){
                str += '<option value="'+ json.list[i].communityId +'">'+ json.list[i].communityName +'</option>';
            }
        }
        $('#areaId4').append(str);
        data = json = null;
    });
}
//删除收货地址
function toDelAddress(addressId){
    addressIdForDel = addressId;
	WST.dialog('确定删除吗？','delAddress()');
}
//删除收货地址
function delAddress(){
    $.post(ThinkPHP.U('WeChat/UsersAddress/delAddress'), {addressId:addressIdForDel}, function(data){
        var json = WST.toJson(data);
        if(json.status==1){
        	WST.msg('删除成功','success');
            setTimeout(function(){
                location.href = ThinkPHP.U('WeChat/UsersAddress/index');
            },2000);
        }else{
        	WST.msg('删除失败，请重试','warn');
        }
    	WST.dialogHide(1);
        data = json = null;
    });
}
//设为默认地址
function setToDefault(obj){
    if( $(obj).attr('class').indexOf('ui-icon-unchecked-s') > -1 ){
        $(obj).removeClass('ui-icon-unchecked-s').addClass('ui-icon-success-block wst-active');
    }else{
        $(obj).removeClass('ui-icon-success-block wst-active').addClass('ui-icon-unchecked-s');
    }
}
//清空
function emptyOpt(str){
	var s = str.split(',');
	for(var i=0;i<s.length;i++){
		$(s[i]).empty().html('<option value="">请选择</option>');
	}
}
$(document).ready(function(){
	initFooter('users');
});