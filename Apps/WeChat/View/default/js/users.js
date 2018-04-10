//返回个人信息 
function returnUserinfo(){
	WST.showHide('','.useri_nickname,#useri_nickname,.useri_nicknamed,.useri_sex,#useri_sex,.return_useri');
	fadeIns('.useri_info,#useri_info,#footer,.return_users');
}
//修改昵称面板
function openNickName(){
	WST.showHide('','.useri_info,#useri_info,#footer,.return_users');
	fadeIns('.useri_nickname,#useri_nickname,.useri_nicknamed,.return_useri');
}
//修改昵称
function editNickName(){
    var userName = $('#userName').val();
    if(userName==''){
    	WST.msg('昵称不能为空','info');
	    $('#userName').focus();
        return false;
    }
    $.post(ThinkPHP.U('WeChat/Users/editUserInfo'), {userName:userName}, function(data){
        var json = WST.toJson(data);
        if(json.status == '1'){
        	WST.msg('修改昵称成功','success');
            $('#nickname').html(userName);
    	    setTimeout(function(){
    	    	returnUserinfo();
    	    },1000);
        }else{
        	WST.msg('修改昵称失败，请重试','warn');
            return false;
        }
    });
}
//修改性別面板
function openUserSex(){
	WST.showHide('','.useri_info,#useri_info,#footer,.return_users');
	fadeIns('.useri_sex,#useri_sex,.return_useri');
}
//修改性别
function eidtUserSex(obj, userSex){
	$(obj).children('.wst-list-infose2').html('<i class="ui-icon-checked-s wst-icon-checked-s_se"></i>');
	$(obj).siblings().children('.wst-list-infose2').html('');
    $.post(ThinkPHP.U('WeChat/Users/editUserInfo'), {userSex:userSex}, function(data){
        var json = WST.toJson(data);
        if(json.status == '1'){
            var newUserSex = '';
            if(userSex==0){
                newUserSex = '保密';
            }else if(userSex==1){
                newUserSex = '男';
            }else if(userSex==2){
                newUserSex = '女';
            }
            WST.msg('修改性别成功','success');
            $('#usersex').html(newUserSex);
    	    setTimeout(function(){
    	    	returnUserinfo();
    	    },1000);
        }else{
        	WST.msg('修改性别失败，请重试','warn');
            return false;
        }
    });
}
function fadeIns(str){
	var s = str.split(',');
	for(var i=0;i<s.length;i++){
		$(s[i]).fadeIn(500);
	}
}
$(document).ready(function(){
    initFooter('users');
});